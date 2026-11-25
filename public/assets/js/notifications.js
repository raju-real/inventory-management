/* The bell: unseen count that refreshes by itself, latest alerts when opened, and read/unread toggles. */
(function () {
    'use strict';

    const root = document.querySelector('.notify');
    if (!root) {
        return;
    }

    const badge = root.querySelector('.notify-count');
    const headCount = root.querySelector('.notify-head-count');
    const list = root.querySelector('[data-notify-list]');
    const readAll = root.querySelector('[data-notify-read-all]');
    const feedUrl = root.dataset.feedUrl;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const REFRESH_MS = 60000;
    // Bumped by every read/unread change, so a refresh that started before it cannot undo it on screen.
    let changes = 0;

    function setCount(count) {
        count = Number(count) || 0;
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.classList.toggle('d-none', count === 0);
        headCount.textContent = count ? '(' + count + ' new)' : '';
        readAll.classList.toggle('d-none', count === 0);
    }
    setCount(badge.classList.contains('d-none') ? 0 : parseInt(badge.textContent, 10));

    function request(url, options) {
        return fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
        }, options || {})).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    function load() {
        const seen = changes;
        return request(feedUrl).then(function (data) {
            if (seen !== changes) {
                return;
            }
            list.innerHTML = data.html;
            setCount(data.count);
        }).catch(function () {
            list.innerHTML = '<div class="notify-empty text-danger">Could not load notifications. Try again.</div>';
        });
    }

    function refreshCount() {
        if (document.hidden) {
            return;
        }
        const open = root.querySelector('.dropdown-menu.show');
        const seen = changes;
        (open ? load() : request(feedUrl + '?count_only=1').then(function (data) {
            if (seen === changes) {
                setCount(data.count);
            }
        })).catch(function () {});
    }

    root.addEventListener('show.bs.dropdown', function () {
        // The bell's tooltip would sit on top of the open menu.
        const tip = window.bootstrap && bootstrap.Tooltip.getInstance(root.querySelector('[data-bs-toggle="dropdown"]'));
        if (tip) {
            tip.hide();
        }
        load();
    });
    setInterval(refreshCount, REFRESH_MS);
    document.addEventListener('visibilitychange', refreshCount);

    // Seen / unseen toggle for one alert, without closing the menu.
    list.addEventListener('click', function (event) {
        const button = event.target.closest('.notify-toggle');
        if (!button) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        button.disabled = true;
        changes++;
        request(button.dataset.toggleUrl, { method: 'POST' }).then(function (data) {
            const item = button.closest('.notify-item');
            item.classList.toggle('is-read', data.read);
            item.classList.toggle('is-unread', !data.read);
            const label = data.read ? 'Mark as unread' : 'Mark as read';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            const tip = window.bootstrap && bootstrap.Tooltip.getInstance(button);
            if (tip) {
                tip.setContent({ '.tooltip-inner': label });
            }
            setCount(data.count);
        }).catch(function () {
            window.Toast && window.Toast('Could not update the notification.', 'danger');
        }).finally(function () {
            button.disabled = false;
        });
    });

    readAll.addEventListener('click', function () {
        readAll.disabled = true;
        changes++;
        request(root.dataset.readAllUrl, { method: 'POST' }).then(function () {
            list.querySelectorAll('.notify-item.is-unread').forEach(function (item) {
                item.classList.replace('is-unread', 'is-read');
                const button = item.querySelector('.notify-toggle');
                button.setAttribute('aria-label', 'Mark as unread');
                button.setAttribute('title', 'Mark as unread');
            });
            setCount(0);
        }).catch(function () {
            window.Toast && window.Toast('Could not mark notifications as read.', 'danger');
        }).finally(function () {
            readAll.disabled = false;
        });
    });
})();
