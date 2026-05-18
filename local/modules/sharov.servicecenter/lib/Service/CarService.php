<?php

namespace Sharov\ServiceCenter\Service;

use Bitrix\Main\Type\DateTime;
use Sharov\ServiceCenter\Model\CarTable;

class CarService
{
    /**
     * Возвращает автомобили контакта.
     *
     * @param int $contactId
     * @return array
     */
    public function getListByContactId(int $contactId): array
    {
        return CarTable::getList([
            'select' => [
                'ID',
                'CONTACT_ID',
                'BRAND',
                'MODEL',
                'LICENSE_PLATE',
                'YEAR',
                'COLOR',
                'MILEAGE',
                'VIN',
            ],
            'filter' => [
                '=CONTACT_ID' => $contactId,
            ],
            'order' => [
                'ID' => 'DESC',
            ],
        ])->fetchAll();
    }

    /**
     * Возвращает автомобиль по ID.
     *
     * @param int $carId
     * @return array|null
     */
    public function getById(int $carId): ?array
    {
        $car = CarTable::getByPrimary($carId)->fetch();

        return $car ?: null;
    }

    /**
     * Добавляет автомобиль.
     *
     * @param array $fields
     * @return int
     */
    public function add(array $fields): int
    {
        $fields['DATE_CREATE'] = new DateTime();

        $result = CarTable::add($fields);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return (int)$result->getId();
    }

    /**
     * Проверяет, принадлежит ли автомобиль контакту.
     *
     * @param int $carId
     * @param int $contactId
     * @return bool
     */
    public function validateOwnership(int $carId, int $contactId): bool
    {
        $car = $this->getById($carId);

        return $car && (int)$car['CONTACT_ID'] === $contactId;
    }
}