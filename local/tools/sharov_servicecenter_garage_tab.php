<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER;

if (!$USER || !$USER->IsAuthorized()) {
    die('Access denied');
}

$contactId = (int)($_REQUEST['contactId'] ?? $_REQUEST['entityId'] ?? $_REQUEST['ENTITY_ID'] ?? 0);

if ($contactId <= 0) {
    echo '<div class="sc-garage-error">Не указан контакт</div>';
    die();
}
?>

<style>
    .sc-garage-wrap {
        padding: 16px 20px;
        font-family: Arial, sans-serif;
    }

    .sc-garage-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    .sc-garage-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
    }

    .sc-garage-btn {
        border: 0;
        border-radius: 8px;
        padding: 10px 14px;
        background: #9dcf22;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .sc-garage-btn:hover {
        opacity: .9;
    }

    .sc-garage-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }

    .sc-garage-table th {
        background: #f3f6f8;
        text-align: left;
        padding: 12px;
        font-size: 13px;
        color: #59636e;
        border-bottom: 1px solid #e4e8eb;
    }

    .sc-garage-table td {
        padding: 12px;
        border-bottom: 1px solid #eef1f3;
        font-size: 14px;
        color: #222;
    }

    .sc-garage-row {
        cursor: pointer;
    }

    .sc-garage-row:hover {
        background: #f5fbff;
    }

    .sc-garage-car-main {
        font-weight: 600;
        color: #2067b0;
    }

    .sc-garage-actions {
        white-space: nowrap;
    }

    .sc-garage-link-btn {
        border: 0;
        background: transparent;
        color: #2067b0;
        cursor: pointer;
        padding: 4px 6px;
        font-size: 13px;
    }

    .sc-garage-link-btn:hover {
        text-decoration: underline;
    }

    .sc-garage-empty {
        padding: 18px;
        background: #f5f7f9;
        border-radius: 10px;
        color: #666;
    }

    .sc-modal-overlay {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,.35);
    }

    .sc-modal {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: min(1000px, calc(100vw - 60px));
        max-height: calc(100vh - 80px);
        overflow: auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 35px rgba(0,0,0,.25);
    }

    .sc-modal-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 22px;
        border-bottom: 1px solid #e7ecef;
    }

    .sc-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #222;
    }

    .sc-modal-close {
        border: 0;
        background: transparent;
        font-size: 28px;
        cursor: pointer;
        color: #888;
    }

    .sc-modal-body {
        padding: 20px 22px;
    }

    .sc-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 14px;
    }

    .sc-form-field label {
        display: block;
        margin-bottom: 6px;
        color: #6b747d;
        font-size: 13px;
    }

    .sc-form-field input {
        width: 100%;
        min-height: 38px;
        box-sizing: border-box;
        border: 1px solid #cfd7df;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
    }

    .sc-form-actions {
        margin-top: 18px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .sc-btn-secondary {
        background: #eef2f4;
        color: #333;
    }

    .sc-btn-danger {
        background: #e74c3c;
    }

    .sc-history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sc-history-table th {
        background: #f3f6f8;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #e4e8eb;
        font-size: 13px;
        color: #59636e;
    }

    .sc-history-table td {
        padding: 10px;
        border-bottom: 1px solid #eef1f3;
        vertical-align: top;
        font-size: 14px;
    }

    .sc-history-parts {
        margin: 0;
        padding-left: 18px;
    }

    .sc-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        background: #e8f4ff;
        color: #2067b0;
        font-size: 12px;
    }

    .sc-error {
        color: #c0392b;
        background: #fff0f0;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 12px;
    }
</style>

<div class="sc-garage-wrap" id="scGarageRoot" data-contact-id="<?= (int)$contactId ?>">
    <div class="sc-garage-header">
        <div class="sc-garage-title">Гараж клиента</div>
        <button type="button" class="sc-garage-btn" id="scAddCarBtn">Добавить автомобиль</button>
    </div>

    <div id="scGarageContent">
        Загрузка автомобилей...
    </div>
</div>

