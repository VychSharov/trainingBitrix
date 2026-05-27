<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

header('Content-Type: application/json; charset=UTF-8');

global $USER, $DB;

try {
    if (!$USER || !$USER->IsAuthorized()) {
        throw new RuntimeException('Пользователь не авторизован');
    }

    if (!Loader::includeModule('crm')) {
        throw new RuntimeException('Модуль crm не подключен');
    }

    Loader::includeModule('iblock');
    Loader::includeModule('catalog');

    $action = (string)($_REQUEST['action'] ?? '');

    if ($action === '') {
        throw new RuntimeException('Не указано действие');
    }

    /*
     * Общий список автомобилей:
     * https://cb523223.tw1.ru/services/lists/19/view/0/?list_section_id=
     */
    if (!defined('SC_GARAGE_LIST_IBLOCK_ID')) {
        define('SC_GARAGE_LIST_IBLOCK_ID', 19);
    }

    if (!defined('SC_GARAGE_LIST_XML_PREFIX')) {
        define('SC_GARAGE_LIST_XML_PREFIX', 'SC_CAR_');
    }

    /**
     * Определяет таблицу автомобилей.
     *
     * @return string
     */
    function scGarageGetCarTableName(): string
    {
        global $DB;

        static $foundTableName = null;

        if ($foundTableName !== null) {
            return $foundTableName;
        }

        $candidates = [
            'b_sharov_sc_car',
            'sharov_sc_cars',
            'sharov_sc_car',
            'sharov_servicecenter_cars',
            'sharov_servicecenter_car',
            'b_sharov_servicecenter_cars',
            'b_sharov_servicecenter_car',
            'sharov_servicecenter_garage',
            'sharov_sc_garage',
            'servicecenter_cars',
            'servicecenter_car',
        ];

        foreach ($candidates as $tableName) {
            $tableNameSql = $DB->ForSql($tableName);
            $result = $DB->Query("SHOW TABLES LIKE '{$tableNameSql}'");

            if ($result->Fetch()) {
                $foundTableName = $tableName;
                return $foundTableName;
            }
        }

        /*
         * Если имя таблицы неизвестно — ищем таблицу по набору колонок.
         */
        $tablesResult = $DB->Query("SHOW TABLES");

        while ($tableRow = $tablesResult->Fetch()) {
            $tableName = reset($tableRow);

            if (!$tableName) {
                continue;
            }

            $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tableName);

            if ($safeTableName === '') {
                continue;
            }

            $columns = [];

            try {
                $columnsResult = $DB->Query("DESCRIBE {$safeTableName}");

                while ($columnRow = $columnsResult->Fetch()) {
                    $columns[strtoupper((string)$columnRow['Field'])] = true;
                }
            } catch (Throwable $exception) {
                continue;
            }

            $score = 0;

            if (isset($columns['ID'])) {
                $score++;
            }

            if (
                isset($columns['CONTACT_ID'])
                || isset($columns['ID_CONTACT'])
                || isset($columns['OWNER_ID'])
                || isset($columns['CLIENT_ID'])
            ) {
                $score++;
            }

            if (
                isset($columns['BRAND'])
                || isset($columns['MARK'])
                || isset($columns['MAKE'])
            ) {
                $score++;
            }

            if (isset($columns['MODEL'])) {
                $score++;
            }

            if (
                isset($columns['LICENSE_PLATE'])
                || isset($columns['NUMBER'])
                || isset($columns['CAR_NUMBER'])
                || isset($columns['STATE_NUMBER'])
                || isset($columns['REG_NUMBER'])
            ) {
                $score++;
            }

            if (
                isset($columns['YEAR'])
                || isset($columns['CAR_YEAR'])
            ) {
                $score++;
            }

            if (isset($columns['COLOR'])) {
                $score++;
            }

            if (
                isset($columns['MILEAGE'])
                || isset($columns['ODOMETER'])
                || isset($columns['PROBEG'])
            ) {
                $score++;
            }

            if ($score >= 6) {
                $foundTableName = $safeTableName;
                return $foundTableName;
            }
        }

        throw new RuntimeException(
            'Не найдена таблица автомобилей. Выполни SQL: SHOW TABLES LIKE "%car%"; или SHOW TABLES LIKE "%garage%";'
        );
    }

    /**
     * Возвращает список колонок таблицы.
     *
     * @param string $tableName
     * @return array
     */
    function scGarageGetColumns(string $tableName): array
    {
        global $DB;

        $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $result = $DB->Query("DESCRIBE {$safeTableName}");

        $columns = [];

        while ($row = $result->Fetch()) {
            $columns[strtoupper((string)$row['Field'])] = (string)$row['Field'];
        }

        return $columns;
    }

    /**
     * Ищет колонку по возможным названиям.
     *
     * @param array $columns
     * @param array $variants
     * @return string
     */
    function scGarageColumn(array $columns, array $variants): string
    {
        foreach ($variants as $variant) {
            $key = strtoupper($variant);

            if (isset($columns[$key])) {
                return $columns[$key];
            }
        }

        return '';
    }

    /**
     * Возвращает карту колонок автомобиля.
     *
     * @param string $tableName
     * @return array
     */
    function scGarageGetColumnMap(string $tableName): array
    {
        $columns = scGarageGetColumns($tableName);

        $map = [
            'id' => scGarageColumn($columns, ['ID']),
            'contact' => scGarageColumn($columns, ['CONTACT_ID', 'ID_CONTACT', 'OWNER_ID', 'CLIENT_ID']),
            'brand' => scGarageColumn($columns, ['BRAND', 'MARK', 'MAKE']),
            'model' => scGarageColumn($columns, ['MODEL']),
            'number' => scGarageColumn($columns, ['LICENSE_PLATE', 'NUMBER', 'CAR_NUMBER', 'STATE_NUMBER', 'REG_NUMBER']),
            'year' => scGarageColumn($columns, ['YEAR', 'CAR_YEAR']),
            'color' => scGarageColumn($columns, ['COLOR']),
            'mileage' => scGarageColumn($columns, ['MILEAGE', 'ODOMETER', 'PROBEG']),
            'vin' => scGarageColumn($columns, ['VIN']),
        ];

        foreach (['id', 'contact', 'brand', 'model', 'number', 'year', 'color', 'mileage'] as $required) {
            if ($map[$required] === '') {
                throw new RuntimeException(
                    'В таблице автомобилей не найдена колонка для поля: ' . $required
                );
            }
        }

        return $map;
    }

    /**
     * Нормализует строку автомобиля.
     *
     * @param array $row
     * @param array $map
     * @return array
     */
    function scGarageNormalizeCar(array $row, array $map): array
    {
        return [
            'id' => (int)$row[$map['id']],
            'contactId' => (int)$row[$map['contact']],
            'brand' => (string)$row[$map['brand']],
            'model' => (string)$row[$map['model']],
            'number' => (string)$row[$map['number']],
            'year' => (int)$row[$map['year']],
            'color' => (string)$row[$map['color']],
            'mileage' => (int)$row[$map['mileage']],
            'label' => trim(
                (string)$row[$map['brand']]
                . ' '
                . (string)$row[$map['model']]
                . ' — '
                . (string)$row[$map['number']]
                . ', '
                . (string)$row[$map['year']]
                . ', '
                . (string)$row[$map['color']]
            ),
        ];
    }

    /**
     * Возвращает автомобиль по ID.
     *
     * @param int $carId
     * @return array
     */
    function scGarageGetCarById(int $carId): array
    {
        global $DB;

        $tableName = scGarageGetCarTableName();
        $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $map = scGarageGetColumnMap($tableName);

        $idCol = $map['id'];

        $result = $DB->Query("
            SELECT *
            FROM {$safeTableName}
            WHERE `{$idCol}` = {$carId}
            LIMIT 1
        ");

        $row = $result->Fetch();

        if (!$row) {
            throw new RuntimeException('Автомобиль не найден');
        }

        return scGarageNormalizeCar($row, $map);
    }

    /**
     * Ищет свойство инфоблока по CODE или NAME.
     *
     * @param int $iblockId
     * @param array $variants
     * @return int
     */
    function scGarageFindIblockPropertyId(int $iblockId, array $variants): int
    {
        foreach ($variants as $variant) {
            $variant = trim((string)$variant);

            if ($variant === '') {
                continue;
            }

            $propertyResult = CIBlockProperty::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    '=CODE' => $variant,
                ]
            );

            $property = $propertyResult->Fetch();

            if ($property) {
                return (int)$property['ID'];
            }

            $propertyResult = CIBlockProperty::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    '=NAME' => $variant,
                ]
            );

            $property = $propertyResult->Fetch();

            if ($property) {
                return (int)$property['ID'];
            }
        }

        return 0;
    }

    /**
     * Добавляет значение свойства, если такое свойство существует.
     *
     * @param array $props
     * @param int $iblockId
     * @param array $variants
     * @param mixed $value
     * @return void
     */
    function scGarageAddListPropertyValue(array &$props, int $iblockId, array $variants, $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $propertyId = scGarageFindIblockPropertyId($iblockId, $variants);

        if ($propertyId <= 0) {
            return;
        }

        $props[$propertyId] = $value;
    }

    /**
     * Ищет элемент общего списка автомобилей по XML_ID.
     *
     * @param int $carId
     * @return int
     */
    function scGarageFindListElementIdByCarId(int $carId): int
    {
        if ($carId <= 0) {
            return 0;
        }

        $result = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => SC_GARAGE_LIST_IBLOCK_ID,
                '=XML_ID' => SC_GARAGE_LIST_XML_PREFIX . $carId,
            ],
            false,
            false,
            [
                'ID',
            ]
        );

        $element = $result->Fetch();

        return $element ? (int)$element['ID'] : 0;
    }

    /**
     * Создаёт или обновляет автомобиль в общем списке автомобилей.
     *
     * @param int $carId
     * @param int $contactId
     * @param string $brand
     * @param string $model
     * @param string $number
     * @param int $year
     * @param string $color
     * @param int $mileage
     * @return void
     */
    function scGarageSyncCarToCommonList(
        int $carId,
        int $contactId,
        string $brand,
        string $model,
        string $number,
        int $year,
        string $color,
        int $mileage
    ): void {
        if ($carId <= 0) {
            return;
        }

        if (!Loader::includeModule('iblock')) {
            return;
        }

        $iblockId = SC_GARAGE_LIST_IBLOCK_ID;
        $name = trim($brand . ' ' . $model . ' — ' . $number);

        if ($name === '' || $name === '—') {
            $name = 'Автомобиль #' . $carId;
        }

        $props = [];

        /*
         * Если свойства в списке называются по-другому,
         * скрипт их просто пропустит.
         */
        scGarageAddListPropertyValue($props, $iblockId, ['BRAND', 'MARKA', 'MARK', 'Марка'], $brand);
        scGarageAddListPropertyValue($props, $iblockId, ['MODEL', 'Модель'], $model);
        scGarageAddListPropertyValue($props, $iblockId, ['LICENSE_PLATE', 'NUMBER', 'CAR_NUMBER', 'STATE_NUMBER', 'Госномер', 'Номер'], $number);
        scGarageAddListPropertyValue($props, $iblockId, ['YEAR', 'CAR_YEAR', 'Год'], $year > 0 ? $year : null);
        scGarageAddListPropertyValue($props, $iblockId, ['COLOR', 'Цвет'], $color);
        scGarageAddListPropertyValue($props, $iblockId, ['MILEAGE', 'PROBEG', 'Пробег'], $mileage > 0 ? $mileage : null);
        scGarageAddListPropertyValue($props, $iblockId, ['CONTACT_ID', 'CLIENT_ID', 'CONTACT', 'Клиент', 'Контакт'], $contactId > 0 ? $contactId : null);
        scGarageAddListPropertyValue($props, $iblockId, ['SC_CAR_ID', 'CAR_ID', 'ID автомобиля'], $carId);

        /*
         * Старые ID свойств списка автомобилей:
         * 73 — год, 74 — цвет, 77 — марка.
         */
        $props[77] = $brand;

        if ($year > 0) {
            $props[73] = $year;
        }

        if ($color !== '') {
            $props[74] = $color;
        }

        $fields = [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'NAME' => $name,
            'XML_ID' => SC_GARAGE_LIST_XML_PREFIX . $carId,
            'PROPERTY_VALUES' => $props,
        ];

        $elementObject = new CIBlockElement();
        $elementId = scGarageFindListElementIdByCarId($carId);

        if ($elementId > 0) {
            $updateFields = $fields;
            unset($updateFields['IBLOCK_ID']);

            if (!$elementObject->Update($elementId, $updateFields)) {
                throw new RuntimeException(
                    'Автомобиль сохранён в гараже, но не обновился в общем списке: '
                    . $elementObject->LAST_ERROR
                );
            }

            return;
        }

        $newElementId = (int)$elementObject->Add($fields);

        if ($newElementId <= 0) {
            throw new RuntimeException(
                'Автомобиль сохранён в гараже, но не добавился в общий список: '
                . $elementObject->LAST_ERROR
            );
        }
    }

    /**
     * Удаляет автомобиль из общего списка автомобилей.
     *
     * @param int $carId
     * @return void
     */
    function scGarageDeleteCarFromCommonList(int $carId): void
    {
        if ($carId <= 0) {
            return;
        }

        if (!Loader::includeModule('iblock')) {
            return;
        }

        $elementId = scGarageFindListElementIdByCarId($carId);

        if ($elementId > 0) {
            CIBlockElement::Delete($elementId);
        }
    }

    /**
     * Возвращает название стадии.
     *
     * @param string $stageId
     * @return string
     */
    function scGarageGetStageName(string $stageId): string
    {
        global $DB;

        if ($stageId === '') {
            return '';
        }

        $stageIdSql = $DB->ForSql($stageId);

        $result = $DB->Query("
            SELECT NAME
            FROM b_crm_status
            WHERE STATUS_ID = '{$stageIdSql}'
            LIMIT 1
        ");

        $row = $result->Fetch();

        return $row ? (string)$row['NAME'] : $stageId;
    }

    /**
     * Возвращает имя пользователя.
     *
     * @param int $userId
     * @return array
     */
    function scGarageGetUserLabel(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'id' => 0,
                'label' => 'Не указан',
                'url' => '',
            ];
        }

        $userResult = CUser::GetByID($userId);
        $user = $userResult->Fetch();

        if (!$user) {
            return [
                'id' => $userId,
                'label' => 'ID ' . $userId,
                'url' => '/company/personal/user/' . $userId . '/',
            ];
        }

        $label = trim(
            (string)$user['LAST_NAME']
            . ' '
            . (string)$user['NAME']
            . ' '
            . (string)$user['SECOND_NAME']
        );

        if ($label === '') {
            $label = (string)$user['LOGIN'];
        }

        return [
            'id' => $userId,
            'label' => $label,
            'url' => '/company/personal/user/' . $userId . '/',
        ];
    }

    /**
     * Возвращает запчасти сделки.
     *
     * @param int $dealId
     * @return array
     */
    function scGarageGetDealParts(int $dealId): array
    {
        $parts = [];

        if (!class_exists('CCrmProductRow')) {
            return $parts;
        }

        $rows = CCrmProductRow::LoadRows('D', $dealId);

        if (!is_array($rows)) {
            return $parts;
        }

        foreach ($rows as $row) {
            $name = trim((string)($row['PRODUCT_NAME'] ?? ''));

            if ($name === '' && !empty($row['PRODUCT_ID'])) {
                $elementResult = CIBlockElement::GetList(
                    [],
                    [
                        '=ID' => (int)$row['PRODUCT_ID'],
                    ],
                    false,
                    false,
                    [
                        'ID',
                        'NAME',
                    ]
                );

                $element = $elementResult->Fetch();

                if ($element) {
                    $name = (string)$element['NAME'];
                }
            }

            if ($name === '') {
                $name = 'Товар ID ' . (int)($row['PRODUCT_ID'] ?? 0);
            }

            $quantity = (float)($row['QUANTITY'] ?? 0);

            $parts[] = [
                'name' => $name,
                'quantity' => $quantity,
                'text' => $name . ' — ' . $quantity . ' шт.',
            ];
        }

        return $parts;
    }

    $tableName = scGarageGetCarTableName();
    $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $map = scGarageGetColumnMap($tableName);

    if ($action === 'list') {
        $contactId = (int)($_REQUEST['contactId'] ?? 0);

        if ($contactId <= 0) {
            throw new RuntimeException('Не указан контакт');
        }

        $contactCol = $map['contact'];

        $result = $DB->Query("
            SELECT *
            FROM {$safeTableName}
            WHERE `{$contactCol}` = {$contactId}
            ORDER BY `{$map['id']}` DESC
        ");

        $cars = [];

        while ($row = $result->Fetch()) {
            $cars[] = scGarageNormalizeCar($row, $map);
        }

        echo Json::encode([
            'success' => true,
            'cars' => $cars,
        ]);

        die();
    }

    if ($action === 'save') {
        $carId = (int)($_REQUEST['id'] ?? 0);
        $contactId = (int)($_REQUEST['contactId'] ?? 0);

        if ($contactId <= 0) {
            throw new RuntimeException('Не указан контакт');
        }

        $brand = trim((string)($_REQUEST['brand'] ?? ''));
        $model = trim((string)($_REQUEST['model'] ?? ''));
        $number = trim((string)($_REQUEST['number'] ?? ''));
        $year = (int)($_REQUEST['year'] ?? 0);
        $color = trim((string)($_REQUEST['color'] ?? ''));
        $mileage = (int)($_REQUEST['mileage'] ?? 0);

        if ($brand === '') {
            throw new RuntimeException('Укажите марку автомобиля');
        }

        if ($model === '') {
            throw new RuntimeException('Укажите модель автомобиля');
        }

        if ($number === '') {
            throw new RuntimeException('Укажите номер автомобиля');
        }

        $brandSql = $DB->ForSql($brand);
        $modelSql = $DB->ForSql($model);
        $numberSql = $DB->ForSql($number);
        $colorSql = $DB->ForSql($color);

        if ($carId > 0) {
            $DB->Query("
                UPDATE {$safeTableName}
                SET
                    `{$map['brand']}` = '{$brandSql}',
                    `{$map['model']}` = '{$modelSql}',
                    `{$map['number']}` = '{$numberSql}',
                    `{$map['year']}` = {$year},
                    `{$map['color']}` = '{$colorSql}',
                    `{$map['mileage']}` = {$mileage}
                WHERE `{$map['id']}` = {$carId}
                  AND `{$map['contact']}` = {$contactId}
            ");
        } else {
            $DB->Query("
                INSERT INTO {$safeTableName}
                    (
                        `{$map['contact']}`,
                        `{$map['brand']}`,
                        `{$map['model']}`,
                        `{$map['number']}`,
                        `{$map['year']}`,
                        `{$map['color']}`,
                        `{$map['mileage']}`
                    )
                VALUES
                    (
                        {$contactId},
                        '{$brandSql}',
                        '{$modelSql}',
                        '{$numberSql}',
                        {$year},
                        '{$colorSql}',
                        {$mileage}
                    )
            ");

            $carId = (int)$DB->LastID();
        }

        scGarageSyncCarToCommonList(
            $carId,
            $contactId,
            $brand,
            $model,
            $number,
            $year,
            $color,
            $mileage
        );

        echo Json::encode([
            'success' => true,
            'id' => $carId,
        ]);

        die();
    }

    if ($action === 'delete') {
        $carId = (int)($_REQUEST['id'] ?? 0);

        if ($carId <= 0) {
            throw new RuntimeException('Не указан автомобиль');
        }

        /*
         * Нельзя удалять автомобиль, если по нему уже есть сделки.
         * Иначе в сделках останется UF_CRM_SC_CAR_ID со ссылкой на несуществующий автомобиль,
         * а история обслуживания станет битой.
         */
        $dealResult = CCrmDeal::GetListEx(
            [
                'DATE_CREATE' => 'DESC',
            ],
            [
                'CHECK_PERMISSIONS' => 'N',
                '=UF_CRM_SC_CAR_ID' => $carId,
            ],
            false,
            [
                'nTopCount' => 1,
            ],
            [
                'ID',
                'TITLE',
                'DATE_CREATE',
            ]
        );

        $deal = $dealResult->Fetch();

        if ($deal) {
            throw new RuntimeException(
                'Нельзя удалить автомобиль, потому что по нему уже есть история обращений. '
                . 'Найдена сделка: "' . (string)$deal['TITLE'] . '"'
            );
        }

        $DB->Query("
            DELETE FROM {$safeTableName}
            WHERE `{$map['id']}` = {$carId}
        ");

        scGarageDeleteCarFromCommonList($carId);

        echo Json::encode([
            'success' => true,
        ]);

        die();
    }

    if ($action === 'history') {
        $carId = (int)($_REQUEST['carId'] ?? 0);
        $contactId = (int)($_REQUEST['contactId'] ?? 0);

        if ($carId <= 0) {
            throw new RuntimeException('Не указан автомобиль');
        }

        $car = scGarageGetCarById($carId);

        $contactName = '';

        if ($contactId > 0) {
            $contactResult = CCrmContact::GetListEx(
                [],
                [
                    '=ID' => $contactId,
                    'CHECK_PERMISSIONS' => 'N',
                ],
                false,
                false,
                [
                    'ID',
                    'NAME',
                    'LAST_NAME',
                    'SECOND_NAME',
                ]
            );

            $contact = $contactResult->Fetch();

            if ($contact) {
                $contactName = trim(
                    (string)$contact['LAST_NAME']
                    . ' '
                    . (string)$contact['NAME']
                    . ' '
                    . (string)$contact['SECOND_NAME']
                );
            }
        }

        $dealCarField = 'UF_CRM_SC_CAR_ID';

        $dealResult = CCrmDeal::GetListEx(
            [
                'DATE_CREATE' => 'DESC',
            ],
            [
                'CHECK_PERMISSIONS' => 'N',
                '=' . $dealCarField => $carId,
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'DATE_CREATE',
                'STAGE_ID',
                'ASSIGNED_BY_ID',
                'OPPORTUNITY',
                'CURRENCY_ID',
            ]
        );

        $deals = [];

        while ($deal = $dealResult->Fetch()) {
            $assigned = scGarageGetUserLabel((int)$deal['ASSIGNED_BY_ID']);
            $parts = scGarageGetDealParts((int)$deal['ID']);

            $deals[] = [
                'id' => (int)$deal['ID'],
                'title' => (string)$deal['TITLE'],
                'url' => '/crm/deal/details/' . (int)$deal['ID'] . '/',
                'dateCreate' => (string)$deal['DATE_CREATE'],
                'stageId' => (string)$deal['STAGE_ID'],
                'stageName' => scGarageGetStageName((string)$deal['STAGE_ID']),
                'assigned' => $assigned,
                'sum' => number_format((float)$deal['OPPORTUNITY'], 2, '.', ' ')
                    . ' '
                    . (string)$deal['CURRENCY_ID'],
                'parts' => $parts,
            ];
        }

        echo Json::encode([
            'success' => true,
            'car' => $car,
            'contactName' => $contactName,
            'title' => $car['brand']
                . ' '
                . $car['model']
                . ' - '
                . $car['number']
                . ($contactName !== '' ? ' (' . $contactName . ')' : ''),
            'deals' => $deals,
        ]);

        die();
    }

    throw new RuntimeException('Неизвестное действие: ' . $action);
} catch (Throwable $exception) {
    echo Json::encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}