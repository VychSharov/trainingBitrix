<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.alerts']);
?>

<style>
    .sharov-sc-garage {
        padding: 16px 20px;
        font-family: Arial, sans-serif;
    }

    .sharov-sc-garage-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    .sharov-sc-garage-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
    }

    .sharov-sc-garage-hint {
        margin-bottom: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        background: #eef7ff;
        color: #3b6f9f;
        font-size: 13px;
    }

    .sharov-sc-car-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    }

    .sharov-sc-car-table th {
        background: #f3f6f8;
        text-align: left;
        padding: 12px;
        font-size: 13px;
        color: #59636e;
        border-bottom: 1px solid #e4e8eb;
    }

    .sharov-sc-car-table td {
        padding: 12px;
        border-bottom: 1px solid #eef1f3;
        font-size: 14px;
        color: #222;
    }

    .sharov-sc-car-row {
        cursor: pointer;
        transition: background .15s;
    }

    .sharov-sc-car-row:hover {
        background: #f5fbff;
    }

    .sharov-sc-car-main {
        font-weight: 600;
        color: #2067b0;
    }

    .sharov-sc-car-click-hint {
        display: inline-block;
        margin-top: 4px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #e8f4ff;
        color: #2067b0;
        font-size: 12px;
    }

    .sharov-sc-car-actions {
        white-space: nowrap;
    }

    .sharov-sc-link-btn {
        border: 0;
        background: transparent;
        color: #2067b0;
        cursor: pointer;
        padding: 4px 6px;
        font-size: 13px;
    }

    .sharov-sc-link-btn:hover {
        text-decoration: underline;
    }

    .sharov-sc-empty {
        margin-top: 10px;
    }

    .sharov-sc-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, .35);
    }

    .sharov-sc-modal {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: min(1000px, calc(100vw - 60px));
        max-height: calc(100vh - 80px);
        overflow: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 35px rgba(0, 0, 0, .25);
    }

    .sharov-sc-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 22px;
        border-bottom: 1px solid #e7ecef;
    }

    .sharov-sc-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #222;
    }

    .sharov-sc-modal-close {
        border: 0;
        background: transparent;
        font-size: 28px;
        cursor: pointer;
        color: #888;
    }

    .sharov-sc-modal-body {
        padding: 20px 22px;
    }

    .sharov-sc-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 14px;
    }

    .sharov-sc-form-field label {
        display: block;
        margin-bottom: 6px;
        color: #6b747d;
        font-size: 13px;
    }

    .sharov-sc-form-field input {
        width: 100%;
        min-height: 38px;
        box-sizing: border-box;
        border: 1px solid #cfd7df;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
    }

    .sharov-sc-form-actions {
        margin-top: 18px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .sharov-sc-history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sharov-sc-history-table th {
        background: #f3f6f8;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #e4e8eb;
        font-size: 13px;
        color: #59636e;
    }

    .sharov-sc-history-table td {
        padding: 10px;
        border-bottom: 1px solid #eef1f3;
        vertical-align: top;
        font-size: 14px;
    }

    .sharov-sc-history-parts {
        margin: 0;
        padding-left: 18px;
    }

    .sharov-sc-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        background: #e8f4ff;
        color: #2067b0;
        font-size: 12px;
    }

    .sharov-sc-error {
        color: #c0392b;
        background: #fff0f0;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 12px;
    }
</style>

<div class="sharov-sc-garage" data-contact-id="<?= (int)$arResult['CONTACT_ID'] ?>">
    <div class="sharov-sc-garage-header">
        <div>
            <div class="sharov-sc-garage-title">Гараж клиента</div>
            <div class="sharov-sc-garage-hint">
                Нажмите на строку автомобиля, чтобы открыть историю обращений.
            </div>
        </div>

        <button type="button" class="ui-btn ui-btn-success" id="sharov-sc-add-car-btn">
            Добавить автомобиль
        </button>
    </div>

    <div id="sharov-sc-garage-content">
        Загрузка автомобилей...
    </div>
