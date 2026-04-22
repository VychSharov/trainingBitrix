(function (window, document) {
    'use strict';

    function normalizeText(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    }

    function parseElementId(href) {
        if (!href) {
            return 0;
        }

        var match = href.match(/\/element\/0\/(\d+)\//);
        return match ? parseInt(match[1], 10) : 0;
    }

    function getHeaderIndexes(table) {
        var result = {
            name: -1,
            procedure: -1,
            booking: -1
        };

        var headers = table.querySelectorAll('thead th');
        headers.forEach(function (header, index) {
            var text = normalizeText(header.innerText);

            if (text === 'Название') {
                result.name = index;
            } else if (text === 'Процедура') {
                result.procedure = index;
            } else if (text === 'Запись на процедуру') {
                result.booking = index;
            }
        });

        return result;
    }

    function getProceduresFromCell(cell) {
        var result = [];
        var links = cell.querySelectorAll('a[href]');

        links.forEach(function (link) {
            var procedureId = parseElementId(link.getAttribute('href'));
            var procedureName = normalizeText(link.textContent);

            if (!procedureId || !procedureName) {
                return;
            }

            result.push({
                id: procedureId,
                name: procedureName
            });
        });

        return result;
    }

    function buildButtons(cell, doctorId, procedures) {
        cell.innerHTML = '';

        if (!doctorId || !procedures.length) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'otus-public-booking-cell';

        procedures.forEach(function (procedure) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'ui-btn ui-btn-xs ui-btn-light-border otus-booking-btn';
            button.setAttribute('data-doctor-id', doctorId);
            button.setAttribute('data-procedure-id', procedure.id);
            button.setAttribute('data-procedure-name', procedure.name);
            button.style.margin = '0 6px 6px 0';
            button.textContent = procedure.name;

            wrap.appendChild(button);
        });

        cell.appendChild(wrap);
    }

    function renderList() {
        var table = document.querySelector('.main-grid-table');
        if (!table) {
            return;
        }

        var indexes = getHeaderIndexes(table);
        if (indexes.name === -1 || indexes.procedure === -1 || indexes.booking === -1) {
            return;
        }

        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (!cells.length) {
                return;
            }

            var nameCell = cells[indexes.name];
            var procedureCell = cells[indexes.procedure];
            var bookingCell = cells[indexes.booking];

            if (!nameCell || !procedureCell || !bookingCell) {
                return;
            }

            var doctorLink = nameCell.querySelector('a[href*="/element/0/"]');
            var doctorId = parseElementId(doctorLink ? doctorLink.getAttribute('href') : '');
            var procedures = getProceduresFromCell(procedureCell);

            buildButtons(bookingCell, doctorId, procedures);
        });
    }

    function init() {
        renderList();

        var observerTarget = document.querySelector('.main-grid-container') || document.body;
        if (!observerTarget) {
            return;
        }

        var observer = new MutationObserver(function () {
            renderList();
        });

        observer.observe(observerTarget, {
            childList: true,
            subtree: true
        });
    }

    if (window.BX) {
        BX.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(window, document);