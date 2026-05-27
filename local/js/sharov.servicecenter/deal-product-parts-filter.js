(function () {
    var allowedNames = [];
    var loaded = false;

    BX.ready(function () {
        if (!isDealPage()) {
            return;
        }

        loadAllowedParts(function () {
            startObserver();
            filterVisiblePopups();
            console.log('[servicecenter] deal product parts filter loaded', allowedNames);
        });
    });

    function isDealPage() {
        return /\/crm\/deal\/details\/\d+\//i.test(window.location.pathname)
            || /\/crm\/deal\/details\/\d+\//i.test(window.location.href);
    }

    function loadAllowedParts(callback) {
        BX.ajax({
            url: '/local/tools/sharov_servicecenter_parts_list.php',
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                if (!response || !response.success || !response.parts) {
                    console.warn('[servicecenter] parts list error', response);
                    callback();
                    return;
                }

                allowedNames = response.parts
                    .map(function (part) {
                        return normalize(part.name);
                    })
                    .filter(function (name) {
                        return name.length > 0;
                    });

                loaded = true;
                callback();
            },
            onfailure: function () {
                console.warn('[servicecenter] parts list ajax failed');
                callback();
            }
        });
    }

    function startObserver() {
        var observer = new MutationObserver(function () {
            filterVisiblePopups();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        document.addEventListener('keyup', function () {
            setTimeout(filterVisiblePopups, 100);
        });

        document.addEventListener('click', function () {
            setTimeout(filterVisiblePopups, 100);
            setTimeout(filterVisiblePopups, 400);
        });
    }

    function filterVisiblePopups() {
        if (!loaded || !allowedNames.length) {
            return;
        }

        var popups = document.querySelectorAll(
            '.popup-window, .menu-popup, .main-ui-selector-popup, .catalog-product-selector-popup'
        );

        for (var i = 0; i < popups.length; i++) {
            filterPopup(popups[i]);
        }
    }

    function filterPopup(popup) {
        if (!popup || !popup.offsetParent) {
            return;
        }

        var items = popup.querySelectorAll(
            '.menu-popup-item, .main-ui-selector-item, .catalog-product-selector-popup-item, [class*="product"], [class*="selector-item"]'
        );

        for (var i = 0; i < items.length; i++) {
            filterItem(items[i]);
        }
    }

    function filterItem(item) {
        if (!item || item.querySelector('input, textarea, select')) {
            return;
        }

        var text = normalize(item.innerText || item.textContent || '');

        if (!text) {
            return;
        }

        /*
         * Не трогаем служебные строки.
         */
        if (
            text === 'товары'
            || text === 'услуги'
            || text.indexOf('создать') !== -1
            || text.indexOf('найти') !== -1
            || text.indexOf('загрузка') !== -1
            || text.indexOf('ничего не найдено') !== -1
        ) {
            return;
        }

        /*
         * Признаки строки товара в стандартном селекторе Битрикса:
         * цена в рублях, либо слово "остаток", либо товарное название.
         */
        var looksLikeProduct =
            text.indexOf('₽') !== -1
            || text.indexOf('руб') !== -1
            || text.indexOf('остаток') !== -1
            || item.className.toString().toLowerCase().indexOf('product') !== -1;

        if (!looksLikeProduct) {
            return;
        }

        var allowed = allowedNames.some(function (name) {
            return text.indexOf(name) !== -1;
        });

        if (allowed) {
            item.style.display = '';
            item.setAttribute('data-sharov-sc-allowed-part', 'Y');
        } else {
            item.style.display = 'none';
            item.setAttribute('data-sharov-sc-hidden-not-part', 'Y');
        }
    }

    function normalize(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }
})();