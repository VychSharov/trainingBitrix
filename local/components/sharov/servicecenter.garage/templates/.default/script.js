BX.ready(function () {
    var addButton = document.getElementById('sharov-sc-add-car-btn');
    var saveButton = document.getElementById('sharov-sc-save-car-btn');
    var cancelButton = document.getElementById('sharov-sc-cancel-car-btn');
    var form = document.getElementById('sharov-sc-car-form');

    if (addButton && form) {
        addButton.addEventListener('click', function () {
            form.style.display = 'block';
            addButton.style.display = 'none';
        });
    }

    if (cancelButton && form && addButton) {
        cancelButton.addEventListener('click', function () {
            form.style.display = 'none';
            addButton.style.display = 'inline-block';
        });
    }

    if (saveButton && form) {
        saveButton.addEventListener('click', function () {
            var data = {
                sessid: BX.bitrix_sessid(),
                contactId: form.querySelector('[name="contactId"]').value,
                brand: form.querySelector('[name="brand"]').value,
                model: form.querySelector('[name="model"]').value,
                licensePlate: form.querySelector('[name="licensePlate"]').value,
                year: form.querySelector('[name="year"]').value,
                color: form.querySelector('[name="color"]').value,
                mileage: form.querySelector('[name="mileage"]').value,
                vin: form.querySelector('[name="vin"]').value
            };

            BX.ajax({
                url: '/local/tools/sharov_servicecenter_car_save.php',
                method: 'POST',
                dataType: 'json',
                data: data,
                onsuccess: function (response) {
                    if (!response || !response.success) {
                        alert(response && response.error ? response.error : 'Ошибка сохранения автомобиля');
                        return;
                    }

                    location.reload();
                },
                onfailure: function () {
                    alert('Ошибка ajax-запроса при сохранении автомобиля');
                }
            });
        });
    }

    document.querySelectorAll('.sharov-sc-car-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var carId = this.dataset.carId;

            BX.SidePanel.Instance.open('/local/tools/sharov_servicecenter_car_history.php?carId=' + carId, {
                width: 900,
                cacheable: false
            });
        });
    });
});