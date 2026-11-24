/*
 * A popup form that adds a record without leaving the page, then selects it in a dropdown.
 * Used by the + buttons: <form data-quick-add="#customer_id" ...> inside a .modal.
 */
(function () {
    'use strict';

    document.querySelectorAll('form[data-quick-add]').forEach(function (form) {
        const select = document.querySelector(form.dataset.quickAdd);
        const modalElement = form.closest('.modal');

        function clearErrors() {
            form.classList.remove('was-validated');
            form.querySelectorAll('.is-invalid').forEach(function (input) { input.classList.remove('is-invalid'); });
        }

        function showErrors(errors) {
            Object.keys(errors).forEach(function (field) {
                const input = form.querySelector('[name="' + field + '"]');
                if (input) {
                    input.classList.add('is-invalid');
                    input.parentElement.querySelector('.invalid-feedback').textContent = errors[field][0];
                }
            });
            const first = form.querySelector('.is-invalid');
            if (first) {
                first.focus();
            }
        }

        function selectNewRecord(record) {
            jQuery(select).append(new Option(record.label, record.id, true, true)).trigger('change');

            // The choice is now valid, so a message from an earlier failed save no longer applies.
            select.classList.remove('is-invalid');
            const oldMessage = select.parentElement.querySelector('.invalid-feedback.d-block');
            if (oldMessage) {
                oldMessage.remove();
            }

            bootstrap.Modal.getOrCreateInstance(modalElement).hide();
            window.Toast(record.name + ' added and selected.', 'success');
        }

        modalElement.addEventListener('shown.bs.modal', function () {
            form.querySelector('input:not([type="hidden"])').focus();
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            delete form.dataset.dirty;
            clearErrors();
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearErrors();

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                form.querySelector(':invalid').focus();
                return;
            }

            window.setSubmitting(form, true);

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        if (response.ok) {
                            selectNewRecord(data);
                        } else if (response.status === 422) {
                            showErrors(data.errors || {});
                        } else if (response.status === 419) {
                            window.Toast('Your session has expired. Please refresh the page.', 'danger');
                        } else {
                            window.Toast('This could not be saved. Please try again.', 'danger');
                        }
                    });
                })
                .catch(function () {
                    window.Toast('Could not reach the server. Check your connection.', 'danger');
                })
                .finally(function () {
                    window.setSubmitting(form, false);
                });
        });
    });
})();
