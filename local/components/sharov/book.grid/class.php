<?php

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Sharov\Crmcustomtab\Model\BookTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * Class SharovBookGridComponent
 *
 * Компонент вывода книг в стандартном UI Grid.
 */
class SharovBookGridComponent extends CBitrixComponent
{
    private const GRID_ID = 'SHAROV_CRM_BOOKS_GRID';

    /**
     * @return void
     */
    public function executeComponent()
    {
        if (!Loader::includeModule('sharov.crmcustomtab')) {
            ShowError('Module sharov.crmcustomtab is not installed');
            return;
        }

        $entityTypeId = (int)($this->arParams['ENTITY_TYPE_ID'] ?? 0);
        $entityId     = (int)($this->arParams['ENTITY_ID'] ?? 0);

        $gridOptions = new GridOptions(self::GRID_ID);

        $sorting = $gridOptions->GetSorting([
            'sort' => ['ID' => 'desc'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);

        $nav = new PageNavigation(self::GRID_ID);
        $navParams = $gridOptions->GetNavParams();
        $pageSize  = (int)($navParams['nPageSize'] ?? 20);

        $nav->allowAllRecords(false)
            ->setPageSize($pageSize)
            ->initFromUri();

        $filter = [
            '=ENTITY_TYPE_ID' => $entityTypeId,
            '=ENTITY_ID'      => $entityId,
        ];

        $result = BookTable::getList([
            'select' => [
                'ID',
                'ENTITY_TYPE_ID',
                'ENTITY_ID',
                'BOOK_NAME',
                'YEAR_PUB',
                'PAGES_CNT',
                'AUTHORS',
                'DATE_PUB',
                'DATE_CREATE',
            ],
            'filter' => $filter,
            'order'  => $sorting['sort'],
            'offset' => $nav->getOffset(),
            'limit'  => $nav->getLimit(),
            'count_total' => true,
        ]);

        $rows = [];
        foreach ($result->fetchAll() as $row) {
            $datePub = $row['DATE_PUB'] ? $row['DATE_PUB']->toString() : '';
            $dateCreate = $row['DATE_CREATE'] ? $row['DATE_CREATE']->toString() : '';

            $data = [
                'ID' => $row['ID'],
                'ENTITY_TYPE_ID' => $row['ENTITY_TYPE_ID'],
                'ENTITY_ID' => $row['ENTITY_ID'],
                'BOOK_NAME' => $row['BOOK_NAME'],
                'YEAR_PUB' => $row['YEAR_PUB'],
                'PAGES_CNT' => $row['PAGES_CNT'],
                'AUTHORS' => $row['AUTHORS'],
                'DATE_PUB' => $datePub,
                'DATE_CREATE' => $dateCreate,
            ];

            $rows[] = [
                'id' => $row['ID'],
                'data' => $data,
                'columns' => $data, // КЛЮЧЕВО: чтобы грид показывал все колонки
            ];
        }

        $nav->setRecordCount($result->getCount());

        $this->arResult = [
            'GRID_ID' => self::GRID_ID,
            'COLUMNS' => $this->getColumns(),
            'ROWS' => $rows,
            'NAV' => $nav,
        ];

        $this->includeComponentTemplate();
    }

    /**
     * @return array
     */
    private function getColumns(): array
    {
        return [
            ['id' => 'ID', 'name' => 'ID', 'default' => true, 'sort' => 'ID'],
            ['id' => 'ENTITY_TYPE_ID', 'name' => Loc::getMessage('SHAROV_ENTITY_TYPE_ID'), 'default' => true, 'sort' => 'ENTITY_TYPE_ID'],
            ['id' => 'ENTITY_ID', 'name' => Loc::getMessage('SHAROV_ENTITY_ID'), 'default' => true, 'sort' => 'ENTITY_ID'],

            ['id' => 'BOOK_NAME', 'name' => Loc::getMessage('SHAROV_BOOK_NAME'), 'default' => true, 'sort' => 'BOOK_NAME'],
            ['id' => 'YEAR_PUB', 'name' => Loc::getMessage('SHAROV_YEAR_PUB'), 'default' => true, 'sort' => 'YEAR_PUB'],
            ['id' => 'PAGES_CNT', 'name' => Loc::getMessage('SHAROV_PAGES_CNT'), 'default' => true, 'sort' => 'PAGES_CNT'],
            ['id' => 'AUTHORS', 'name' => Loc::getMessage('SHAROV_AUTHORS'), 'default' => true, 'sort' => 'AUTHORS'],

            ['id' => 'DATE_PUB', 'name' => Loc::getMessage('SHAROV_DATE_PUB'), 'default' => true, 'sort' => 'DATE_PUB'],
            ['id' => 'DATE_CREATE', 'name' => Loc::getMessage('SHAROV_DATE_CREATE'), 'default' => true, 'sort' => 'DATE_CREATE'],
        ];
    }
}