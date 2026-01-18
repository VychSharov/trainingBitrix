<?php

namespace Local\Model\Iblock;

use Bitrix\Iblock\ElementTable;

class Cars
{
    public const IBLOCK_ID = 19;
    public const PROP_YEAR_ID = 73;
    public const PROP_COLOR_ID = 74;
    public const PROP_BRAND_ID = 77;

    public static function getIblockId(): int
    {
        return static::IBLOCK_ID;
    }

    public static function getYearPropertyId(): int
    {
        return static::PROP_YEAR_ID;
    }

    public static function getColorPropertyId(): int
    {
        return static::PROP_COLOR_ID;
    }

    public static function getBrandPropertyId(): int
    {
        return static::PROP_BRAND_ID;
    }

    public static function getList(array $filter = [], int $limit = 50): array
    {
        $filter = array_merge(
            ['=IBLOCK_ID' => static::getIblockId(), '=ACTIVE' => 'Y'],
            $filter
        );

        return ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => $filter,
            'order'  => ['NAME' => 'ASC'],
            'limit'  => $limit,
        ])->fetchAll();
    }
}
