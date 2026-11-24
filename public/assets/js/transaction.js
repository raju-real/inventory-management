/* Line items of purchases, sales and damage entries. Totals here are only a preview: the server recalculates them. */
(function () {
    'use strict';

    const form = document.getElementById('transaction-form');
    if (!form) {
        return;
    }

    const mode = form.dataset.mode;
    const tbody = form.querySelector('#line-items');
    const emptyRow = form.querySelector('#line-empty');
    const money = function (value) { return (Number(value) || 0).toFixed(2); };
    const field = function (name) { return form.querySelector('[name="' + name + '"]'); };
    let counter = 0;

    function number(value) {
        return parseFloat(value) || 0;
    }

    function makeInput(name, value, attrs) {
        const input = document.createElement('input');
        input.name = name;
        input.value = value;
        input.className = 'form-control form-control-sm';
        Object.keys(attrs || {}).forEach(function (key) { input.setAttribute(key, attrs[key]); });
        return input;
    }

    function cell(child, className) {
        const td = document.createElement('td');
        if (className) {
            td.className = className;
        }
        if (child instanceof Node) {
            td.appendChild(child);
        } else if (child !== undefined) {
            td.textContent = child;
        }
        return td;
    }

    function findRow(productId) {
        return tbody.querySelector('tr[data-product-id="' + productId + '"]');
    }

    function addRow(product, quantity) {
        const index = counter++;
        const row = document.createElement('tr');
        row.dataset.productId = product.id;
        row.dataset.available = product.available;

        const name = document.createElement('div');
        const title = document.createElement('div');
        title.className = 'fw-medium';
        title.textContent = product.name;
        const code = document.createElement('small');
        code.className = 'text-muted';
        code.textContent = product.sku + (product.barcode ? ' · ' + product.barcode : '');
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'items[' + index + '][product_id]';
        hidden.value = product.id;
        name.append(title, code, hidden);
        row.appendChild(cell(name));

        if (mode !== 'purchase') {
            row.appendChild(cell(product.available + ' ' + (product.unit || ''), 'text-nowrap'));
        }

        const qtyAttrs = { type: 'number', min: '0.001', step: 'any', required: 'required', placeholder: 'Qty', 'data-role': 'qty' };
        if (mode !== 'purchase') {
            qtyAttrs.max = product.available;
        }
        row.appendChild(cell(makeInput('items[' + index + '][quantity]', quantity, qtyAttrs)));

        if (mode === 'damage') {
            row.appendChild(cell(makeInput('items[' + index + '][reason]', product.reason || '', {
                type: 'text', required: 'required', maxlength: '255', placeholder: 'Damage reason',
            })));
        } else {
            row.appendChild(cell(makeInput('items[' + index + '][unit_price]', product.price, {
                type: 'number', min: '0', step: 'any', required: 'required', placeholder: 'Price', 'data-role': 'price',
            })));
            const total = cell('0.00', 'num');
            total.dataset.role = 'total';
            row.appendChild(total);
        }

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.title = 'Remove';
        remove.setAttribute('data-role', 'remove');
        remove.innerHTML = '<i class="bi bi-x-lg"></i>';
        row.appendChild(cell(remove, 'text-end'));

        tbody.appendChild(row);
        return row;
    }

    function recalculate() {
        let subtotal = 0;
        let quantity = 0;

        tbody.querySelectorAll('tr').forEach(function (row) {
            const qty = number(row.querySelector('[data-role="qty"]').value);
            quantity += qty;
            const priceInput = row.querySelector('[data-role="price"]');
            if (priceInput) {
                const line = qty * number(priceInput.value);
                subtotal += line;
                row.querySelector('[data-role="total"]').textContent = money(line);
            }
        });

        emptyRow.classList.toggle('d-none', tbody.children.length > 0);
        const setText = function (id, value) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = value;
            }
        };

        setText('summary-qty', Math.round(quantity * 1000) / 1000);
        setText('summary-count', tbody.children.length);

        if (mode === 'damage') {
            return;
        }

        const discount = number(field('discount').value);
        const other = field('other_charges') ? number(field('other_charges').value) : 0;
        const grand = subtotal - discount + other;
        const paid = number(field('paid_amount').value);

        setText('summary-subtotal', money(subtotal));
        setText('summary-grand', money(grand));
        setText('summary-due', money(Math.max(0, grand - paid)));
        field('discount').max = subtotal.toFixed(2);
        field('paid_amount').max = Math.max(0, grand).toFixed(2);
    }

    function flashRow(row) {
        row.classList.remove('row-flash');
        void row.offsetWidth;
        row.classList.add('row-flash');
        row.scrollIntoView({ block: 'nearest' });
    }

    function onPick(product, fromScan) {
        let row = findRow(product.id);

        if (mode !== 'purchase' && product.available <= 0) {
            window.Scanner.beep(false);
            window.Toast(product.name + ' is out of stock.', 'warning');
            return;
        }

        if (row) {
            const qtyInput = row.querySelector('[data-role="qty"]');
            const next = number(qtyInput.value) + 1;
            if (mode !== 'purchase' && next > product.available) {
                window.Scanner.beep(false);
                window.Toast('Only ' + product.available + ' of ' + product.name + ' available.', 'warning');
                flashRow(row);
                return;
            }
            qtyInput.value = next;
        } else {
            row = addRow(product, 1);
        }

        if (fromScan) {
            window.Scanner.beep(true);
        } else {
            row.querySelector('[data-role="qty"]').select();
        }

        form.dataset.dirty = '1';
        flashRow(row);
        recalculate();
    }

    form.addEventListener('input', recalculate);

    form.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-role="remove"]');
        if (remove) {
            remove.closest('tr').remove();
            recalculate();
        }
        if (event.target.closest('#pay-full')) {
            field('paid_amount').value = document.getElementById('summary-grand').textContent;
            recalculate();
        }
    });

    // Enter in a line field moves on instead of posting the invoice by accident.
    form.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.tagName === 'INPUT' && !event.defaultPrevented) {
            event.preventDefault();
            document.getElementById('product-search').focus();
        }
    });

    form.addEventListener('form:validate', function (event) {
        if (!tbody.children.length) {
            event.preventDefault();
            window.Toast('Add at least one product.', 'warning');
        }
    });

    window.ProductPicker({
        input: document.getElementById('product-search'),
        context: mode,
        searchUrl: form.dataset.searchUrl,
        lookupUrl: form.dataset.lookupUrl,
        onPick: onPick,
        onNotFound: function (code) {
            if (form.dataset.createUrl) {
                const box = document.getElementById('scan-not-found');
                box.querySelector('[data-code]').textContent = code;
                box.querySelector('a').href = form.dataset.createUrl + '?barcode=' + encodeURIComponent(code);
                box.classList.remove('d-none');
                setTimeout(function () { box.classList.add('d-none'); }, 8000);
            } else {
                window.Toast('No product found for code ' + code + '.', 'warning');
            }
        },
    });

    JSON.parse(form.dataset.oldItems || '[]').forEach(function (item) {
        addRow(item, item.quantity);
    });
    recalculate();

    if (!tbody.children.length) {
        document.getElementById('product-search').focus();
    }
})();
