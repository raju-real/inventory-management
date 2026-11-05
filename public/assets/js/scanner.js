/*
 * USB / Bluetooth barcode scanners work as a keyboard: they "type" the code very fast
 * and usually finish with Enter. No driver is needed. This file tells a scan apart from
 * human typing by the speed of the keystrokes, wherever the cursor happens to be, and
 * passes the code to the handler of the current page.
 */
(function () {
    'use strict';

    const config = window.APP.scanner;
    const handlers = [];
    let audio = null;
    let buffer = '';
    let lastKeyAt = 0;
    let startedOn = null;
    let snapshot = null;
    let timer = null;

    function reset() {
        buffer = '';
        startedOn = null;
        snapshot = null;
        clearTimeout(timer);
    }

    function popupOpen() {
        return document.body.classList.contains('modal-open');
    }

    function isField(el) {
        return el && ['INPUT', 'TEXTAREA', 'SELECT'].indexOf(el.tagName) !== -1;
    }

    function beep(ok) {
        if (!config.beep) {
            return;
        }
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            const osc = audio.createOscillator();
            const gain = audio.createGain();
            osc.frequency.value = ok ? 1200 : 240;
            gain.gain.value = 0.08;
            osc.connect(gain);
            gain.connect(audio.destination);
            osc.start();
            osc.stop(audio.currentTime + (ok ? 0.09 : 0.3));
        } catch (e) { /* sound is optional */ }
    }

    function flash() {
        const status = document.querySelector('.scan-status');
        if (status) {
            status.classList.add('flash');
            setTimeout(function () { status.classList.remove('flash'); }, 500);
        }
    }

    function finish() {
        const code = buffer.trim();
        const target = startedOn;
        const before = snapshot;
        reset();

        if (code.length < config.minLength || (!handlers.length && !popupOpen())) {
            return false;
        }

        // Take the scanned characters back out of whichever field had the cursor.
        if (isField(target) && target.value !== before && target.dataset.scanKeep === undefined) {
            target.value = before;
            target.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // A scan cannot be meant for a popup's form, and must not reach the page behind it either.
        if (popupOpen()) {
            beep(false);
            return true;
        }

        flash();
        handlers.forEach(function (handler) { handler(code); });
        return true;
    }

    document.addEventListener('keydown', function (event) {
        if (!config.enabled || event.ctrlKey || event.altKey || event.metaKey) {
            return;
        }

        // timeStamp is when the key arrived, so a busy page cannot make a fast scan look like typing.
        const now = event.timeStamp;
        const gap = now - lastKeyAt;

        if (event.key.length === 1) {
            if (buffer && gap > config.maxInterval) {
                reset();
            }
            if (!buffer) {
                startedOn = document.activeElement;
                snapshot = isField(startedOn) ? startedOn.value : null;
            }
            buffer += event.key;
            lastKeyAt = now;

            if (config.suffix === 'none') {
                clearTimeout(timer);
                timer = setTimeout(finish, Math.max(config.maxInterval * 3, 120));
            }
            return;
        }

        const isSuffix = (config.suffix === 'enter' && event.key === 'Enter') || (config.suffix === 'tab' && event.key === 'Tab');

        if (isSuffix && buffer) {
            if (buffer.length >= config.minLength && gap <= config.maxInterval * 2 && (handlers.length || popupOpen())) {
                // The Enter belongs to the scanner: it must not submit the form.
                event.preventDefault();
                event.stopPropagation();
                finish();
            } else {
                reset();
            }
        } else if (event.key !== 'Shift' && event.key !== 'CapsLock') {
            reset();
        }
    }, true);

    window.Scanner = {
        on: function (handler) { handlers.push(handler); },
        beep: beep,
    };

    // Lists with a search box: a scan searches for that code.
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[data-scan-search]');
        if (form && !handlers.length) {
            window.Scanner.on(function (code) {
                beep(true);
                form.querySelector('[name="search"]').value = code;
                form.submit();
            });
        }
    });
})();
