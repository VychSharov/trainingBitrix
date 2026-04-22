<?php

namespace Sharov\Crmcustomtab\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class BookTable
 *
 * ORM-обёртка над таблицей sharov_crm_books.
 */
class BookTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'sharov_crm_books';
    }

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),

            (new IntegerField('ENTITY_TYPE_ID'))->configureRequired(true),
            (new IntegerField('ENTITY_ID'))->configureRequired(true),

            (new StringField('BOOK_NAME'))->configureRequired(true),
            (new IntegerField('YEAR_PUB')),
            (new IntegerField('PAGES_CNT')),
            (new StringField('AUTHORS')),

            (new DatetimeField('DATE_PUB')),
            (new DatetimeField('DATE_CREATE')),
        ];
    }
}