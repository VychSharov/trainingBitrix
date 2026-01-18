<?php
declare(strict_types=1);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
require(__DIR__ . '/AbstractPage.php');

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
final class Homework3DoctorsPage extends AbstractPage
{
    private const DOCTORS_IBLOCK_CODE = 'doctors';
    private const COMMON_LANG_FILE = '/otus/lang/ru.php';

    /** @var string|null */
    private $errorMessage = null;

    /**
     * @return void
     */
    protected function handlePost(): void
    {
        $this->loadCommonLang();

        if (!check_bitrix_sessid()) {
            $this->errorMessage = Loc::getMessage('OTUS_HW3_BAD_SESSID') ?: 'Неверная сессия (sessid)';
            return;
        }

        if (!Loader::includeModule('iblock')) {
            $this->errorMessage = Loc::getMessage('OTUS_HW3_ERR_IBLOCK') ?: 'Модуль iblock не подключен';
            return;
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action !== 'add_doctor') {
            return;
        }

        $doctorsIblockId = $this->getIblockIdByCode(self::DOCTORS_IBLOCK_CODE);
        if ($doctorsIblockId <= 0) {
            $this->errorMessage = Loc::getMessage('OTUS_HW3_DOCTORS_IBLOCK_NOT_FOUND') ?: 'Инфоблок врачей не найден (проверьте CODE)';
            return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $firstName = trim((string)($_POST['name'] ?? ''));
        $lastName  = trim((string)($_POST['lastname'] ?? ''));

        if ($title === '') {
            $this->errorMessage = Loc::getMessage('OTUS_HW3_TITLE_REQUIRED') ?: 'Поле "Название" обязательно';
            return;
        }

        $elementCode = 'doctor-' . substr(md5($title . '|' . microtime(true)), 0, 12);

        $el = new CIBlockElement();
        $newId = (int)$el->Add([
            'IBLOCK_ID' => $doctorsIblockId,
            'NAME' => $title,
            'CODE' => $elementCode,
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'NAME' => $firstName,
                'LASTNAME' => $lastName,
            ],
        ]);

        if ($newId <= 0) {
            $this->errorMessage = $el->LAST_ERROR ?: (Loc::getMessage('OTUS_HW3_ADD_FAILED') ?: 'Не удалось добавить врача');
            return;
        }

        LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
    }

    /**
     * @return void
     */
    protected function render(): void
    {
        $this->loadCommonLang();

        if (!Loader::includeModule('iblock')) {
            echo htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_ERR_IBLOCK') ?: 'Модуль iblock не подключен');
            return;
        }

        $doctorsIblockId = $this->getIblockIdByCode(self::DOCTORS_IBLOCK_CODE);
        if ($doctorsIblockId <= 0) {
            echo '<p>' . htmlspecialcharsbx(
                Loc::getMessage('OTUS_HW3_DOCTORS_IBLOCK_NOT_FOUND') ?: 'Инфоблок врачей не найден (проверьте CODE)'
            ) . '</p>';
            return;
        }

        echo '<p><a href="index.php">' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_BACK_TO_MENU') ?: '← Меню') . '</a></p>';
        echo '<h1>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_DOCTORS_TITLE') ?: 'Врачи') . '</h1>';

        if ($this->errorMessage) {
            echo '<div style="padding:10px;border:1px solid #c00;margin:10px 0;">'
                . htmlspecialcharsbx($this->errorMessage)
                . '</div>';
        }

        echo '<h3>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_ADD_DOCTOR_TITLE') ?: 'Добавить врача') . '</h3>';
        echo '<form method="post">';
        echo bitrix_sessid_post();
        echo '<input type="hidden" name="action" value="add_doctor">';

        echo '<div>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_TITLE_FIELD') ?: 'Название') . ': '
            . '<input name="title" required></div>';

        echo '<div>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_NAME') ?: 'Имя') . ': '
            . '<input name="name"></div>';

        echo '<div>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_LASTNAME') ?: 'Фамилия') . ': '
            . '<input name="lastname"></div>';

        echo '<button type="submit">' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_ADD') ?: 'Добавить') . '</button>';
        echo '</form>';

        echo '<hr>';

        $doctors = ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['=IBLOCK_ID' => $doctorsIblockId],
            'order' => ['NAME' => 'ASC'],
        ])->fetchAll();

        if (!$doctors) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_EMPTY') ?: 'Список пуст') . '</p>';
            return;
        }

        echo '<ul>';
        foreach ($doctors as $doctor) {
            $url = 'procedures.php?id=' . (int)$doctor['ID'];
            echo '<li><a href="' . htmlspecialcharsbx($url) . '">' . htmlspecialcharsbx($doctor['NAME']) . '</a></li>';
        }
        echo '</ul>';
    }

    /**
     * @return void
     */
    private function loadCommonLang(): void
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . self::COMMON_LANG_FILE;
        if (is_file($path)) {
            Loc::loadMessages($path);
        }
    }

    /**
     * @param string $code
     * @return int
     */
    private function getIblockIdByCode(string $code): int
    {
        $row = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['=CODE' => $code],
            'limit' => 1,
        ])->fetch();

        return $row ? (int)$row['ID'] : 0;
    }
}

$page = new Homework3DoctorsPage();
$page->run();

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
