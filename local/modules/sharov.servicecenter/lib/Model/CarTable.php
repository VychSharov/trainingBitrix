<?php

namespace Sharov\ServiceCenter\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class CarTable extends DataManager
{
    /**
     * Возвращает имя таблицы.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_sharov_sc_car';
    }

    /**
     * Возвращает карту ORM-полей.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),

            (new IntegerField('CONTACT_ID'))
                ->configureRequired(true),

            (new StringField('BRAND'))
                ->configureRequired(true),

            (new StringField('MODEL'))
                ->configureRequired(true),

            (new StringField('LICENSE_PLATE'))
                ->configureRequired(true),

            new IntegerField('YEAR'),
            new StringField('COLOR'),
            new IntegerField('MILEAGE'),
            new StringField('VIN'),
            new DatetimeField('DATE_CREATE'),
            new DatetimeField('DATE_UPDATE'),
        ];
    }
}