</div>

<div class="sharov-sc-modal-overlay" id="sharov-sc-car-modal">
    <div class="sharov-sc-modal">
        <div class="sharov-sc-modal-head">
            <div class="sharov-sc-modal-title" id="sharov-sc-car-modal-title">Автомобиль</div>
            <button type="button" class="sharov-sc-modal-close" data-close-modal="sharov-sc-car-modal">×</button>
        </div>

        <div class="sharov-sc-modal-body">
            <input type="hidden" id="sharov-sc-car-id">

            <div class="sharov-sc-form-grid">
                <div class="sharov-sc-form-field">
                    <label>Марка *</label>
                    <input type="text" id="sharov-sc-car-brand">
                </div>

                <div class="sharov-sc-form-field">
                    <label>Модель *</label>
                    <input type="text" id="sharov-sc-car-model">
                </div>

                <div class="sharov-sc-form-field">
                    <label>Номер *</label>
                    <input type="text" id="sharov-sc-car-number">
                </div>

                <div class="sharov-sc-form-field">
                    <label>Год</label>
                    <input type="number" id="sharov-sc-car-year">
                </div>

                <div class="sharov-sc-form-field">
                    <label>Цвет</label>
                    <input type="text" id="sharov-sc-car-color">
                </div>

                <div class="sharov-sc-form-field">
                    <label>Пробег</label>
                    <input type="number" id="sharov-sc-car-mileage">
                </div>
            </div>

            <div class="sharov-sc-form-actions">
                <button type="button" class="ui-btn ui-btn-light-border" data-close-modal="sharov-sc-car-modal">
                    Отмена
                </button>

                <button type="button" class="ui-btn ui-btn-primary" id="sharov-sc-save-car-btn">
                    Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="sharov-sc-modal-overlay" id="sharov-sc-history-modal">
    <div class="sharov-sc-modal">
        <div class="sharov-sc-modal-head">
            <div class="sharov-sc-modal-title" id="sharov-sc-history-title">История обращений</div>
            <button type="button" class="sharov-sc-modal-close" data-close-modal="sharov-sc-history-modal">×</button>
        </div>

        <div class="sharov-sc-modal-body" id="sharov-sc-history-body">
            Загрузка...
        </div>
    </div>
</div>

