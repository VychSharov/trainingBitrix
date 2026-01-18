<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Local\Model\ORM\CarOfferTable;
use Local\Model\Iblock\Cars;
use Local\Model\Iblock\Dealers;

$rows = CarOfferTable::getList([
    'select' => [
        'ID',
        'PRICE',
        'NOTE',
        'CREATED_AT',

        'CAR_NAME' => 'CAR.NAME',
        'DEALER_NAME' => 'DEALER.NAME',

        'BRAND' => 'CAR_BRAND.VALUE',
        'YEAR'  => 'CAR_YEAR.VALUE',
        'COLOR' => 'COLOR_ENUM.VALUE',

        'CITY'   => 'DEALER_CITY.VALUE',
        'RATING' => 'DEALER_RATING.VALUE',
    ],
    'runtime' => [
        'CAR_BRAND' => new ReferenceField(
            'CAR_BRAND',
            ElementPropertyTable::class,
            Join::on('this.CAR_ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('ref.IBLOCK_PROPERTY_ID', Cars::getBrandPropertyId())
        ),
        'CAR_YEAR' => new ReferenceField(
            'CAR_YEAR',
            ElementPropertyTable::class,
            Join::on('this.CAR_ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('ref.IBLOCK_PROPERTY_ID', Cars::getYearPropertyId())
        ),
        'CAR_COLOR' => new ReferenceField(
            'CAR_COLOR',
            ElementPropertyTable::class,
            Join::on('this.CAR_ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('ref.IBLOCK_PROPERTY_ID', Cars::getColorPropertyId())
        ),
        'COLOR_ENUM' => new ReferenceField(
            'COLOR_ENUM',
            PropertyEnumerationTable::class,
            Join::on('this.CAR_COLOR.VALUE', 'ref.ID')
        ),
        'DEALER_CITY' => new ReferenceField(
            'DEALER_CITY',
            ElementPropertyTable::class,
            Join::on('this.DEALER_ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('ref.IBLOCK_PROPERTY_ID', Dealers::getCityPropertyId())
        ),
        'DEALER_RATING' => new ReferenceField(
            'DEALER_RATING',
            ElementPropertyTable::class,
            Join::on('this.DEALER_ID', 'ref.IBLOCK_ELEMENT_ID')
                ->where('ref.IBLOCK_PROPERTY_ID', Dealers::getRatingPropertyId())
        ),
    ],
    'order' => ['ID' => 'DESC'],
])->fetchAll();
?>

<?php if (!$rows): ?>
    <p>Данных нет</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
        <tr>
            <th>ID</th>
            <th>Автомобиль</th>
            <th>Марка</th>
            <th>Год</th>
            <th>Цвет</th>
            <th>Дилер</th>
            <th>Город</th>
            <th>Рейтинг</th>
            <th>Цена</th>
            <th>Комментарий</th>
            <th>Создано</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int)$row['ID'] ?></td>
                <td><?= htmlspecialcharsbx($row['CAR_NAME']) ?></td>
                <td><?= htmlspecialcharsbx($row['BRAND']) ?></td>
                <td><?= htmlspecialcharsbx($row['YEAR']) ?></td>
                <td><?= htmlspecialcharsbx($row['COLOR']) ?></td>
                <td><?= htmlspecialcharsbx($row['DEALER_NAME']) ?></td>
                <td><?= htmlspecialcharsbx($row['CITY']) ?></td>
                <td><?= htmlspecialcharsbx($row['RATING']) ?></td>
                <td><?= htmlspecialcharsbx($row['PRICE']) ?></td>
                <td><?= htmlspecialcharsbx($row['NOTE']) ?></td>
                <td><?= htmlspecialcharsbx($row['CREATED_AT']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
