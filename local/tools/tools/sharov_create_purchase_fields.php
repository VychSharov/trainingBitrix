<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Crm\Model\Dynamic\TypeTable;

global $USER;

if (!$USER->IsAdmin()) {
    die('Access denied');
}

Loader::includeModule('crm');

$type = TypeTable::getList([
    'select' => ['ID', 'ENTITY_TYPE_ID', 'TITLE'],
    'filter' => [
        '=TITLE' => 'Заявка на закупку',
    ],
    'limit' => 1,
])->fetch();

if (!$type) {
    die('Не найден смарт-процесс "Заявка на закупку". Сначала создай его в CRM.');
}

$entityTypeId = (int)$type['ENTITY_TYPE_ID'];
$userFieldEntityId = 'CRM_' . $entityTypeId;

echo 'Найден смарт-процесс: Заявка на закупку<br>';
echo 'ENTITY_TYPE_ID = ' . $entityTypeId . '<br>';
echo 'USER_FIELD_ENTITY_ID = ' . $userFieldEntityId . '<hr>';

/**
 * Создаёт пользовательское поле смарт-процесса, если его ещё нет.
 *
 * @param string $entityId
 * @param string $fieldName
 * @param string $title
 * @param string $type
 * @param array $settings
 * @return void
 */
function createSmartFieldIfNotExists($entityId, $fieldName, $title, $type, array $settings = [])
{
    $exists = CUserTypeEntity::GetList(
        [],
        [
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName,
        ]
    )->Fetch();

    if ($exists) {
        echo 'Уже есть: ' . $fieldName . '<br>';
        return;
    }

    $userTypeEntity = new CUserTypeEntity();

    $result = $userTypeEntity->Add([
        'ENTITY_ID' => $entityId,
        'FIELD_NAME' => $fieldName,
        'USER_TYPE_ID' => $type,
        'XML_ID' => $fieldName,
        'SORT' => 100,
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'Y',
        'SHOW_IN_LIST' => 'Y',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
        'SETTINGS' => $settings,
        'EDIT_FORM_LABEL' => [
            'ru' => $title,
        ],
        'LIST_COLUMN_LABEL' => [
            'ru' => $title,
        ],
        'LIST_FILTER_LABEL' => [
            'ru' => $title,
        ],
        'ERROR_MESSAGE' => [
            'ru' => '',
        ],
        'HELP_MESSAGE' => [
            'ru' => '',
        ],
    ]);

    if ($result) {
        echo 'Создано: ' . $fieldName . '<br>';
    } else {
        global $APPLICATION;
        $exception = $APPLICATION->GetException();
        echo 'Ошибка: ' . $fieldName . ' — ' . ($exception ? $exception->GetString() : 'неизвестная ошибка') . '<br>';
    }
}

createSmartFieldIfNotExists(
    $userFieldEntityId,
    'UF_SC_REQUESTER_ID',
    'Инициатор заявки',
    'integer'
);

createSmartFieldIfNotExists(
    $userFieldEntityId,
    'UF_SC_APPROVER_ID',
    'Согласующий',
    'integer'
);

createSmartFieldIfNotExists(
    $userFieldEntityId,
    'UF_SC_AUTO_CREATED',
    'Автоматическая заявка',
    'boolean'
);

createSmartFieldIfNotExists(
    $userFieldEntityId,
    'UF_SC_REJECT_REASON',
    'Причина отказа',
    'string'
);

createSmartFieldIfNotExists(
    $userFieldEntityId,
    'UF_SC_SOURCE_PRODUCT_ID',
    'Запчасть',
    'integer'
);

createSmartFieldIfNotExists(
    $userFieldEntityId,
    'UF_SC_QUANTITY',
    'Количество',
    'double'
);

echo '<hr>Готово. После выполнения файл лучше удалить.';