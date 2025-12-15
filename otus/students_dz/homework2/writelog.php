<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>

<?php
use Bitrix\Main\Localization\Loc;
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/otus/debug.php');

$APPLICATION->SetTitle(Loc::getMessage('LOG_TITLE'));

require_once __DIR__ . '/logger.php';

$logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/log_custom.log';

$logger = new Logger(filePath: $logPath);

$logger->write(
    Loc::getMessage('LOG_TEXT') . ', дата/время: ' . date('Y-m-d H:i:s')
);

?>
    <ul class="list-group">
        <li class="list-group-item">
            <a href="/local/logs/log_custom.log">Файл лога</a>,
        <?= Loc::getMessage('LOG_TEXT'); ?>
        </li>
    </ul>
<?

?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
