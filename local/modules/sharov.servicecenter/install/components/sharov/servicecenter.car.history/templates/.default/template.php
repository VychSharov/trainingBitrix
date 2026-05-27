<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

/** @var array $arResult */
?>

<div class="sharov-sc-history">
    <h2 class="sharov-sc-history__title">
        <?= htmlspecialcharsbx($arResult['TITLE']) ?>
    </h2>

    <?php if (empty($arResult['CAR'])): ?>
        <div class="ui-alert ui-alert-danger">
            <span class="ui-alert-message">
                <?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_CAR_NOT_FOUND')) ?>
            </span>
        </div>
    <?php elseif (empty($arResult['DEALS'])): ?>
        <div class="ui-alert ui-alert-info">
            <span class="ui-alert-message">
                <?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_EMPTY')) ?>
            </span>
        </div>
    <?php else: ?>
        <table class="sharov-sc-history__table">
            <thead>
                <tr>
                    <th><?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_DEAL_TITLE')) ?></th>
                    <th><?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_DATE_CREATE')) ?></th>
                    <th><?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_STAGE')) ?></th>
                    <th><?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_RESPONSIBLE')) ?></th>
                    <th><?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_SUM')) ?></th>
                    <th><?= htmlspecialcharsbx(Loc::getMessage('SHAROV_SC_HISTORY_PRODUCTS')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arResult['DEALS'] as $deal): ?>
                    <tr>
                        <td>
                            <a href="/crm/deal/details/<?= (int)$deal['ID'] ?>/" target="_blank">
                                <?= htmlspecialcharsbx($deal['TITLE']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialcharsbx((string)$deal['DATE_CREATE']) ?></td>
                        <td><?= htmlspecialcharsbx($deal['STAGE_ID']) ?></td>
                        <td>
                            <?php if ((int)$deal['ASSIGNED_BY_ID'] > 0): ?>
                                <a href="/company/personal/user/<?= (int)$deal['ASSIGNED_BY_ID'] ?>/" target="_blank">
                                    ID <?= (int)$deal['ASSIGNED_BY_ID'] ?>
                                </a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialcharsbx((string)$deal['OPPORTUNITY']) ?>
                            <?= htmlspecialcharsbx((string)$deal['CURRENCY_ID']) ?>
                        </td>
                        <td>
                            <?php if (empty($deal['PRODUCTS'])): ?>
                                —
                            <?php else: ?>
                                <ul class="sharov-sc-history__products">
                                    <?php foreach ($deal['PRODUCTS'] as $product): ?>
                                        <li>
                                            <?= htmlspecialcharsbx($product['PRODUCT_NAME'] ?? $product['PRODUCT_ID'] ?? '') ?>
                                            <?php if (isset($product['QUANTITY'])): ?>
                                                — <?= htmlspecialcharsbx((string)$product['QUANTITY']) ?> <?= htmlspecialcharsbx($product['MEASURE_NAME'] ?? '') ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
