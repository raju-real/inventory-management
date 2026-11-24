/*
 * Shared by every page: form validation, and the .btn-submit class.
 * A .btn-submit button or link is disabled and says "Please wait..." while its action runs.
 * It reacts to the form's submit event, after validation, so a form that fails
 * validation keeps a working button.
 */
(function () {
    'use strict';

    const BUSY_LABEL = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Please wait...';

    function setBusy(element, busy) {
        const isLink = element.tagName === 'A';

        if (busy && element.dataset.label === undefined) {
            element.dataset.label = element.innerHTML;
            element.innerHTML = BUSY_LABEL;
            if (isLink) {
                element.classList.add('disabled');
                element.setAttribute('aria-disabled', 'true');
            } else {
                element.disabled = true;
            }
        } else if (!busy && element.dataset.label !== undefined) {
            element.innerHTML = element.dataset.label;
            delete element.dataset.label;
            if (isLink) {
                element.classList.remove('disabled');
                element.removeAttribute('aria-disabled');
            } else {
                element.disabled = false;
            }
        }
    }

    window.setSubmitting = function (form, busy) {
        form.querySelectorAll('.btn-submit').forEach(function (button) { setBusy(button, busy); });
    };

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (event.defaultPrevented) {
            return;
        }

        if (form.classList.contains('needs-validation')) {
            form.querySelectorAll('[data-match]').forEach(function (input) {
                const other = form.querySelector(input.dataset.match);
                input.setCustomValidity(other && other.value !== input.value ? 'The passwords do not match.' : '');
            });

            const customOk = form.dispatchEvent(new CustomEvent('form:validate', { cancelable: true, bubbles: true }));

            if (!form.checkValidity() || !customOk) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
                return;
            }
        }

        delete form.dataset.dirty;
        window.setSubmitting(form, true);
    });

    // A form someone has typed into, so leaving by a shortcut can ask first.
    document.addEventListener('input', function (event) {
        const form = event.target.form;
        if (form && form.method.toLowerCase() === 'post') {
            form.dataset.dirty = '1';
        }
    });

    // Add and New links. Ctrl+click and the like open a new tab, so this page stays as it is.
    document.addEventListener('click', function (event) {
        const link = event.target.closest('a.btn-submit');
        if (link && event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && link.target !== '_blank') {
            setBusy(link, true);
        }
    });

    // The Back button can restore a page exactly as it was left, with its buttons still disabled.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            document.querySelectorAll('.btn-submit[data-label]').forEach(function (element) { setBusy(element, false); });
        }
    });
})();
