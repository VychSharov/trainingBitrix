<?php

namespace Local\Model\Iblock;

use Bitrix\Iblock\ElementTable;

class Dealers
{
    public const IBLOCK_ID = 20;
    public const PROP_RATING_ID = 75;
    public const PROP_CITY_ID = 76;

    public static function getIblockId(): int
    {
        return static::IBLOCK_ID;
    }

    public static function getRatingPropertyId(): int
    {
        return static::PROP_RATING_ID;
    }

    public static function getCityPropertyId(): int
    {
        return static::PROP_CITY_ID;
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
