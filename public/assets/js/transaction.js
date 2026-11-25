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

    // label: shown above the value when the line list is too narrow for a table (see admin.css).
    function cell(child, className, label) {
        const td = document.createElement('td');
        if (className) {
            td.className = className;
        }
        if (label) {
            td.dataset.label = label;
        }
        if (child instanceof Node) {
            td.appendChild(child);
        } else if (child !== undefined) {
            td.textContent = child;
        }
        return td;
    }

    function vatCell(index, product) {
        const group = document.createElement('div');
        group.className = 'input-group input-group-sm line-vat';
        const type = document.createElement('select');
        type.className = 'form-select';
        type.name = 'items[' + index + '][vat_type]';
        type.setAttribute('data-role', 'vat-type');
        type.setAttribute('aria-label', 'VAT type');
        type.title = 'P = percentage, F = flat amount per unit';
        type.append(new Option('P', 'percentage'), new Option('F', 'flat'));
        type.value = product.vat_type === 'flat' ? 'flat' : 'percentage';
        const value = makeInput('items[' + index + '][vat_value]', product.vat_value || 0, {
            type: 'number', min: '0', step: 'any', placeholder: 'VAT', 'data-role': 'vat-value', 'aria-label': 'VAT',
        });
        group.append(type, value);

        const wrap = document.createElement('div');
        wrap.className = 'line-vat-wrap';
        const amount = document.createElement('div');
        amount.className = 'line-vat-amount';
        amount.setAttribute('data-role', 'vat-amount');
        wrap.append(group, amount);
        return wrap;
    }

    function round2(value) {
        return Math.round((value + Number.EPSILON) * 100) / 100;
    }

    // Splits an amount in proportion to the weights, to the paisa, the same way the server does.
    function spread(amount, weights) {
        const parts = weights.map(function () { return 0; });
        const total = weights.reduce(function (sum, weight) { return sum + weight; }, 0);
        if (!weights.length) {
            return parts;
        }
        if (total <= 0) {
            parts[0] = amount;
            return parts;
        }
        let given = 0;
        weights.forEach(function (weight, index) {
            parts[index] = index === weights.length - 1 ? round2(amount - given) : round2(amount * weight / total);
            given += parts[index];
        });
        return parts;
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    // Sale totals: discount, then VAT on what is paid after the discount. Same rules as SaleCalculator.
    function recalculateSale(lines) {
        const subtotal = round2(lines.reduce(function (sum, line) { return sum + line.total; }, 0));

        const discountType = field('discount_type').value;
        const discountInput = field('discount_value');
        const discountValue = number(discountInput.value);
        discountInput.max = discountType === 'percentage' ? '100' : subtotal.toFixed(2);
        const discount = discountType === 'percentage'
            ? round2(subtotal * Math.min(discountValue, 100) / 100)
            : Math.min(round2(discountValue), subtotal);
        const nets = spread(discount, lines.map(function (line) { return line.total; }))
            .map(function (share, index) { return round2(lines[index].total - share); });

        const onTotal = field('vat_mode').value === 'order';
        document.getElementById('order-vat-row').classList.toggle('d-none', !onTotal);
        let vat = 0;

        lines.forEach(function (line, index) {
            const type = line.row.querySelector('[data-role="vat-type"]');
            const value = line.row.querySelector('[data-role="vat-value"]');
            const amount = line.row.querySelector('[data-role="vat-amount"]');
            type.disabled = onTotal;
            value.disabled = onTotal;
            value.max = type.value === 'percentage' ? '100' : '';
            if (onTotal) {
                amount.textContent = 'On total';
                return;
            }
            const rate = number(value.value);
            const lineVat = type.value === 'percentage' ? round2(nets[index] * Math.min(rate, 100) / 100) : round2(rate * line.qty);
            amount.textContent = '= ' + money(lineVat);
            vat += lineVat;
        });

        if (onTotal) {
            const orderType = field('vat_type').value;
            const orderInput = field('vat_value');
            orderInput.max = orderType === 'percentage' ? '100' : '';
            const rate = number(orderInput.value);
            vat = orderType === 'percentage' ? round2((subtotal - discount) * Math.min(rate, 100) / 100) : round2(rate);
        }
        vat = round2(vat);

        const grand = round2(subtotal - discount + vat);
        // Points pay part of the sale, like a payment.
        const pointsAmount = applyPoints(grand);
        const payable = round2(grand - pointsAmount);
        const paidInput = field('paid_amount');
        // Paid follows the amount to pay until the cashier types an amount of their own.
        if (!paidInput.dataset.touched) {
            paidInput.value = payable > 0 ? payable.toFixed(2) : '';
        }
        const due = Math.max(0, round2(payable - number(paidInput.value)));
        paidInput.max = Math.max(0, payable).toFixed(2);
        setText('summary-payable', money(payable));
        document.getElementById('summary-payable-row').classList.toggle('d-none', pointsAmount <= 0);

        setText('summary-subtotal', money(subtotal));
        setText('summary-discount', money(discount));
        setText('summary-vat', money(vat));
        setText('summary-grand', money(grand));
        setText('summary-due', money(due));

        document.getElementById('walk-in-due').classList.toggle('d-none', !walkInWithDue(due));
        return due;
    }

    const pointBalances = JSON.parse(form.dataset.points || '{}');

    // Shows the chosen customer's points, and works out the value of the points being used.
    function applyPoints(grand) {
        const box = document.getElementById('points-box');
        const customer = field('customer_id').value;
        const named = !!customer && customer !== form.dataset.walkIn;
        const balance = named ? (pointBalances[customer] || 0) : 0;
        const value = number(form.dataset.pointValue);
        const input = field('points_redeemed');
        let amount = 0;

        if (box) {
            box.classList.toggle('d-none', !named);
        }
        if (input) {
            const usable = named && value > 0 ? Math.max(0, Math.min(balance, Math.floor(grand / value + 1e-9))) : 0;
            input.max = usable;
            input.disabled = usable === 0;
            if (usable === 0) {
                input.value = '';
            }
            const used = Math.min(Math.max(0, Math.floor(number(input.value))), usable);
            amount = round2(used * value);
            document.getElementById('use-all-points').dataset.usable = usable;
            setText('points-worth', money(balance * value));
        }
        setText('points-balance', balance.toLocaleString());

        const earnAmount = number(form.dataset.pointEarn);
        setText('points-earn', named && earnAmount > 0 ? Math.floor(round2(grand - amount) / earnAmount + 1e-9) : 0);
        setText('summary-points', money(amount));
        const row = document.getElementById('summary-points-row');
        if (row) {
            row.classList.toggle('d-none', amount <= 0);
        }
        return amount;
    }

    function walkInWithDue(due) {
        const walkIn = form.dataset.walkIn;
        return !!walkIn && field('customer_id').value === walkIn && due > 0.004 && tbody.children.length > 0;
    }

    function findRow(productId) {
        return tbody.querySelector('tr[data-product-id="' + productId + '"]');
    }

    function addRow(product, quantity) {
        const index = counter++;
        const row = document.createElement('tr');
        row.dataset.productId = product.id;
        row.dataset.available = product.available;

        // Row number comes from a CSS counter, so it stays right after removals.
        row.appendChild(cell(undefined, 'c-sl'));

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
        row.appendChild(cell(name, 'c-product'));

        if (mode !== 'purchase') {
            row.appendChild(cell(product.available + ' ' + (product.unit || ''), 'text-nowrap c-available', 'Available'));
        }

        const qtyAttrs = { type: 'number', min: '0.001', step: 'any', required: 'required', placeholder: 'Qty', 'data-role': 'qty' };
        if (mode !== 'purchase') {
            qtyAttrs.max = product.available;
        }
        row.appendChild(cell(makeInput('items[' + index + '][quantity]', quantity, qtyAttrs), 'c-qty', 'Qty'));

        if (mode === 'damage') {
            row.appendChild(cell(makeInput('items[' + index + '][reason]', product.reason || '', {
                type: 'text', required: 'required', maxlength: '255', placeholder: 'Damage reason',
            }), 'c-reason', 'Reason'));
        } else {
            row.appendChild(cell(makeInput('items[' + index + '][unit_price]', product.price, {
                type: 'number', min: '0', step: 'any', required: 'required', placeholder: 'Price', 'data-role': 'price',
            }), 'c-price', mode === 'sale' ? 'Unit price' : 'Purchase price'));
            if (mode === 'sale') {
                row.appendChild(cell(vatCell(index, product), 'c-vat', 'VAT'));
            }
            const total = cell('0.00', 'num c-total', 'Total');
            total.dataset.role = 'total';
            row.appendChild(total);
        }

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-sm btn-outline-danger';
        remove.title = 'Remove';
        remove.setAttribute('data-role', 'remove');
        remove.innerHTML = '<i class="bi bi-x-lg"></i>';
        row.appendChild(cell(remove, 'text-end c-remove'));

        tbody.appendChild(row);
        return row;
    }

    function recalculate() {
        let subtotal = 0;
        let quantity = 0;
        const lines = [];

        tbody.querySelectorAll('tr').forEach(function (row) {
            const qty = number(row.querySelector('[data-role="qty"]').value);
            quantity += qty;
            const priceInput = row.querySelector('[data-role="price"]');
            if (priceInput) {
                const line = round2(qty * number(priceInput.value));
                subtotal += line;
                lines.push({ row: row, qty: qty, total: line });
                row.querySelector('[data-role="total"]').textContent = money(line);
            }
        });

        emptyRow.classList.toggle('d-none', tbody.children.length > 0);

        setText('summary-qty', Math.round(quantity * 1000) / 1000);
        setText('summary-count', tbody.children.length);

        if (mode === 'damage') {
            return;
        }
        if (mode === 'sale') {
            recalculateSale(lines);
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
        const useAll = event.target.closest('#use-all-points');
        if (useAll) {
            field('points_redeemed').value = useAll.dataset.usable || 0;
            recalculate();
        }
        if (event.target.closest('#pay-full')) {
            delete field('paid_amount').dataset.touched;
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
            return;
        }
        if (mode === 'sale' && walkInWithDue(recalculateSale(saleLines()))) {
            event.preventDefault();
            window.Toast('A sale with a due amount needs a named customer. Choose the customer, or take the full payment.', 'warning');
        }
    });

    function saleLines() {
        return Array.prototype.map.call(tbody.querySelectorAll('tr'), function (row) {
            const qty = number(row.querySelector('[data-role="qty"]').value);
            return { row: row, qty: qty, total: round2(qty * number(row.querySelector('[data-role="price"]').value)) };
        });
    }

    if (mode === 'sale') {
        // Typing an amount stops Paid from following the total.
        field('paid_amount').addEventListener('input', function (event) {
            event.target.dataset.touched = '1';
        });
        // The customer list is a Select2 dropdown, which reports changes through jQuery.
        jQuery(field('customer_id')).on('change', recalculate);
    }

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
