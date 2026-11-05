/* Product search box used by purchase, sale, damage and adjustment screens. */
(function () {
    'use strict';

    window.ProductPicker = function (options) {
        const input = options.input;
        const context = options.context;
        const box = input.closest('.picker');
        const menu = document.createElement('div');
        menu.className = 'picker-menu d-none';
        box.appendChild(menu);

        let results = [];
        let active = -1;
        let timer = null;
        let requestId = 0;

        function url(base, params) {
            const query = new URLSearchParams(Object.assign({ context: context }, params));
            return base + '?' + query.toString();
        }

        function getJson(address) {
            return fetch(address, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        }

        function hide() {
            menu.classList.add('d-none');
            results = [];
            active = -1;
        }

        function render() {
            menu.innerHTML = '';
            if (!results.length) {
                const empty = document.createElement('div');
                empty.className = 'picker-item text-muted';
                empty.textContent = 'No matching product';
                menu.appendChild(empty);
            }
            results.forEach(function (product, index) {
                const item = document.createElement('div');
                item.className = 'picker-item' + (index === active ? ' active' : '');
                const left = document.createElement('div');
                const name = document.createElement('div');
                name.textContent = product.name;
                const code = document.createElement('small');
                code.textContent = product.sku + (product.barcode ? ' · ' + product.barcode : '');
                left.append(name, code);
                const right = document.createElement('small');
                right.className = 'text-end text-nowrap';
                right.textContent = 'Stock: ' + product.available + ' ' + (product.unit || '');
                item.append(left, right);
                item.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    choose(product, false);
                });
                menu.appendChild(item);
            });
            menu.classList.remove('d-none');
        }

        function choose(product, fromScan) {
            hide();
            input.value = '';
            options.onPick(product, fromScan);
        }

        function search(term) {
            const id = ++requestId;
            getJson(url(options.searchUrl, { q: term }))
                .then(function (response) { return response.ok ? response.json() : []; })
                .then(function (data) {
                    if (id !== requestId || input.value.trim() === '') {
                        return;
                    }
                    results = data;
                    active = data.length ? 0 : -1;
                    render();
                })
                .catch(function () { hide(); });
        }

        function lookup(code, fromScan) {
            requestId++;
            clearTimeout(timer);
            return getJson(url(options.lookupUrl, { code: code }))
                .then(function (response) {
                    if (response.ok) {
                        return response.json().then(function (product) {
                            choose(product, fromScan);
                            return true;
                        });
                    }
                    return false;
                })
                .catch(function () {
                    window.Toast('Could not reach the server. Check your connection.', 'danger');
                    return null;
                });
        }

        function notFound(code) {
            window.Scanner.beep(false);
            hide();
            input.value = '';
            if (options.onNotFound) {
                options.onNotFound(code);
            } else {
                window.Toast('No product found for code ' + code + '.', 'warning');
            }
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            const term = input.value.trim();
            if (!term) {
                requestId++;
                hide();
                return;
            }
            timer = setTimeout(function () { search(term); }, 220);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (results.length) {
                    active = (active + (event.key === 'ArrowDown' ? 1 : -1) + results.length) % results.length;
                    render();
                }
            } else if (event.key === 'Enter') {
                // Never submit the invoice from the search box.
                event.preventDefault();
                const term = input.value.trim();
                if (active >= 0 && results[active]) {
                    choose(results[active], false);
                } else if (term) {
                    lookup(term, false).then(function (found) {
                        if (found === false) {
                            notFound(term);
                        }
                    });
                }
            } else if (event.key === 'Escape') {
                hide();
            }
        });

        input.addEventListener('blur', function () { setTimeout(hide, 120); });

        window.Scanner.on(function (code) {
            hide();
            input.value = '';
            lookup(code, true).then(function (found) {
                if (found === false) {
                    notFound(code);
                }
            });
        });
    };
})();
