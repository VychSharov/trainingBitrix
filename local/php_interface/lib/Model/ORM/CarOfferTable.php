<?php

namespace Local\Model\ORM;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Local\Model\Iblock\Cars;
use Local\Model\Iblock\Dealers;

class CarOfferTable extends DataManager
{
    public static function getTableName()
    {
        return 'b_my_car_offer';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))->configurePrimary(true)->configureAutocomplete(true),
            (new IntegerField('CAR_ID'))->configureRequired(true),
            (new IntegerField('DEALER_ID'))->configureRequired(true),

            (new FloatField('PRICE'))->configureDefaultValue(0.00),

            (new StringField('NOTE', [
                'validation' => function () {
                    return [new LengthValidator(null, 255)];
                },
            ])),

            (new DatetimeField('CREATED_AT'))->configureDefaultValue(function () {
                return new DateTime();
            }),

            (new Reference(
                'CAR',
                ElementTable::class,
                Join::on('this.CAR_ID', 'ref.ID')
                    ->where('ref.IBLOCK_ID', new SqlExpression('?i', Cars::getIblockId()))
            )),

            (new Reference(
                'DEALER',
                ElementTable::class,
                Join::on('this.DEALER_ID', 'ref.ID')
                    ->where('ref.IBLOCK_ID', new SqlExpression('?i', Dealers::getIblockId()))
            )),
        ];
    }
}