<div class="sc-modal-overlay" id="scCarModal">
    <div class="sc-modal">
        <div class="sc-modal-head">
            <div class="sc-modal-title" id="scCarModalTitle">Автомобиль</div>
            <button type="button" class="sc-modal-close" data-close-modal="scCarModal">×</button>
        </div>

        <div class="sc-modal-body">
            <input type="hidden" id="scCarId">

            <div class="sc-form-grid">
                <div class="sc-form-field">
                    <label>Марка</label>
                    <input type="text" id="scCarBrand">
                </div>

                <div class="sc-form-field">
                    <label>Модель</label>
                    <input type="text" id="scCarModel">
                </div>

                <div class="sc-form-field">
                    <label>Номер</label>
                    <input type="text" id="scCarNumber">
                </div>

                <div class="sc-form-field">
                    <label>Год</label>
                    <input type="number" id="scCarYear">
                </div>

                <div class="sc-form-field">
                    <label>Цвет</label>
                    <input type="text" id="scCarColor">
                </div>

                <div class="sc-form-field">
                    <label>Пробег</label>
                    <input type="number" id="scCarMileage">
                </div>
            </div>

            <div class="sc-form-actions">
                <button type="button" class="sc-garage-btn sc-btn-secondary" data-close-modal="scCarModal">Отмена</button>
                <button type="button" class="sc-garage-btn" id="scSaveCarBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="sc-modal-overlay" id="scHistoryModal">
    <div class="sc-modal">
        <div class="sc-modal-head">
            <div class="sc-modal-title" id="scHistoryTitle">История обращений</div>
            <button type="button" class="sc-modal-close" data-close-modal="scHistoryModal">×</button>
        </div>

        <div class="sc-modal-body" id="scHistoryBody">
            Загрузка...
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('scGarageRoot');
    var content = document.getElementById('scGarageContent');
    var contactId = root ? parseInt(root.getAttribute('data-contact-id'), 10) : 0;
    var cars = [];

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function request(action, data) {
        data = data || {};
        data.action = action;

        var formData = new FormData();

        Object.keys(data).forEach(function (key) {
            formData.append(key, data[key]);
        });

        return fetch('/local/tools/sharov_servicecenter_garage_api.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        });
    }

    function openModal(id) {
        document.getElementById(id).style.display = 'block';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function loadCars() {
        content.innerHTML = 'Загрузка автомобилей...';

        request('list', {
            contactId: contactId
        }).then(function (response) {
            if (!response || !response.success) {
                content.innerHTML = '<div class="sc-error">' + escapeHtml(response && response.error ? response.error : 'Ошибка загрузки') + '</div>';
                return;
            }

            cars = response.cars || [];
            renderCars();
        });
    }

    function renderCars() {
        if (!cars.length) {
            content.innerHTML = '<div class="sc-garage-empty">У клиента пока нет автомобилей в гараже.</div>';
            return;
        }

        var html = '';

        html += '<table class="sc-garage-table">';
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
            html += '<tr class="sc-garage-row" data-car-id="' + car.id + '">';
            html += '<td><span class="sc-garage-car-main">' + escapeHtml(car.brand + ' ' + car.model) + '</span><br><span class="sc-badge">Нажмите, чтобы открыть историю</span></td>';
            html += '<td>' + escapeHtml(car.number) + '</td>';
            html += '<td>' + escapeHtml(car.year) + '</td>';
            html += '<td>' + escapeHtml(car.color) + '</td>';
            html += '<td>' + escapeHtml(car.mileage) + '</td>';
            html += '<td class="sc-garage-actions">';
            html += '<button type="button" class="sc-garage-link-btn" data-edit-car="' + car.id + '">Изменить</button>';
            html += '<button type="button" class="sc-garage-link-btn" data-delete-car="' + car.id + '">Удалить</button>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</tbody>';
        html += '</table>';

        content.innerHTML = html;
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

    function clearForm() {
        document.getElementById('scCarId').value = '';
        document.getElementById('scCarBrand').value = '';
        document.getElementById('scCarModel').value = '';
        document.getElementById('scCarNumber').value = '';
        document.getElementById('scCarYear').value = '';
        document.getElementById('scCarColor').value = '';
        document.getElementById('scCarMileage').value = '';
    }

    function openAddForm() {
        clearForm();
        document.getElementById('scCarModalTitle').innerText = 'Добавить автомобиль';
        openModal('scCarModal');
    }

    function openEditForm(carId) {
        var car = findCar(carId);

        if (!car) {
            alert('Автомобиль не найден');
            return;
        }

        document.getElementById('scCarId').value = car.id;
        document.getElementById('scCarBrand').value = car.brand;
        document.getElementById('scCarModel').value = car.model;
        document.getElementById('scCarNumber').value = car.number;
        document.getElementById('scCarYear').value = car.year;
        document.getElementById('scCarColor').value = car.color;
        document.getElementById('scCarMileage').value = car.mileage;

        document.getElementById('scCarModalTitle').innerText = 'Изменить автомобиль';
        openModal('scCarModal');
    }

    function saveCar() {
        request('save', {
            id: document.getElementById('scCarId').value,
            contactId: contactId,
            brand: document.getElementById('scCarBrand').value,
            model: document.getElementById('scCarModel').value,
            number: document.getElementById('scCarNumber').value,
            year: document.getElementById('scCarYear').value,
            color: document.getElementById('scCarColor').value,
            mileage: document.getElementById('scCarMileage').value
        }).then(function (response) {
            if (!response || !response.success) {
                alert(response && response.error ? response.error : 'Ошибка сохранения');
                return;
            }

            closeModal('scCarModal');
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

        request('delete', {
            id: carId
        }).then(function (response) {
            if (!response || !response.success) {
                alert(response && response.error ? response.error : 'Ошибка удаления');
                return;
            }

            loadCars();
        });
    }

    function openHistory(carId) {
        document.getElementById('scHistoryTitle').innerText = 'История обращений';
        document.getElementById('scHistoryBody').innerHTML = 'Загрузка...';
        openModal('scHistoryModal');

        request('history', {
            carId: carId,
            contactId: contactId
        }).then(function (response) {
            if (!response || !response.success) {
                document.getElementById('scHistoryBody').innerHTML =
                    '<div class="sc-error">' + escapeHtml(response && response.error ? response.error : 'Ошибка загрузки истории') + '</div>';
                return;
            }

            document.getElementById('scHistoryTitle').innerText = response.title || 'История обращений';

            var deals = response.deals || [];

            if (!deals.length) {
                document.getElementById('scHistoryBody').innerHTML =
                    '<div class="sc-garage-empty">По этому автомобилю пока нет обращений.</div>';
                return;
            }

            var html = '';

            html += '<table class="sc-history-table">';
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
                html += '<td><span class="sc-badge">' + escapeHtml(deal.stageName) + '</span></td>';

                if (deal.assigned && deal.assigned.url) {
                    html += '<td><a href="' + escapeHtml(deal.assigned.url) + '" target="_blank">' + escapeHtml(deal.assigned.label) + '</a></td>';
                } else {
                    html += '<td>' + escapeHtml(deal.assigned ? deal.assigned.label : 'Не указан') + '</td>';
                }

                html += '<td>' + escapeHtml(deal.sum) + '</td>';

                html += '<td>';

                if (deal.parts && deal.parts.length) {
                    html += '<ul class="sc-history-parts">';

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

            document.getElementById('scHistoryBody').innerHTML = html;
        });
    }

    document.getElementById('scAddCarBtn').addEventListener('click', function () {
        openAddForm();
    });

    document.getElementById('scSaveCarBtn').addEventListener('click', function () {
        saveCar();
    });

    document.addEventListener('click', function (event) {
        var closeId = event.target.getAttribute('data-close-modal');

        if (closeId) {
            closeModal(closeId);
            return;
        }

        var editCarId = event.target.getAttribute('data-edit-car');

        if (editCarId) {
            event.stopPropagation();
            openEditForm(editCarId);
            return;
        }

        var deleteCarId = event.target.getAttribute('data-delete-car');

        if (deleteCarId) {
            event.stopPropagation();
            deleteCar(deleteCarId);
            return;
        }

        var row = event.target.closest('.sc-garage-row');

        if (row) {
            openHistory(row.getAttribute('data-car-id'));
        }
    });

    loadCars();
})();
</script>