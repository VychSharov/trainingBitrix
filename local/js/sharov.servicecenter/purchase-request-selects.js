(function () {
    if (window.__SHAROV_PURCHASE_SELECTS_LOADED) {
        return;
    }

    window.__SHAROV_PURCHASE_SELECTS_LOADED = true;

    var PRODUCT_FIELD = 'UF_SC_SOURCE_PRODUCT_ID';
    var REQUESTER_FIELD = 'UF_SC_REQUESTER_ID';
    var APPROVER_FIELD = 'UF_SC_APPROVER_ID';

    var PRODUCT_SELECT_ID = 'sharov-sc-purchase-product-select';
    var REQUESTER_SELECT_ID = 'sharov-sc-purchase-requester-select';
    var APPROVER_SELECT_ID = 'sharov-sc-purchase-approver-select';

    var labelsRequestInProgress = false;
    var lastLabelsResponse = null;
    var lastLabelsRequestTime = 0;

    function log(message, data) {
        if (window.console) {
            console.log('[purchase-request]', message, data || '');
        }
    }

    function getItemIdFromUrl() {
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
                /\/crm\/type\/1046\/details\/(\d+)\//i,
                /\/crm\/type\/1046\/edit\/(\d+)\//i,
                /itemId=(\d+)/i,
                /item_id=(\d+)/i,
                /ENTITY_ID=(\d+)/i,
                /"itemId"\s*:\s*"?(\d+)"?/i,
                /"ENTITY_ID"\s*:\s*"?(\d+)"?/i
            ];

            for (var j = 0; j < patterns.length; j++) {
                var match = source.match(patterns[j]);

                if (match && match[1]) {
                    return parseInt(match[1], 10);
                }
            }
        }

        return 0;
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

    function ensureProductSelect() {
        var input = findInputByFieldName(PRODUCT_FIELD, 'Запчасть');

        if (!input) {
            return;
        }

        var select = createSelectAfterInput(input, PRODUCT_SELECT_ID);

        if (select.getAttribute('data-loaded') === 'Y') {
            return;
        }

        select.setAttribute('data-loaded', 'Y');

        select.innerHTML = '<option value="">Загрузка запчастей...</option>';

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_parts_list.php',
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                select.innerHTML = '<option value="">Выберите запчасть</option>';

                if (!response || !response.success) {
                    select.innerHTML += '<option value="">Ошибка загрузки запчастей</option>';
                    return;
                }

                response.parts.forEach(function (part) {
                    var option = document.createElement('option');
                    option.value = part.id;
                    option.text = part.label;

                    if (String(input.value) === String(part.id)) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });
            }
        });

        select.onchange = function () {
            setInputValue(input, select.value);
            log('selected product id', select.value);
        };
    }

    function ensureUserSelect(fieldName, labelText, selectId, role, autoCurrentUser, autoSingleUser) {
        var input = findInputByFieldName(fieldName, labelText);

        if (!input) {
            return;
        }

        var select = createSelectAfterInput(input, selectId);

        if (select.getAttribute('data-loaded') === 'Y') {
            return;
        }

        select.setAttribute('data-loaded', 'Y');

        select.innerHTML = '<option value="">Загрузка пользователей...</option>';

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_purchase_users.php?role=' + encodeURIComponent(role),
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                select.innerHTML = '<option value="">Выберите пользователя</option>';

                if (!response || !response.success) {
                    var errorOption = document.createElement('option');
                    errorOption.value = '';
                    errorOption.text = response && response.error ? response.error : 'Ошибка загрузки пользователей';
                    select.appendChild(errorOption);
                    return;
                }

                response.users.forEach(function (user) {
                    var option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.label;

                    if (String(input.value) === String(user.id)) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });

                if (!input.value && autoCurrentUser && response.currentUserId) {
                    select.value = response.currentUserId;
                    setInputValue(input, response.currentUserId);
                }

                if (!input.value && autoSingleUser && response.users.length === 1) {
                    select.value = response.users[0].id;
                    setInputValue(input, response.users[0].id);
                }
            }
        });

        select.onchange = function () {
            setInputValue(input, select.value);
            log('selected user for ' + fieldName, select.value);
        };
    }

    function ensureRequesterSelect() {
        ensureUserSelect(
            REQUESTER_FIELD,
            'Инициатор заявки',
            REQUESTER_SELECT_ID,
            'requester',
            true,
            false
        );
    }

    function ensureApproverSelect() {
        ensureUserSelect(
            APPROVER_FIELD,
            'Согласующий',
            APPROVER_SELECT_ID,
            'approver',
            false,
            true
        );
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
            'Запчасть',
            'Инициатор заявки',
            'Согласующий',
            'Автоматическая заявка',
            'Количество',
            'Причина отказа',
            'Заявка обработана',
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
            return false;
        }

        var foundPretty = false;
        var changed = false;

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
            return true;
        }

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

                return true;
            }
        }

        return false;
    }

    function applyReadonlyLabels(response) {
        if (!response || !response.success) {
            return;
        }

        if (response.productLabel) {
            normalizeReadonlyValue('Запчасть', response.productLabel, response.productId);
        }

        if (response.requesterLabel) {
            normalizeReadonlyValue('Инициатор заявки', response.requesterLabel, response.requesterId);
        }

        if (response.approverLabel) {
            normalizeReadonlyValue('Согласующий', response.approverLabel, response.approverId);
        }
    }

    function ensureReadonlyLabels() {
        if (
            document.getElementById(PRODUCT_SELECT_ID)
            || document.getElementById(REQUESTER_SELECT_ID)
            || document.getElementById(APPROVER_SELECT_ID)
        ) {
            return;
        }

        var itemId = getItemIdFromUrl();

        if (!itemId || itemId <= 0) {
            return;
        }

        if (lastLabelsResponse) {
            applyReadonlyLabels(lastLabelsResponse);
        }

        var now = Date.now();

        if (labelsRequestInProgress) {
            return;
        }

        if (now - lastLabelsRequestTime < 3000) {
            return;
        }

        labelsRequestInProgress = true;
        lastLabelsRequestTime = now;

        BX.ajax({
            url: '/local/tools/sharov_servicecenter_purchase_labels.php?itemId=' + encodeURIComponent(itemId),
            method: 'GET',
            dataType: 'json',
            onsuccess: function (response) {
                labelsRequestInProgress = false;

                log('labels response', response);

                if (!response || !response.success) {
                    return;
                }

                lastLabelsResponse = response;
                applyReadonlyLabels(response);
            },
            onfailure: function () {
                labelsRequestInProgress = false;
            }
        });
    }

    function init() {
        ensureProductSelect();
        ensureRequesterSelect();
        ensureApproverSelect();
        ensureReadonlyLabels();
    }

    BX.ready(function () {
        log('purchase-request-selects loaded');

        init();

        setTimeout(init, 100);
        setTimeout(init, 500);
        setTimeout(init, 1000);
        setTimeout(init, 2000);

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

        setInterval(init, 3000);
    });
})();