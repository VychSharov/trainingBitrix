(function (window, BX) {
    'use strict';

    if (!BX) {
        return;
    }

    function applyMessages() {
        if (window.OtusBookingJsMessages) {
            BX.message(window.OtusBookingJsMessages);
        }
    }

    function handleButtonClick(event) {
        var button = event.target.closest('.otus-booking-btn');
        if (!button) {
            return;
        }

        event.preventDefault();

        window.OtusDoctorBooking.showPopup({
            doctorId: button.dataset.doctorId,
            procedureId: button.dataset.procedureId,
            procedureName: button.dataset.procedureName
        });
    }

    window.OtusDoctorBooking = {
        globalBound: false,

        bindGlobal: function () {
            applyMessages();

            if (this.globalBound) {
                return;
            }

            this.globalBound = true;
            document.addEventListener('click', handleButtonClick);
        },

        showPopup: function (data) {
            applyMessages();

            var popupId = 'otus-booking-popup-' + data.doctorId + '-' + data.procedureId;

            var content = BX.create('div', {
                props: {className: 'otus-booking-popup-wrap'},
                html:
                    '<div style="padding:16px; min-width:380px;">' +
                        '<div style="margin-bottom:14px;"><strong>' + BX.util.htmlspecialchars(data.procedureName) + '</strong></div>' +
                        '<div style="margin-bottom:12px;">' +
                            '<label>' + BX.message('OTUS_HW7_PATIENT_LABEL') + '</label><br>' +
                            '<input type="text" class="adm-input otus-booking-patient" style="width:100%;">' +
                        '</div>' +
                        '<div style="margin-bottom:12px;">' +
                            '<label>' + BX.message('OTUS_HW7_TIME_LABEL') + '</label><br>' +
                            '<input type="datetime-local" class="adm-input otus-booking-time" style="width:100%;">' +
                        '</div>' +
                        '<div class="otus-booking-error" style="display:none;color:#c00;"></div>' +
                    '</div>'
            });

            var popup = BX.PopupWindowManager.create(popupId, null, {
                content: content,
                autoHide: true,
                closeIcon: true,
                titleBar: BX.message('OTUS_HW7_POPUP_TITLE'),
                draggable: true,
                overlay: true,
                buttons: [
                    new BX.PopupWindowButton({
                        text: BX.message('OTUS_HW7_CREATE_BUTTON'),
                        className: 'popup-window-button-accept',
                        events: {
                            click: BX.proxy(function () {
                                this.saveBooking(popup, data);
                            }, this)
                        }
                    }),
                    new BX.PopupWindowButtonLink({
                        text: BX.message('OTUS_HW7_CANCEL_BUTTON'),
                        className: 'popup-window-button-link-cancel',
                        events: {
                            click: function () {
                                popup.close();
                            }
                        }
                    })
                ]
            });

            popup.show();
        },

        saveBooking: function (popup, data) {
            var container = popup.contentContainer;
            var patientNode = container.querySelector('.otus-booking-patient');
            var timeNode = container.querySelector('.otus-booking-time');
            var errorNode = container.querySelector('.otus-booking-error');

            var patient = patientNode.value.trim();
            var bookingTime = timeNode.value.trim();

            errorNode.style.display = 'none';
            errorNode.textContent = '';

            if (!patient || !bookingTime) {
                errorNode.textContent = BX.message('OTUS_HW7_REQUIRED_FIELDS');
                errorNode.style.display = 'block';
                return;
            }

            BX.ajax({
                url: '/local/tools/otus_booking_ajax.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    sessid: BX.bitrix_sessid(),
                    action: 'create_booking',
                    doctor_id: data.doctorId,
                    procedure_id: data.procedureId,
                    patient_fio: patient,
                    booking_time: bookingTime
                },
                onsuccess: function (response) {
                    if (!response || response.status !== 'success') {
                        errorNode.textContent = response && response.message
                            ? response.message
                            : BX.message('OTUS_HW7_UNKNOWN_ERROR');
                        errorNode.style.display = 'block';
                        return;
                    }

                    popup.close();
                    window.location.reload();
                },
                onfailure: function () {
                    errorNode.textContent = BX.message('OTUS_HW7_UNKNOWN_ERROR');
                    errorNode.style.display = 'block';
                }
            });
        }
    };

    BX.ready(function () {
        window.OtusDoctorBooking.bindGlobal();
    });
})(window, BX);