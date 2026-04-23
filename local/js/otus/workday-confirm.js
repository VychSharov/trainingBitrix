(function (window, BX) {
    'use strict';

    if (!BX) {
        return;
    }

    var allowNativeClick = false;
    var confirmPopup = null;

    /**
     * Лог в консоль.
     *
     * @param {string} title
     * @param {*} [data]
     * @returns {void}
     */
    function log(title, data) {
        if (typeof console === 'undefined') {
            return;
        }

        if (typeof data === 'undefined') {
            console.log('[HW8]', title);
        } else {
            console.log('[HW8]', title, data);
        }
    }

    /**
     * Получить текст сообщения.
     *
     * @param {string} code
     * @param {string} fallback
     * @returns {string}
     */
    function getMessage(code, fallback) {
        var value = BX.message(code);

        return value ? value : fallback;
    }

    /**
     * Найти только родную кнопку timeman внутри popup.
     *
     * @param {EventTarget} target
     * @returns {HTMLElement|null}
     */
    function findNativeTimemanButton(target) {
        if (!target || !target.closest) {
            return null;
        }

        return target.closest(
            '#timeman_main .tm-popup-button-handler .ui-btn.ui-btn-success.ui-btn-icon-start'
        );
    }

    /**
     * Закрыть родное окно timeman.
     *
     * @returns {void}
     */
    function closeNativeTimemanPopup() {
        var timemanPopup = document.getElementById('timeman_main');

        if (!timemanPopup) {
            log('timeman popup not found for close');
            return;
        }

        var closeButton = timemanPopup.querySelector('.popup-window-close-icon');

        if (closeButton) {
            closeButton.click();
            log('native timeman popup closed');
        } else {
            log('native close button not found');
        }
    }

    /**
     * Показать своё окно подтверждения.
     *
     * @param {HTMLElement} nativeButton
     * @returns {void}
     */
    function showConfirmPopup(nativeButton) {
        if (confirmPopup) {
            confirmPopup.close();
            confirmPopup.destroy();
            confirmPopup = null;
        }

        confirmPopup = BX.PopupWindowManager.create('otus-hw8-workday-confirm', null, {
            autoHide: false,
            closeByEsc: true,
            closeIcon: true,
            overlay: true,
            titleBar: getMessage('OTUS_HW8_POPUP_TITLE', 'Начало рабочего дня'),
            content: BX.create('div', {
                props: {
                    className: 'otus-hw8-confirm-text'
                },
                text: getMessage('OTUS_HW8_POPUP_TEXT', 'Подтвердите начало рабочего дня')
            }),
            buttons: [
                new BX.PopupWindowButton({
                    text: getMessage('OTUS_HW8_START_BUTTON', 'Начать рабочий день'),
                    className: 'popup-window-button-accept',
                    events: {
                        click: function () {
                            log('confirm start click');

                            confirmPopup.close();

                            allowNativeClick = true;

                            setTimeout(function () {
                                try {
                                    nativeButton.click();
                                    log('native timeman button clicked');
                                } finally {
                                    setTimeout(function () {
                                        allowNativeClick = false;
                                    }, 50);
                                }
                            }, 0);
                        }
                    }
                }),
                new BX.PopupWindowButtonLink({
                    text: getMessage('OTUS_HW8_CANCEL_BUTTON', 'Отмена'),
                    className: 'popup-window-button-link-cancel',
                    events: {
                        click: function () {
                            log('confirm cancel click');
                            confirmPopup.close();
                            closeNativeTimemanPopup();
                        }
                    }
                })
            ],
            events: {
                onPopupClose: function () {
                    this.destroy();
                    confirmPopup = null;
                }
            }
        });

        confirmPopup.show();
        log('confirm popup shown');
    }

    /**
     * Глобальный перехват клика.
     *
     * @param {MouseEvent} event
     * @returns {void}
     */
    function handleDocumentClick(event) {
        var nativeButton = findNativeTimemanButton(event.target);

        if (!nativeButton) {
            return;
        }

        log('native timeman button intercepted', {
            text: nativeButton.textContent,
            className: nativeButton.className
        });

        if (allowNativeClick) {
            log('native click allowed');
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
        }

        showConfirmPopup(nativeButton);
    }

    BX.ready(function () {
        if (window.OtusWorkdayJsMessages) {
            BX.message(window.OtusWorkdayJsMessages);
        }

        log('script loaded');

        document.addEventListener('click', handleDocumentClick, true);

        BX.addCustomEvent('ontimemanwindowopen', function () {
            log('ontimemanwindowopen fired');
        });
    });
})(window, BX);