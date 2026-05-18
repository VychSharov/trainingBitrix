<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Sharov\ServiceCenter\Infrastructure\ModuleSettings;
use Sharov\ServiceCenter\Service\SetupValidator;

if (!$USER->IsAdmin()) {
    return;
}

Loader::includeModule('sharov.servicecenter');
Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    foreach (ModuleSettings::getOptionNames() as $optionName) {
        ModuleSettings::set($optionName, (string)$request->getPost($optionName));
    }
}

$tabs = [
    [
        'DIV' => 'sharov_sc_main',
        'TAB' => Loc::getMessage('SHAROV_SC_OPTIONS_TAB_MAIN'),
        'TITLE' => Loc::getMessage('SHAROV_SC_OPTIONS_TAB_MAIN_TITLE'),
    ],
    [
        'DIV' => 'sharov_sc_check',
        'TAB' => Loc::getMessage('SHAROV_SC_OPTIONS_TAB_CHECK'),
        'TITLE' => Loc::getMessage('SHAROV_SC_OPTIONS_TAB_CHECK_TITLE'),
    ],
];

$tabControl = new CAdminTabControl('sharovScOptionsTabControl', $tabs);

$options = [
    'service_category_name' => [Loc::getMessage('SHAROV_SC_OPTIONS_SERVICE_CATEGORY_NAME'), ModuleSettings::DEFAULT_SERVICE_CATEGORY_NAME],
    'service_category_id' => [Loc::getMessage('SHAROV_SC_OPTIONS_SERVICE_CATEGORY_ID'), ''],
    'final_stage_ids' => [Loc::getMessage('SHAROV_SC_OPTIONS_FINAL_STAGE_IDS'), ''],
    'tracked_product_ids' => [Loc::getMessage('SHAROV_SC_OPTIONS_TRACKED_PRODUCT_IDS'), ''],
    'purchase_group_code' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_GROUP_CODE'), ModuleSettings::DEFAULT_PURCHASE_GROUP_CODE],
    'purchase_head_group_code' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_HEAD_GROUP_CODE'), ModuleSettings::DEFAULT_PURCHASE_HEAD_GROUP_CODE],
    'purchase_entity_type_title' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_ENTITY_TYPE_TITLE'), ModuleSettings::DEFAULT_PURCHASE_TYPE_TITLE],
    'purchase_entity_type_id' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_ENTITY_TYPE_ID'), ''],
    'purchase_stage_approved_name' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_STAGE_APPROVED_NAME'), 'Одобрено'],
    'purchase_stage_approved' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_STAGE_APPROVED'), ''],
    'purchase_stage_done_name' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_STAGE_DONE_NAME'), 'Выполнено'],
    'purchase_stage_done' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_STAGE_DONE'), ''],
    'purchase_stage_rejected_name' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_STAGE_REJECTED_NAME'), 'Отклонено'],
    'purchase_stage_rejected' => [Loc::getMessage('SHAROV_SC_OPTIONS_PURCHASE_STAGE_REJECTED'), ''],
    'external_quantity_url' => [Loc::getMessage('SHAROV_SC_OPTIONS_EXTERNAL_QUANTITY_URL'), ModuleSettings::DEFAULT_RANDOM_QUANTITY_URL],
];

$validatorResult = (new SetupValidator())->validate();
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode(ModuleSettings::MODULE_ID) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?php $tabControl->Begin(); ?>

    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <div class="adm-info-message-wrap">
                <div class="adm-info-message">
                    <?= Loc::getMessage('SHAROV_SC_OPTIONS_HINT') ?>
                </div>
            </div>
        </td>
    </tr>

    <?php foreach ($options as $optionName => [$label, $defaultValue]): ?>
        <tr>
            <td width="40%" class="adm-detail-content-cell-l">
                <label for="<?= htmlspecialcharsbx($optionName) ?>"><?= htmlspecialcharsbx($label) ?>:</label>
            </td>
            <td width="60%" class="adm-detail-content-cell-r">
                <input
                    type="text"
                    size="70"
                    id="<?= htmlspecialcharsbx($optionName) ?>"
                    name="<?= htmlspecialcharsbx($optionName) ?>"
                    value="<?= htmlspecialcharsbx(Option::get(ModuleSettings::MODULE_ID, $optionName, $defaultValue)) ?>"
                >
            </td>
        </tr>
    <?php endforeach; ?>

    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <?php if ($validatorResult['success']): ?>
                <div class="adm-info-message-wrap adm-info-message-green">
                    <div class="adm-info-message"><?= Loc::getMessage('SHAROV_SC_OPTIONS_CHECK_SUCCESS') ?></div>
                </div>
            <?php else: ?>
                <div class="adm-info-message-wrap adm-info-message-red">
                    <div class="adm-info-message">
                        <strong><?= Loc::getMessage('SHAROV_SC_OPTIONS_CHECK_ERRORS') ?></strong>
                        <ul>
                            <?php foreach ($validatorResult['errors'] as $error): ?>
                                <li><?= htmlspecialcharsbx($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save">
    <?= bitrix_sessid_post(); ?>

    <?php $tabControl->End(); ?>
</form>
