<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER;

if (!$USER || !$USER->IsAdmin()) {
    die('Access denied');
}

$entityId = 'CRM_1046';
$fieldName = 'UF_SC_PROCESSED';

$exists = CUserTypeEntity::GetList(
    [],
    [
        'ENTITY_ID' => $entityId,
        'FIELD_NAME' => $fieldName,
    ]
)->Fetch();

if ($exists) {
    echo 'Поле уже существует: ' . $fieldName;
    die();
}

$userTypeEntity = new CUserTypeEntity();

$fieldId = $userTypeEntity->Add([
    'ENTITY_ID' => $entityId,
    'FIELD_NAME' => $fieldName,
    'USER_TYPE_ID' => 'boolean',
    'XML_ID' => $fieldName,
    'SORT' => 600,
    'MULTIPLE' => 'N',
    'MANDATORY' => 'N',
    'SHOW_FILTER' => 'Y',
    'SHOW_IN_LIST' => 'Y',
    'EDIT_IN_LIST' => 'Y',
    'IS_SEARCHABLE' => 'N',
    'SETTINGS' => [
        'DEFAULT_VALUE' => 0,
        'DISPLAY' => 'CHECKBOX',
    ],
    'EDIT_FORM_LABEL' => [
        'ru' => 'Заявка обработана',
        'en' => 'Request processed',
    ],
    'LIST_COLUMN_LABEL' => [
        'ru' => 'Заявка обработана',
        'en' => 'Request processed',
    ],
    'LIST_FILTER_LABEL' => [
        'ru' => 'Заявка обработана',
        'en' => 'Request processed',
    ],
]);

if ($fieldId) {
    echo 'Поле создано. ID=' . (int)$fieldId;
} else {
    global $APPLICATION;

    $exception = $APPLICATION->GetException();

    echo 'Ошибка создания поля: '
        . ($exception ? $exception->GetString() : 'неизвестная ошибка');
}