<script>
BX.ready(function () {
    var root = document.querySelector('.sharov-sc-garage');

    if (!root) {
        return;
    }

    var contactId = parseInt(root.getAttribute('data-contact-id'), 10);
    var content = document.getElementById('sharov-sc-garage-content');
    var cars = [];

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function api(action, data, callback) {
        data = data || {};
        data.action = action;
        data.sessid = BX.bitrix_sessid();

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_garage_api.php',
            method: 'POST',
            dataType: 'json',
            data: data,
            onsuccess: function (response) {
                callback(response);
            },
            onfailure: function () {
                callback({
                    success: false,
                    error: 'Ошибка ajax-запроса'
                });
            }
        });
    }

    function openModal(id) {
        document.getElementById(id).style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function findCar(id) {
        id = parseInt(id, 10);

        for (var i = 0; i < cars.length; i++) {
            if (parseInt(cars[i].id, 10) === id) {
                return cars[i];
            }
        }

        return null;
    }

    function loadCars() {
        content.innerHTML = 'Загрузка автомобилей...';

        api('list', {
            contactId: contactId
        }, function (response) {
            if (!response || !response.success) {
                content.innerHTML = '<div class="sharov-sc-error">' + escapeHtml(response && response.error ? response.error : 'Ошибка загрузки автомобилей') + '</div>';
                return;
            }

            cars = response.cars || [];
            renderCars();
        });
    }

    function renderCars() {
        if (!cars.length) {
            content.innerHTML =
                '<div class="ui-alert ui-alert-info sharov-sc-empty">' +
                '<span class="ui-alert-message">У клиента пока нет автомобилей в гараже</span>' +
                '</div>';
            return;
        }

        var html = '';

        html += '<table class="sharov-sc-car-table">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>Автомобиль</th>';
        html += '<th>Номер</th>';
        html += '<th>Год</th>';
        html += '<th>Цвет</th>';
        html += '<th>Пробег</th>';
        html += '<th>Действия</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        cars.forEach(function (car) {
            html += '<tr class="sharov-sc-car-row" data-car-id="' + escapeHtml(car.id) + '">';
            html += '<td>';
            html += '<div class="sharov-sc-car-main">' + escapeHtml(car.brand + ' ' + car.model) + '</div>';
            html += '<span class="sharov-sc-car-click-hint">Открыть историю</span>';
            html += '</td>';
            html += '<td>' + escapeHtml(car.number) + '</td>';
            html += '<td>' + escapeHtml(car.year) + '</td>';
            html += '<td>' + escapeHtml(car.color) + '</td>';
            html += '<td>' + escapeHtml(car.mileage) + '</td>';
            html += '<td class="sharov-sc-car-actions">';
            html += '<button type="button" class="sharov-sc-link-btn" data-edit-car="' + escapeHtml(car.id) + '">Изменить</button>';
            html += '<button type="button" class="sharov-sc-link-btn" data-delete-car="' + escapeHtml(car.id) + '">Удалить</button>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody>';
        html += '</table>';

        content.innerHTML = html;
    }

    function clearCarForm() {
        document.getElementById('sharov-sc-car-id').value = '';
        document.getElementById('sharov-sc-car-brand').value = '';
        document.getElementById('sharov-sc-car-model').value = '';
        document.getElementById('sharov-sc-car-number').value = '';
        document.getElementById('sharov-sc-car-year').value = '';
        document.getElementById('sharov-sc-car-color').value = '';
        document.getElementById('sharov-sc-car-mileage').value = '';
    }

    function openAddCarForm() {
        clearCarForm();
        document.getElementById('sharov-sc-car-modal-title').innerText = 'Добавить автомобиль';
        openModal('sharov-sc-car-modal');
    }

    function openEditCarForm(carId) {
        var car = findCar(carId);

        if (!car) {
            alert('Автомобиль не найден');
            return;
        }

        document.getElementById('sharov-sc-car-id').value = car.id;
        document.getElementById('sharov-sc-car-brand').value = car.brand;
        document.getElementById('sharov-sc-car-model').value = car.model;
        document.getElementById('sharov-sc-car-number').value = car.number;
        document.getElementById('sharov-sc-car-year').value = car.year;
        document.getElementById('sharov-sc-car-color').value = car.color;
        document.getElementById('sharov-sc-car-mileage').value = car.mileage;

        document.getElementById('sharov-sc-car-modal-title').innerText = 'Изменить автомобиль';
        openModal('sharov-sc-car-modal');
    }

    function saveCar() {
        api('save', {
            id: document.getElementById('sharov-sc-car-id').value,
            contactId: contactId,
            brand: document.getElementById('sharov-sc-car-brand').value,
            model: document.getElementById('sharov-sc-car-model').value,
            number: document.getElementById('sharov-sc-car-number').value,
            year: document.getElementById('sharov-sc-car-year').value,
            color: document.getElementById('sharov-sc-car-color').value,
            mileage: document.getElementById('sharov-sc-car-mileage').value
        }, function (response) {
            if (!response || !response.success) {
                alert(response && response.error ? response.error : 'Ошибка сохранения автомобиля');
                return;
            }

            closeModal('sharov-sc-car-modal');
            loadCars();
        });
    }

    function deleteCar(carId) {
        var car = findCar(carId);

        if (!car) {
            alert('Автомобиль не найден');
            return;
        }

        if (!confirm('Удалить автомобиль "' + car.brand + ' ' + car.model + ' — ' + car.number + '"?')) {
            return;
        }

        api('delete', {
            id: carId
        }, function (response) {
            if (!response || !response.success) {
                alert(response && response.error ? response.error : 'Ошибка удаления автомобиля');
                return;
            }

            loadCars();
        });
    }

    function openHistory(carId) {
        document.getElementById('sharov-sc-history-title').innerText = 'История обращений';
        document.getElementById('sharov-sc-history-body').innerHTML = 'Загрузка...';
        openModal('sharov-sc-history-modal');

        api('history', {
            carId: carId,
            contactId: contactId
        }, function (response) {
            if (!response || !response.success) {
                document.getElementById('sharov-sc-history-body').innerHTML =
                    '<div class="sharov-sc-error">' + escapeHtml(response && response.error ? response.error : 'Ошибка загрузки истории') + '</div>';
                return;
            }

            document.getElementById('sharov-sc-history-title').innerText = response.title || 'История обращений';

            var deals = response.deals || [];

            if (!deals.length) {
                document.getElementById('sharov-sc-history-body').innerHTML =
                    '<div class="ui-alert ui-alert-info">' +
                    '<span class="ui-alert-message">По этому автомобилю пока нет обращений.</span>' +
                    '</div>';
                return;
            }

            var html = '';

            html += '<table class="sharov-sc-history-table">';
            html += '<thead>';
            html += '<tr>';
            html += '<th>Сделка</th>';
            html += '<th>Дата создания</th>';
            html += '<th>Стадия</th>';
            html += '<th>Ответственный</th>';
            html += '<th>Сумма</th>';
            html += '<th>Запчасти</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            deals.forEach(function (deal) {
                html += '<tr>';
                html += '<td><a href="' + escapeHtml(deal.url) + '" target="_blank">' + escapeHtml(deal.title) + '</a></td>';
                html += '<td>' + escapeHtml(deal.dateCreate) + '</td>';
                html += '<td><span class="sharov-sc-badge">' + escapeHtml(deal.stageName) + '</span></td>';

                if (deal.assigned && deal.assigned.url) {
                    html += '<td><a href="' + escapeHtml(deal.assigned.url) + '" target="_blank">' + escapeHtml(deal.assigned.label) + '</a></td>';
                } else {
                    html += '<td>' + escapeHtml(deal.assigned ? deal.assigned.label : 'Не указан') + '</td>';
                }

                html += '<td>' + escapeHtml(deal.sum) + '</td>';

                html += '<td>';

                if (deal.parts && deal.parts.length) {
                    html += '<ul class="sharov-sc-history-parts">';

                    deal.parts.forEach(function (part) {
                        html += '<li>' + escapeHtml(part.text) + '</li>';
                    });

                    html += '</ul>';
                } else {
                    html += '—';
                }

                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';

            document.getElementById('sharov-sc-history-body').innerHTML = html;
        });
    }

    document.getElementById('sharov-sc-add-car-btn').onclick = function () {
        openAddCarForm();
    };

    document.getElementById('sharov-sc-save-car-btn').onclick = function () {
        saveCar();
    };

    document.addEventListener('click', function (event) {
        var closeModalId = event.target.getAttribute('data-close-modal');

        if (closeModalId) {
            closeModal(closeModalId);
            return;
        }

        var editCarId = event.target.getAttribute('data-edit-car');

        if (editCarId) {
            event.stopPropagation();
            openEditCarForm(editCarId);
            return;
        }

        var deleteCarId = event.target.getAttribute('data-delete-car');

        if (deleteCarId) {
            event.stopPropagation();
            deleteCar(deleteCarId);
            return;
        }

        var row = event.target.closest('.sharov-sc-car-row');

        if (row) {
            openHistory(row.getAttribute('data-car-id'));
        }
    });

    loadCars();
});
</script>