<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require __DIR__ . "/lang/ru/rulang.php";

/**
 * Class OtusCurrenciesComponent
 */
class OtusCurrenciesComponent extends CBitrixComponent
{
    /**
     * Подготовка параметров компонента
     *
     * @param array $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams)
    {
        $arParams["CURRENCY"] = isset($arParams["CURRENCY"]) ? trim((string)$arParams["CURRENCY"]) : "";
        if ($arParams["CURRENCY"] === "")
        {
            $arParams["CURRENCY"] = "USD";
        }

        return $arParams;
    }

    /**
     * Получить валюту из справочника /bitrix/admin/currencies.php
     * Курс по умолчанию = AMOUNT
     * Номинал = AMOUNT_CNT
     *
     * @param string $currencyCode
     * @return array|null
     */
    protected function getCurrency($currencyCode)
    {
        $row = CCurrency::GetByID($currencyCode);
        if (!$row)
        {
            return null;
        }

        return $row;
    }

    /**
     * Точка входа в компонент
     *
     * @return void
     */
    public function executeComponent()
    {
        try
        {
            if (!CModule::IncludeModule("currency"))
            {
                throw new Exception(GetMessage("OTUS_CURRENCIES_ERR_NO_MODULE"));
            }

            if ($this->StartResultCache())
            {
                $currencyCode = $this->arParams["CURRENCY"];
                $currency = $this->getCurrency($currencyCode);

                $this->arResult = array(
                    "CURRENCY" => $currencyCode,
                    "CURRENCY_DATA" => $currency,
                );

                $this->IncludeComponentTemplate();
            }
        }
        catch (Exception $e)
        {
            ShowError($e->getMessage());
        }
    }
}