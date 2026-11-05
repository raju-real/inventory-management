/* Return screens: a scan finds the invoice, then counts returned items against its lines. */
(function () {
    'use strict';

    const lookupForm = document.getElementById('invoice-lookup');
    const form = document.getElementById('return-form');

    if (lookupForm) {
        window.Scanner.on(function (code) {
            const row = form ? form.querySelector('tr[data-codes~="' + CSS.escape(code) + '"]') : null;

            if (row) {
                const input = row.querySelector('[data-role="qty"]');
                const max = parseFloat(input.max) || 0;
                const next = (parseFloat(input.value) || 0) + 1;
                if (next > max) {
                    window.Scanner.beep(false);
                    window.Toast('Only ' + max + ' can be returned for this product.', 'warning');
                    return;
                }
                input.value = next;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                row.classList.remove('row-flash');
                void row.offsetWidth;
                row.classList.add('row-flash');
                window.Scanner.beep(true);
                return;
            }

            if (form && !/^(SAL|PUR)-/i.test(code)) {
                window.Scanner.beep(false);
                window.Toast('Code ' + code + ' is not a product of this invoice.', 'warning');
                return;
            }

            // Otherwise treat it as the invoice number printed on the invoice.
            lookupForm.querySelector('[name="invoice"]').value = code.toUpperCase();
            lookupForm.submit();
        });
    }

    if (!form) {
        return;
    }

    function recalculate() {
        let quantity = 0;
        let total = 0;

        form.querySelectorAll('tr[data-codes]').forEach(function (row) {
            const qty = parseFloat(row.querySelector('[data-role="qty"]').value) || 0;
            const line = qty * parseFloat(row.dataset.price);
            quantity += qty;
            total += line;
            row.querySelector('[data-role="total"]').textContent = line.toFixed(2);
        });

        document.getElementById('return-qty').textContent = Math.round(quantity * 1000) / 1000;
        document.getElementById('return-total').textContent = total.toFixed(2);
        form.querySelector('[name="refund_amount"]').max = total.toFixed(2);
    }

    form.addEventListener('input', recalculate);

    form.addEventListener('click', function (event) {
        if (event.target.closest('#refund-full')) {
            form.querySelector('[name="refund_amount"]').value = document.getElementById('return-total').textContent;
        }
    });

    form.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.tagName === 'INPUT' && !event.defaultPrevented) {
            event.preventDefault();
        }
    });

    form.addEventListener('form:validate', function (event) {
        const any = Array.prototype.some.call(form.querySelectorAll('[data-role="qty"]'), function (input) {
            return parseFloat(input.value) > 0;
        });
        if (!any) {
            event.preventDefault();
            window.Toast('Enter a return quantity for at least one product.', 'warning');
        }
    });

    recalculate();
})();
