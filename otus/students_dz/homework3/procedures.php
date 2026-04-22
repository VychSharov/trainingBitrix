<?php
declare(strict_types=1);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
require(__DIR__ . '/AbstractPage.php');

use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Class Homework3ProceduresPage
 *
 * Shows procedures linked to selected doctor, allows adding procedures and linking them.
 */
final class Homework3ProceduresPage extends AbstractPage
{
    private const DOCTORS_IBLOCK_CODE = 'doctors';
    private const PROCEDURES_IBLOCK_CODE = 'procedures';
    private const LINK_PROP_CODE = 'PROTSEDURA_ID';

    private const COMMON_LANG_FILE = '/otus/lang/ru.php';

    /**
     * @return void
     */
    protected function handlePost(): void
    {
        $this->loadCommonLang();

        if (!check_bitrix_sessid()) {
            return;
        }

        if (!Loader::includeModule('iblock')) {
            return;
        }

        $doctorId = (int)($_GET['id'] ?? 0);
        if ($doctorId <= 0) {
            return;
        }

        $doctorsIblockId = $this->getIblockIdByCode(self::DOCTORS_IBLOCK_CODE);
        $proceduresIblockId = $this->getIblockIdByCode(self::PROCEDURES_IBLOCK_CODE);
        if ($doctorsIblockId <= 0 || $proceduresIblockId <= 0) {
            return;
        }

        $action = (string)($_POST['action'] ?? '');

        // 1) Add procedure
        if ($action === 'add_procedure') {
            $procName = trim((string)($_POST['proc_name'] ?? ''));
            if ($procName !== '') {
                $el = new CIBlockElement();
                $el->Add([
                    'IBLOCK_ID' => $proceduresIblockId,
                    'NAME' => $procName,
                ]);
            }

            LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
        }

        // 2) Link existing procedure to doctor (WITHOUT deleting previous links)
        if ($action === 'link_procedure') {
            $procedureId = (int)($_POST['procedure_id'] ?? 0);
            if ($procedureId > 0) {
                $linkPropId = $this->getPropertyIdByCode($doctorsIblockId, self::LINK_PROP_CODE);
                if ($linkPropId > 0) {
                    // current values
                    $currentRows = ElementPropertyTable::getList([
                        'select' => ['VALUE'],
                        'filter' => [
                            '=IBLOCK_ELEMENT_ID' => $doctorId,
                            '=IBLOCK_PROPERTY_ID' => $linkPropId,
                        ],
                    ])->fetchAll();

                    $values = [];
                    foreach ($currentRows as $row) {
                        $val = (int)$row['VALUE'];
                        if ($val > 0) {
                            $values[$val] = $val;
                        }
                    }

                    // add new one
                    $values[$procedureId] = $procedureId;

                    // write all back
                    CIBlockElement::SetPropertyValuesEx(
                        $doctorId,
                        $doctorsIblockId,
                        [$linkPropId => array_values($values)]
                    );
                }
            }

            LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
        }
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

        $doctorId = (int)($_GET['id'] ?? 0);

        echo '<p><a href="doctors.php">' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_BACK') ?: '← Назад') . '</a></p>';

        if ($doctorId <= 0) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_NO_DOCTOR') ?: 'Врач не выбран') . '</p>';
            return;
        }

        $doctorsIblockId = $this->getIblockIdByCode(self::DOCTORS_IBLOCK_CODE);
        $proceduresIblockId = $this->getIblockIdByCode(self::PROCEDURES_IBLOCK_CODE);

