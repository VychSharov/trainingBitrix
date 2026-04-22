<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

class OtusDoctorBookingProperty
{
    const USER_TYPE = 'OTUS_DOCTOR_BOOKING';
    const DESCRIPTION = 'Запись на процедуру';
    const DOCTORS_IBLOCK_CODE = 'doctors';
    const PROCEDURES_IBLOCK_CODE = 'procedures';
    const LINK_PROP_CODE = 'PROTSEDURA_ID';

    /**
     * Описание пользовательского типа свойства.
     *
     * @return array
     */
    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('OTUS_HW7_PROPERTY_DESCRIPTION') ?: self::DESCRIPTION,
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
            'ConvertToDB' => array(__CLASS__, 'ConvertToDB'),
        );
    }

    /**
     * Рендер свойства в карточке врача.
     *
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        if (!Loader::includeModule('iblock')) {
            return '<span style="color:#c00;">' . htmlspecialcharsbx(
                Loc::getMessage('OTUS_HW7_ERR_IBLOCK') ?: 'Модуль iblock не подключен'
            ) . '</span>';
        }

        \CJSCore::Init(array('popup'));
        Extension::load('otus.booking-selector');

        $doctorId = isset($_REQUEST['ID']) ? (int)$_REQUEST['ID'] : 0;
        $inputName = htmlspecialcharsbx(isset($strHTMLControlName['VALUE']) ? $strHTMLControlName['VALUE'] : '');

        $html = '<input type="hidden" name="' . $inputName . '" value="">';

        if ($doctorId <= 0) {
            $html .= '<div style="color:#666;">' . htmlspecialcharsbx(
                Loc::getMessage('OTUS_HW7_SAVE_DOCTOR_FIRST') ?: 'Сначала сохраните врача, затем можно создавать бронирование'
            ) . '</div>';

            $html .= self::renderMessagesScript();

            return $html;
        }

        $procedures = self::getDoctorProcedures($doctorId);

        if (empty($procedures)) {
            $html .= '<div style="color:#666;">' . htmlspecialcharsbx(
                Loc::getMessage('OTUS_HW7_NO_PROCEDURES') ?: 'У врача нет связанных процедур'
            ) . '</div>';

            $html .= self::renderMessagesScript();

            return $html;
        }

        $html .= '<div class="otus-booking-widget" data-doctor-id="' . (int)$doctorId . '">';

        foreach ($procedures as $procedure) {
            $html .=
                '<button ' .
                'type="button" ' .
                'class="ui-btn ui-btn-light-border otus-booking-btn" ' .
                'data-doctor-id="' . (int)$doctorId . '" ' .
                'data-procedure-id="' . (int)$procedure['ID'] . '" ' .
                'data-procedure-name="' . htmlspecialcharsbx($procedure['NAME']) . '" ' .
                'style="margin:0 10px 10px 0;">' .
                htmlspecialcharsbx($procedure['NAME']) .
                '</button>';
        }

        $html .= '</div>';
        $html .= self::renderMessagesScript();

        return $html;
    }

    /**
     * Сохранение значения свойства в БД.
     *
     * @param array $arProperty
     * @param array $value
     * @return array
     */
    public static function ConvertToDB($arProperty, $value)
    {
        return array(
            'VALUE' => '',
            'DESCRIPTION' => '',
        );
    }

    /**
     * Подключает JS-сообщения.
     *
     * @return string
     */
    protected static function renderMessagesScript()
    {
        static $alreadyPrinted = false;

        if ($alreadyPrinted) {
            return '';
        }

        $alreadyPrinted = true;

        $messages = \CUtil::PhpToJSObject(array(
            'OTUS_HW7_POPUP_TITLE' => Loc::getMessage('OTUS_HW7_POPUP_TITLE') ?: 'Создание бронирования',
            'OTUS_HW7_PATIENT_LABEL' => Loc::getMessage('OTUS_HW7_PATIENT_LABEL') ?: 'ФИО пациента',
            'OTUS_HW7_TIME_LABEL' => Loc::getMessage('OTUS_HW7_TIME_LABEL') ?: 'Время записи',
            'OTUS_HW7_CREATE_BUTTON' => Loc::getMessage('OTUS_HW7_CREATE_BUTTON') ?: 'Создать',
            'OTUS_HW7_CANCEL_BUTTON' => Loc::getMessage('OTUS_HW7_CANCEL_BUTTON') ?: 'Отмена',
            'OTUS_HW7_REQUIRED_FIELDS' => Loc::getMessage('OTUS_HW7_REQUIRED_FIELDS') ?: 'Заполните все поля',
            'OTUS_HW7_UNKNOWN_ERROR' => Loc::getMessage('OTUS_HW7_UNKNOWN_ERROR') ?: 'Неизвестная ошибка',
        ));

        return '<script>
            BX.message(' . $messages . ');
            BX.ready(function () {
                if (window.OtusDoctorBooking) {
                    window.OtusDoctorBooking.bindGlobal();
                }
            });
        </script>';
    }

    /**
     * Возвращает процедуры врача.
     *
     * @param int $doctorId
     * @return array
     */
    protected static function getDoctorProcedures($doctorId)
    {
        $doctorId = (int)$doctorId;
        if ($doctorId <= 0) {
            return array();
        }

        $doctorsIblockId = self::getIblockIdByCode(self::DOCTORS_IBLOCK_CODE);
        $proceduresIblockId = self::getIblockIdByCode(self::PROCEDURES_IBLOCK_CODE);

        if ($doctorsIblockId <= 0 || $proceduresIblockId <= 0) {
            return array();
        }

        $procedureIds = array();

        $propertyRes = CIBlockElement::GetProperty(
            $doctorsIblockId,
            $doctorId,
            array('sort' => 'asc'),
            array('CODE' => self::LINK_PROP_CODE)
        );

        while ($property = $propertyRes->Fetch()) {
            $procedureId = (int)$property['VALUE'];
            if ($procedureId > 0) {
                $procedureIds[$procedureId] = $procedureId;
            }
        }

        if (empty($procedureIds)) {
            return array();
        }

        $result = array();
        $elementsRes = CIBlockElement::GetList(
            array('NAME' => 'ASC'),
            array(
                'IBLOCK_ID' => $proceduresIblockId,
                'ID' => array_values($procedureIds),
                'ACTIVE' => 'Y',
            ),
            false,
            false,
            array('ID', 'NAME')
        );

        while ($element = $elementsRes->Fetch()) {
            $result[] = $element;
        }

        return $result;
    }

    /**
     * Возвращает ID инфоблока по символьному коду.
     *
     * @param string $code
     * @return int
     */
    protected static function getIblockIdByCode($code)
    {
        $iblockRes = CIBlock::GetList(
            array(),
            array(
                'CODE' => $code,
                'CHECK_PERMISSIONS' => 'N',
            )
        );

        if ($iblock = $iblockRes->Fetch()) {
            return (int)$iblock['ID'];
        }

        return 0;
    }
}