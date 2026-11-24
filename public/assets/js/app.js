/* Shared admin panel behaviour: modals, toasts, datepickers, sidebar. */
(function () {
    'use strict';

    function isDesktop() {
        return window.matchMedia('(min-width: 992px)').matches;
    }

    function isRail() {
        return isDesktop() && document.documentElement.classList.contains('sidebar-collapsed');
    }

    // Icon rail: keep each flyout next to its icon and inside the window.
    function placeFlyout(item) {
        const flyout = item.querySelector(':scope > .side-sub, :scope > a.side-link > span');
        if (!flyout || !isRail()) {
            return;
        }
        const top = item.getBoundingClientRect().top;
        flyout.style.top = Math.max(8, Math.min(top, window.innerHeight - flyout.offsetHeight - 8)) + 'px';
    }

    document.querySelectorAll('.side-item').forEach(function (item) {
        item.addEventListener('mouseenter', function () { placeFlyout(item); });
        item.addEventListener('focusin', function () { placeFlyout(item); });
    });

    // On the icon rail a group opens by hover, so its button must not expand inline.
    const sidebarNav = document.querySelector('.sidebar-nav');
    if (sidebarNav) {
        sidebarNav.addEventListener('click', function (event) {
            if (isRail() && event.target.closest('[data-bs-toggle="collapse"]')) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }

    jQuery('[data-datepicker]').each(function () {
        jQuery(this).datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            todayBtn: 'linked',
            clearBtn: !this.required,
            orientation: 'bottom auto',
        });
    });

    // Tooltips: every .btn with a title shows it, but only while the button shows nothing but an icon.
    // Shortcut buttons always show theirs, because the key is not written on the button.
    new bootstrap.Tooltip(document.body, {
        selector: '.btn[title], .btn[data-bs-original-title]',
        trigger: 'hover',
        container: 'body',
    });

    function visibleText(element) {
        let text = '';
        const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) {
            const parent = walker.currentNode.parentElement;
            if (parent.closest('.badge') || parent.getClientRects().length === 0) {
                continue;
            }
            text += walker.currentNode.textContent;
        }
        return text.trim();
    }

    document.addEventListener('show.bs.tooltip', function (event) {
        if (!event.target.hasAttribute('data-shortcut') && visibleText(event.target) !== '') {
            event.preventDefault();
        }
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.btn');
        const tooltip = button && bootstrap.Tooltip.getInstance(button);
        if (tooltip) {
            tooltip.hide();
        }
    });

    // Keyboard shortcuts come from the top bar links (data-shortcut="F4").
    // A user without the link keeps the key's normal meaning, so F5 still refreshes for them.
    document.addEventListener('keydown', function (event) {
        if (event.ctrlKey || event.altKey || event.metaKey || event.shiftKey) {
            return;
        }
        const link = document.querySelector('.topbar a[data-shortcut="' + event.key + '"]');
        if (!link) {
            return;
        }
        event.preventDefault();

        if (event.repeat || document.body.classList.contains('modal-open')) {
            return;
        }
        if (new URL(link.href).pathname === window.location.pathname) {
            const search = document.getElementById('product-search');
            if (search) {
                search.focus();
            }
            return;
        }

        const unsaved = Array.prototype.some.call(document.querySelectorAll('form[data-dirty]'), function (form) {
            return !form.closest('.modal');
        });
        if (unsaved && !window.confirm('Leave this page? Changes you have not saved will be lost.')) {
            return;
        }

        link.click();
    });

    window.Toast = function (message, type) {
        const area = document.querySelector('.toast-area');
        const box = document.createElement('div');
        box.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show shadow-sm mb-2';
        box.setAttribute('role', 'alert');
        box.textContent = message;
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close';
        close.setAttribute('data-bs-dismiss', 'alert');
        box.appendChild(close);
        area.appendChild(box);
        setTimeout(function () { box.remove(); }, 5000);
    };

    document.querySelectorAll('.toast-area .alert[data-autohide]').forEach(function (el) {
        setTimeout(function () { el.remove(); }, 6000);
    });

    // Image inputs: size check and preview.
    document.addEventListener('change', function (event) {
        const input = event.target;
        if (input.type !== 'file') {
            return;
        }
        const file = input.files[0];
        const maxKb = parseInt(input.dataset.maxKb || '0', 10);
        input.setCustomValidity(file && maxKb && file.size > maxKb * 1024 ? 'File must be smaller than ' + maxKb + ' KB.' : '');
        if (!input.checkValidity()) {
            input.reportValidity();
            return;
        }
        const preview = document.getElementById(input.dataset.preview || '');
        if (preview && file && file.type.indexOf('image/') === 0) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        }
    });

    // Shared modals opened by [data-delete-url] and [data-cancel-url] buttons.
    document.addEventListener('click', function (event) {
        const del = event.target.closest('[data-delete-url]');
        if (del) {
            const modal = document.getElementById('deleteModal');
            modal.querySelector('form').action = del.dataset.deleteUrl;
            modal.querySelector('[data-name]').textContent = del.dataset.name || 'this record';
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }

        const cancel = event.target.closest('[data-cancel-url]');
        if (cancel) {
            const modal = document.getElementById('cancelModal');
            const form = modal.querySelector('form');
            form.action = cancel.dataset.cancelUrl;
            form.classList.remove('was-validated');
            form.reset();
            modal.querySelector('[data-name]').textContent = cancel.dataset.name || 'this document';
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }

        if (event.target.closest('[data-sidebar-toggle]')) {
            if (isDesktop()) {
                const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
                try {
                    localStorage.setItem(document.documentElement.dataset.sidebarKey, collapsed ? '1' : '0');
                } catch (e) { /* the toggle still works for this page */ }
            } else {
                document.body.classList.toggle('sidebar-open');
            }
        }
        if (event.target.closest('.sidebar-backdrop')) {
            document.body.classList.remove('sidebar-open');
        }
    });
})();
