<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Ошибка для exception");
?>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/logs/exceptions.log">Файл лога</a>
    </li>
</ul>

<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/otus/debug.php');
require_once __DIR__ . '/logger.php';

$logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/exceptions.log';
$logger  = new Logger(filePath: $logPath);

try {

    require __DIR__ . '/error.php';

} catch (Throwable $e) {

    $message =
        Loc::getMessage('EXCEPTION_TEST'). ' (' . get_class($e) . '): '. $e->getMessage(). ', ' . date('Y-m-d H:i:s');
    $logger->write($message);

    echo '[' . get_class($e) . ']' . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine();
}
?>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
