<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER;

if (!$USER->IsAdmin()) {
    die('Access denied');
}

Loader::includeModule('crm');

/**
 * Создаёт пользовательское поле, если его ещё нет.
 *
 * @param string $fieldName
 * @param string $title
 * @param string $type
 * @param array $settings
 * @return void
 */
function createDealUserFieldIfNotExists($fieldName, $title, $type, array $settings = [])
{
    $entityId = 'CRM_DEAL';

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
        echo 'Ошибка: ' . $fieldName . ' — ' . $APPLICATION->GetException()->GetString() . '<br>';
    }
}

createDealUserFieldIfNotExists(
    'UF_CRM_SC_CAR_ID',
    'Автомобиль сервисного центра',
    'integer'
);

createDealUserFieldIfNotExists(
    'UF_CRM_SC_MILEAGE_IN',
    'Пробег при приемке',
    'integer'
);

createDealUserFieldIfNotExists(
    'UF_CRM_SC_COMPLAINT',
    'Жалоба клиента',
    'string'
);

createDealUserFieldIfNotExists(
    'UF_CRM_SC_MECHANIC_ID',
    'Механик',
    'integer'
);

createDealUserFieldIfNotExists(
    'UF_CRM_SC_IS_SERVICE_ORDER',
    'Сервисный заказ-наряд',
    'boolean'
);

echo '<hr>Готово. Файл после выполнения лучше удалить.';