        if ($doctorsIblockId <= 0) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_DOCTORS_IBLOCK_NOT_FOUND') ?: 'Инфоблок врачей не найден') . '</p>';
            return;
        }

        if ($proceduresIblockId <= 0) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_PROCS_IBLOCK_NOT_FOUND') ?: 'Инфоблок процедур не найден') . '</p>';
            return;
        }

        $doctor = ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['=IBLOCK_ID' => $doctorsIblockId, '=ID' => $doctorId],
            'limit' => 1,
        ])->fetch();

        if (!$doctor) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_DOCTOR_NOT_FOUND') ?: 'Врач не найден') . '</p>';
            return;
        }

        $linkPropId = $this->getPropertyIdByCode($doctorsIblockId, self::LINK_PROP_CODE);
        if ($linkPropId <= 0) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_BAD_PROP') ?: 'Не найдено свойство связи') . '</p>';
            return;
        }

        $procedureIds = $this->getProcedureIdsByDoctor($doctorId, $linkPropId);

        echo '<h1>' . htmlspecialcharsbx($doctor['NAME']) . '</h1>';

        // --- Add procedure form ---
        echo '<h3>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_ADD_PROC_TITLE') ?: 'Добавить процедуру') . '</h3>';
        echo '<form method="post">';
        echo bitrix_sessid_post();
        echo '<input type="hidden" name="action" value="add_procedure">';
        echo '<div>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_PROC_NAME') ?: 'Название процедуры') . ': <input name="proc_name" required></div>';
        echo '<button type="submit">' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_ADD') ?: 'Добавить') . '</button>';
        echo '</form>';

        // --- Link existing procedure form ---
        $allProcedures = ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['=IBLOCK_ID' => $proceduresIblockId],
            'order' => ['NAME' => 'ASC'],
        ])->fetchAll();

        echo '<h3>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_LINK_PROC_TITLE') ?: 'Привязать процедуру к врачу') . '</h3>';
        echo '<form method="post">';
        echo bitrix_sessid_post();
        echo '<input type="hidden" name="action" value="link_procedure">';

        echo '<select name="procedure_id">';
        foreach ($allProcedures as $p) {
            echo '<option value="' . (int)$p['ID'] . '">' . htmlspecialcharsbx($p['NAME']) . '</option>';
        }
        echo '</select> ';

        echo '<button type="submit">' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_LINK') ?: 'Привязать') . '</button>';
        echo '</form>';

        echo '<hr>';

        // --- Current doctor procedures list ---
        echo '<h2>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_PROCS_TITLE') ?: 'Процедуры') . '</h2>';

        if (!$procedureIds) {
            echo '<p>' . htmlspecialcharsbx(Loc::getMessage('OTUS_HW3_NO_PROCS') ?: 'Процедуры не назначены') . '</p>';
            return;
        }

        $procedures = ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['=IBLOCK_ID' => $proceduresIblockId, '@ID' => $procedureIds],
            'order' => ['NAME' => 'ASC'],
        ])->fetchAll();

        echo '<ul>';
        foreach ($procedures as $proc) {
            echo '<li>' . htmlspecialcharsbx($proc['NAME']) . '</li>';
        }
        echo '</ul>';
    }

    /**
     * Loads common OTUS lang file messages.
     *
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
     * Returns iblock ID by CODE.
     *
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

    /**
     * Returns property ID by iblock ID and property CODE.
     *
     * @param int $iblockId
     * @param string $code
     * @return int
     */
    private function getPropertyIdByCode(int $iblockId, string $code): int
    {
        $row = PropertyTable::getList([
            'select' => ['ID'],
            'filter' => ['=IBLOCK_ID' => $iblockId, '=CODE' => $code],
            'limit' => 1,
        ])->fetch();

        return $row ? (int)$row['ID'] : 0;
    }

    /**
     * Returns linked procedure IDs for a doctor.
     *
     * @param int $doctorId
     * @param int $propertyId
     * @return int[]
     */
    private function getProcedureIdsByDoctor(int $doctorId, int $propertyId): array
    {
        $rows = ElementPropertyTable::getList([
            'select' => ['VALUE'],
            'filter' => [
                '=IBLOCK_ELEMENT_ID' => $doctorId,
                '=IBLOCK_PROPERTY_ID' => $propertyId,
            ],
        ])->fetchAll();

        $ids = [];
        foreach ($rows as $row) {
            $val = (int)$row['VALUE'];
            if ($val > 0) {
                $ids[$val] = $val;
            }
        }

        return array_values($ids);
    }
}

$page = new Homework3ProceduresPage();
$page->run();

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
