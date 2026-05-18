(function () {
    if (window.__SHAROV_SERVICECENTER_DEAL_SELECTS_LOADED) {
        return;
    }

    window.__SHAROV_SERVICECENTER_DEAL_SELECTS_LOADED = true;

    var CAR_FIELD = 'UF_CRM_SC_CAR_ID';
    var MECHANIC_FIELD = 'UF_CRM_SC_MECHANIC_ID';

    var CAR_SELECT_ID = 'sharov-sc-car-select';
    var MECHANIC_SELECT_ID = 'sharov-sc-mechanic-select';

    var lastContactId = null;
    var dealContactRequestInProgress = false;

    var readonlyLabelsRequestInProgress = false;
    var lastReadonlyLabelsResponse = null;
    var lastReadonlyLabelsRequestTime = 0;

    function log(message, data) {
        if (window.console) {
            console.log('[servicecenter]', message, data || '');
        }
    }

    function getDealIdFromUrl() {
        if (window.SHAROV_SERVICECENTER_DEAL_ID) {
            return parseInt(window.SHAROV_SERVICECENTER_DEAL_ID, 10);
        }

        var sources = [
            window.location.href,
            window.location.pathname,
            window.location.search,
            document.referrer,
            document.body ? document.body.innerHTML : ''
        ];

        for (var i = 0; i < sources.length; i++) {
            var source = sources[i] || '';

            var patterns = [
                /\/crm\/deal\/details\/(\d+)\//i,
                /\/crm\/deal\/edit\/(\d+)\//i,
                /dealId=(\d+)/i,
                /deal_id=(\d+)/i,
                /DEAL_ID=(\d+)/i,
                /entityId=(\d+)/i,
                /entity_id=(\d+)/i,
                /ENTITY_ID=(\d+)/i,
                /"dealId"\s*:\s*"?(\d+)"?/i,
                /"deal_id"\s*:\s*"?(\d+)"?/i,
                /"DEAL_ID"\s*:\s*"?(\d+)"?/i,
                /"entityId"\s*:\s*"?(\d+)"?/i,
                /"ENTITY_ID"\s*:\s*"?(\d+)"?/i,
                /ENTITY_TYPE_ID["'\s:=]+2[^0-9]+ENTITY_ID["'\s:=]+(\d+)/i,
                /ENTITY_ID["'\s:=]+(\d+)[^0-9]+ENTITY_TYPE_ID["'\s:=]+2/i
            ];

            for (var j = 0; j < patterns.length; j++) {
                var match = source.match(patterns[j]);

                if (match && match[1]) {
                    var id = parseInt(match[1], 10);

                    if (id > 0) {
                        return id;
                    }
                }
            }
        }

        return 0;
    }

    function extractContactIdFromText(text) {
        if (!text) {
            return 0;
        }

        var patterns = [
            /\/crm\/contact\/details\/(\d+)\//i,
            /CONTACT_ID["'\s:=]+(\d+)/i,
            /"CONTACT_ID"\s*:\s*"?(\d+)"?/i,
            /CONTACT[_:\- ]+(\d+)/i,
            /"ENTITY_TYPE_ID"\s*:\s*3\s*,\s*"ENTITY_ID"\s*:\s*"?(\d+)"?/i,
            /"ENTITY_ID"\s*:\s*"?(\d+)"?\s*,\s*"ENTITY_TYPE_ID"\s*:\s*3/i,
            /data-entity-type-id=["']3["'][^>]+data-entity-id=["'](\d+)["']/i,
            /data-entity-id=["'](\d+)["'][^>]+data-entity-type-id=["']3["']/i
        ];

        for (var i = 0; i < patterns.length; i++) {
            var match = text.match(patterns[i]);

            if (match && match[1]) {
                return parseInt(match[1], 10);
            }
        }

        return 0;
    }

    function findClientBlock() {
        var blocks = document.querySelectorAll(
            '.ui-entity-editor-content-block,' +
            '.ui-entity-editor-field-container,' +
            '.ui-entity-editor-block'
        );

        for (var i = 0; i < blocks.length; i++) {
            var text = (blocks[i].innerText || blocks[i].textContent || '').trim();

            if (text.indexOf('Клиент') !== -1 && text.indexOf('Контакт') !== -1) {
                return blocks[i];
            }
        }

        return null;
    }

    function findContactIdInDom() {
        var contactLinks = document.querySelectorAll('a[href*="/crm/contact/details/"]');

        for (var i = 0; i < contactLinks.length; i++) {
            var href = contactLinks[i].getAttribute('href') || '';
            var match = href.match(/\/crm\/contact\/details\/(\d+)\//);

            if (match && match[1]) {
                return parseInt(match[1], 10);
            }
        }

        var entityNodes = document.querySelectorAll(
            '[data-entity-id],' +
            '[data-entity-type-id],' +
            '[data-entity-type-name],' +
            '[data-id]'
        );

        for (var j = 0; j < entityNodes.length; j++) {
            var node = entityNodes[j];

            var entityTypeId = node.getAttribute('data-entity-type-id') || '';
            var entityTypeName = node.getAttribute('data-entity-type-name') || '';
            var entityId = node.getAttribute('data-entity-id') || node.getAttribute('data-id') || '';

            if (
                (entityTypeId === '3' || entityTypeName.toUpperCase() === 'CONTACT')
                && /^\d+$/.test(entityId)
            ) {
                return parseInt(entityId, 10);
            }

            var parsedFromOuterHtml = extractContactIdFromText(node.outerHTML || '');

            if (parsedFromOuterHtml > 0) {
                return parsedFromOuterHtml;
            }
        }

        var clientBlock = findClientBlock();

        if (clientBlock) {
            var parsedFromClientBlock = extractContactIdFromText(
                clientBlock.outerHTML || clientBlock.innerHTML || ''
            );

            if (parsedFromClientBlock > 0) {
                return parsedFromClientBlock;
            }
        }

        var inputs = document.querySelectorAll('input, textarea');

        for (var k = 0; k < inputs.length; k++) {
            var name = inputs[k].getAttribute('name') || '';
            var id = inputs[k].getAttribute('id') || '';
            var value = inputs[k].value || '';

            if (
                (name.indexOf('CONTACT') !== -1 || id.indexOf('CONTACT') !== -1)
                && /^\d+$/.test(value)
            ) {
                return parseInt(value, 10);
            }

            var parsedFromInput = extractContactIdFromText(value);

            if (parsedFromInput > 0) {
                return parsedFromInput;
            }
        }

        var parsedFromHtml = extractContactIdFromText(document.body.innerHTML || '');

        if (parsedFromHtml > 0) {
            return parsedFromHtml;
        }

        return 0;
    }

    function getContactId(callback) {
        var contactId = findContactIdInDom();

        if (contactId > 0) {
            callback(contactId);
            return;
        }

        var dealId = getDealIdFromUrl();

        if (dealId <= 0) {
            callback(0);
            return;
        }

        if (dealContactRequestInProgress) {
            callback(lastContactId || 0);
            return;
        }

        dealContactRequestInProgress = true;

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_deal_client.php?dealId=' + encodeURIComponent(dealId),
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                dealContactRequestInProgress = false;

                if (response && response.success && response.contactId > 0) {
                    lastContactId = parseInt(response.contactId, 10);
                    callback(lastContactId);
                    return;
                }

                callback(0);
            },
            onfailure: function () {
                dealContactRequestInProgress = false;
                callback(0);
            }
        });
    }

    function findInputByFieldName(fieldName, labelText) {
        var input = document.querySelector('input[name="' + fieldName + '"]');

        if (input) {
            return input;
        }

        var container = document.querySelector(
            '[data-cid="' + fieldName + '"], [data-id="' + fieldName + '"]'
        );

        if (container) {
            input = container.querySelector('input');

            if (input) {
                return input;
            }
        }

        var labels = document.querySelectorAll(
            '.ui-entity-editor-block-title-text,' +
            '.ui-entity-editor-content-block-title-text,' +
            '.ui-entity-editor-field-title,' +
            'label'
        );

        for (var i = 0; i < labels.length; i++) {
            var text = (labels[i].innerText || labels[i].textContent || '').trim();

            if (text.indexOf(labelText) !== -1) {
                var block = labels[i].closest(
                    '.ui-entity-editor-content-block,' +
                    '.ui-entity-editor-field-container,' +
                    '.ui-entity-editor-block,' +
                    'div'
                );

                if (block) {
                    input = block.querySelector('input');

                    if (input) {
                        return input;
                    }
                }
            }
        }

        return null;
    }

    function setInputValue(input, value) {
        input.value = value;
        input.setAttribute('value', value);

        var inputEvent = document.createEvent('HTMLEvents');
        inputEvent.initEvent('input', true, false);
        input.dispatchEvent(inputEvent);

        var changeEvent = document.createEvent('HTMLEvents');
        changeEvent.initEvent('change', true, false);
        input.dispatchEvent(changeEvent);

        if (window.BX) {
            BX.fireEvent(input, 'input');
            BX.fireEvent(input, 'change');
        }
    }

    function createSelectAfterInput(input, selectId) {
        var select = document.getElementById(selectId);

        if (select) {
            return select;
        }

        select = document.createElement('select');
        select.id = selectId;
        select.style.width = '100%';
        select.style.minHeight = '38px';
        select.style.marginTop = '4px';
        select.style.boxSizing = 'border-box';

        input.style.display = 'none';
        input.parentNode.appendChild(select);

        return select;
    }

    function ensureCarSelect() {
        var input = findInputByFieldName(CAR_FIELD, 'Автомобиль сервисного центра');

        if (!input) {
            return;
        }

        var select = createSelectAfterInput(input, CAR_SELECT_ID);

        select.onchange = function () {
            setInputValue(input, select.value);
            log('selected car id', select.value);
        };

        getContactId(function (contactId) {
            if (!contactId || contactId <= 0) {
                select.innerHTML = '';

                var noClientOption = document.createElement('option');
                noClientOption.value = '';
                noClientOption.text = 'Сначала выберите клиента и сохраните сделку';
                select.appendChild(noClientOption);

                return;
            }

            if (
                select.getAttribute('data-loaded-contact-id') === String(contactId)
                && select.querySelector('option[data-car-option="Y"]')
            ) {
                return;
            }

            select.innerHTML = '';

            var loadingOption = document.createElement('option');
            loadingOption.value = '';
            loadingOption.text = 'Загрузка автомобилей клиента...';
            select.appendChild(loadingOption);

            BX.ajax({
                url: '/local/tools/sharov_servicecenter_contact_cars.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    contactId: contactId
                },
                onsuccess: function (response) {
                    select.innerHTML = '';

                    var defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.text = 'Выберите автомобиль клиента';
                    select.appendChild(defaultOption);

                    if (!response || !response.success) {
                        var errorOption = document.createElement('option');
                        errorOption.value = '';
                        errorOption.text = response && response.error
                            ? response.error
                            : 'Ошибка загрузки автомобилей';
                        select.appendChild(errorOption);

                        select.removeAttribute('data-loaded-contact-id');
                        return;
                    }

                    if (!response.cars || response.cars.length === 0) {
                        var emptyOption = document.createElement('option');
                        emptyOption.value = '';
                        emptyOption.text = 'У клиента нет автомобилей в гараже';
                        select.appendChild(emptyOption);

                        select.setAttribute('data-loaded-contact-id', String(contactId));
                        return;
                    }

                    response.cars.forEach(function (car) {
                        var option = document.createElement('option');

                        option.value = car.id;
                        option.text = car.label;
                        option.setAttribute('data-car-option', 'Y');

                        if (String(input.value) === String(car.id)) {
                            option.selected = true;
                        }

                        select.appendChild(option);
                    });

                    select.setAttribute('data-loaded-contact-id', String(contactId));

                    if (response.cars.length === 1 && !input.value) {
                        select.value = response.cars[0].id;
                        setInputValue(input, response.cars[0].id);
                    }
                },
                onfailure: function () {
                    select.innerHTML = '';

                    var option = document.createElement('option');
                    option.value = '';
                    option.text = 'Ошибка ajax-запроса автомобилей';
                    select.appendChild(option);

                    select.removeAttribute('data-loaded-contact-id');
                }
            });
        });
    }

    function ensureMechanicSelect() {
        var input = findInputByFieldName(MECHANIC_FIELD, 'Механик');

        if (!input) {
            return;
        }

        var select = createSelectAfterInput(input, MECHANIC_SELECT_ID);

        if (select.getAttribute('data-loaded') === 'Y') {
            return;
        }

        select.setAttribute('data-loaded', 'Y');

        select.innerHTML = '';

        var loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.text = 'Загрузка механиков...';
        select.appendChild(loadingOption);

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_mechanics.php',
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                select.innerHTML = '';

                var defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.text = 'Выберите механика';
                select.appendChild(defaultOption);

                if (!response || !response.success) {
                    var errorOption = document.createElement('option');
                    errorOption.value = '';
                    errorOption.text = response && response.error
                        ? response.error
                        : 'Ошибка загрузки механиков';
                    select.appendChild(errorOption);
                    return;
                }

                if (!response.mechanics || response.mechanics.length === 0) {
                    var emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.text = 'В группе механиков нет пользователей';
                    select.appendChild(emptyOption);
                    return;
                }

                response.mechanics.forEach(function (mechanic) {
                    var option = document.createElement('option');
                    option.value = mechanic.id;
                    option.text = mechanic.label;

                    if (String(input.value) === String(mechanic.id)) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });
            },
            onfailure: function () {
                select.innerHTML = '';

                var option = document.createElement('option');
                option.value = '';
                option.text = 'Ошибка ajax-запроса механиков';
                select.appendChild(option);
            }
        });

        select.onchange = function () {
            setInputValue(input, select.value);
            log('selected mechanic id', select.value);
        };
    }

    function getVisibleTextNodes() {
        var walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function (node) {
                    var value = (node.nodeValue || '').trim();

                    if (value === '') {
                        return NodeFilter.FILTER_REJECT;
                    }

                    var parent = node.parentNode;

                    if (!parent || !parent.tagName) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    var tag = parent.tagName.toUpperCase();

                    if (
                        tag === 'SCRIPT'
                        || tag === 'STYLE'
                        || tag === 'OPTION'
                        || tag === 'SELECT'
                        || tag === 'TEXTAREA'
                    ) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    var style = window.getComputedStyle(parent);

                    if (
                        style.display === 'none'
                        || style.visibility === 'hidden'
                        || style.opacity === '0'
                    ) {
                        return NodeFilter.FILTER_REJECT;
                    }

                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );

        var nodes = [];
        var node;

        while ((node = walker.nextNode())) {
            nodes.push(node);
        }

        return nodes;
    }

    function findFieldRegion(nodes, labelText) {
        var stopLabels = [
            'Автомобиль сервисного центра',
            'Пробег при приемке',
            'Жалоба клиента',
            'Механик',
            'Клиент',
            'Сумма и валюта',
            'Дата завершения',
            'Выбрать поле',
            'Создать поле',
            'Удалить раздел'
        ];

        for (var i = 0; i < nodes.length; i++) {
            var text = (nodes[i].nodeValue || '').trim();

            if (text !== labelText && text.indexOf(labelText) === -1) {
                continue;
            }

            var endIndex = nodes.length;

            for (var j = i + 1; j < nodes.length; j++) {
                var stopText = (nodes[j].nodeValue || '').trim();

                if (stopLabels.indexOf(stopText) !== -1) {
                    endIndex = j;
                    break;
                }
            }

            return {
                start: i,
                end: endIndex
            };
        }

        return null;
    }

    function normalizeReadonlyValue(labelText, prettyText, rawId) {
        if (!prettyText || !rawId) {
            return false;
        }

        var rawIdString = String(rawId);
        var nodes = getVisibleTextNodes();
        var region = findFieldRegion(nodes, labelText);

        if (!region) {
            log('readonly region not found', labelText);
            return false;
        }

        var foundPretty = false;
        var changed = false;

        /*
         * Область поля:
         * labelText
         * value
         *
         * Каждый проход:
         * - красивое значение оставляем только одно;
         * - сырой ID удаляем, если красивое уже есть;
         * - сырой ID заменяем на красивое, если красивого ещё нет.
         */
        for (var i = region.start + 1; i < region.end; i++) {
            var text = (nodes[i].nodeValue || '').trim();

            if (text === prettyText) {
                if (!foundPretty) {
                    foundPretty = true;

                    if (nodes[i].parentNode && nodes[i].parentNode.style) {
                        nodes[i].parentNode.style.fontWeight = 'normal';
                        nodes[i].parentNode.style.color = '#333';
                        nodes[i].parentNode.style.fontSize = '14px';
                    }
                } else {
                    nodes[i].nodeValue = '';
                    changed = true;
                }

                continue;
            }

            if (text === rawIdString) {
                if (!foundPretty) {
                    nodes[i].nodeValue = nodes[i].nodeValue.replace(rawIdString, prettyText);
                    foundPretty = true;
                } else {
                    nodes[i].nodeValue = '';
                }

                if (nodes[i].parentNode && nodes[i].parentNode.style) {
                    nodes[i].parentNode.style.fontWeight = 'normal';
                    nodes[i].parentNode.style.color = '#333';
                    nodes[i].parentNode.style.fontSize = '14px';
                }

                changed = true;
            }
        }

        if (foundPretty) {
            log(
                changed ? 'readonly value normalized' : 'readonly value already normalized',
                labelText + ': ' + prettyText
            );
            return true;
        }

        /*
         * Fallback: если Bitrix сделал странную разметку,
         * ищем rawId рядом после label.
         */
        var fallbackEnd = Math.min(nodes.length, region.start + 30);

        for (var j = region.start + 1; j < fallbackEnd; j++) {
            var fallbackText = (nodes[j].nodeValue || '').trim();

            if (fallbackText === prettyText) {
                return true;
            }

            if (fallbackText === rawIdString) {
                nodes[j].nodeValue = nodes[j].nodeValue.replace(rawIdString, prettyText);

                if (nodes[j].parentNode && nodes[j].parentNode.style) {
                    nodes[j].parentNode.style.fontWeight = 'normal';
                    nodes[j].parentNode.style.color = '#333';
                    nodes[j].parentNode.style.fontSize = '14px';
                }

                log('readonly value normalized by fallback', labelText + ': ' + prettyText);
                return true;
            }
        }

        log('readonly value not found after label', labelText + ': ' + rawIdString);
        return false;
    }

    function applyReadonlyLabels(response) {
        if (!response || !response.success) {
            return;
        }

        document.querySelectorAll('.sharov-sc-readable-label').forEach(function (item) {
            item.remove();
        });

        if (response.carLabel) {
            normalizeReadonlyValue(
                'Автомобиль сервисного центра',
                response.carLabel,
                response.carId
            );
        }

        if (response.mechanicLabel) {
            normalizeReadonlyValue(
                'Механик',
                response.mechanicLabel,
                response.mechanicId
            );
        }
    }

    function ensureReadonlyLabels() {
        /*
         * Если карточка в режиме редактирования — там работают select'ы.
         */
        if (document.getElementById(CAR_SELECT_ID) || document.getElementById(MECHANIC_SELECT_ID)) {
            return;
        }

        var dealId = getDealIdFromUrl();

        if (!dealId || dealId <= 0) {
            return;
        }

        /*
         * Даже без нового AJAX каждый проход применяем последние данные:
         * это чистит ID, если Bitrix дорисовал их заново при открытии/перерисовке.
         */
        if (lastReadonlyLabelsResponse) {
            applyReadonlyLabels(lastReadonlyLabelsResponse);
        }

        var now = Date.now();

        if (readonlyLabelsRequestInProgress) {
            return;
        }

        /*
         * Не дёргаем сервер каждую секунду.
         */
        if (now - lastReadonlyLabelsRequestTime < 3000) {
            return;
        }

        readonlyLabelsRequestInProgress = true;
        lastReadonlyLabelsRequestTime = now;

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_deal_labels.php?dealId=' + encodeURIComponent(dealId),
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                readonlyLabelsRequestInProgress = false;

                log('deal labels response', response);

                if (!response || !response.success) {
                    return;
                }

                lastReadonlyLabelsResponse = response;
                applyReadonlyLabels(response);
            },
            onfailure: function () {
                readonlyLabelsRequestInProgress = false;
                log('deal labels ajax failure');
            }
        });
    }

    function init() {
        ensureCarSelect();
        ensureMechanicSelect();
        ensureReadonlyLabels();
    }

    BX.ready(function () {
        log('deal-service-selects loaded');

        init();

        /*
        * Быстрые повторные запуски после открытия карточки.
        * Bitrix дорисовывает поля не сразу.
        */
        setTimeout(init, 50);
        setTimeout(init, 150);
        setTimeout(init, 300);
        setTimeout(init, 700);
        setTimeout(init, 1200);
        setTimeout(init, 2000);

        /*
        * Реакция на перерисовку карточки Bitrix.
        */
        var mutationTimer = null;

        var observer = new MutationObserver(function () {
            clearTimeout(mutationTimer);

            mutationTimer = setTimeout(function () {
                init();
            }, 100);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true
        });

        /*
        * Запасной периодический запуск.
        * Можно оставить 3000, потому что основной ускоритель теперь MutationObserver.
        */
        setInterval(init, 3000);
    });
})();