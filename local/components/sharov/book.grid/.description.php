<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('SHAROV_BOOK_GRID_NAME'),
    'DESCRIPTION' => Loc::getMessage('SHAROV_BOOK_GRID_DESC'),
    'SORT' => 100,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'sharov',
        'NAME' => Loc::getMessage('SHAROV_BOOK_GRID_GROUP'),
    ],
];