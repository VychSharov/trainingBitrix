# Архитектура

## Сущности

### Автомобиль

Таблица: `b_sharov_sc_car`

Поля:

- ID
- CONTACT_ID
- BRAND
- MODEL
- LICENSE_PLATE
- YEAR
- COLOR
- MILEAGE
- VIN
- DATE_CREATE
- DATE_UPDATE

### Заказ-наряд

Стандартная сделка CRM в направлении "Сервисное обслуживание".

Связь с автомобилем:

- `UF_CRM_SC_CAR_ID`

### Заявка на закупку

Смарт-процесс "Заявка на закупку".

## Основные классы

- `CarTable` — ORM-модель автомобилей.
- `CarService` — бизнес-логика гаража.
- `ContactTabHandler` — вкладка "Гараж".
- `DealEventHandler` — запрет дублей сделок.
- `StockSyncAgent` — ежедневная синхронизация остатков.
- `PurchaseRequestService` — логика закупок.