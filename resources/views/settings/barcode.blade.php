@extends('layouts.app')
@section('title', 'Barcode Settings')
@section('content')
<x-page-header title="Barcode Settings" sub="Plug in a USB scanner and it works. These settings are only needed if a scan is not detected." />
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-header">Scanner settings</div><div class="card-body">
            <form method="POST" action="{{ route('barcode.settings.update') }}" class="needs-validation" novalidate>
                @csrf @method('PUT')
                <x-select name="barcode_enabled" label="Scanner detection" :options="[1 => 'On', 0 => 'Off']" :placeholder="false" :value="setting('barcode_enabled', '1')" required />
                <x-select name="barcode_suffix" label="Suffix key (sent after the code)" :options="['enter' => 'Enter (most scanners)', 'tab' => 'Tab', 'none' => 'None']" :placeholder="false" :value="setting('barcode_suffix', 'enter')" required help="Use the test panel to see what your scanner sends." />
                <div class="row">
                    <div class="col-6"><x-input name="barcode_min_length" type="number" label="Minimum code length" placeholder="e.g. 4" :value="setting('barcode_min_length', 4)" required min="1" max="30" /></div>
                    <div class="col-6"><x-input name="barcode_max_interval" type="number" label="Max key gap (ms)" placeholder="e.g. 50" :value="setting('barcode_max_interval', 50)" required min="10" max="300" /></div>
                </div>
                <div class="form-text mb-3">Keys arriving closer together than the key gap count as a scan. Slower wireless scanners may need 80 to 150 ms.</div>
                <x-select name="barcode_beep" label="Beep on scan" :options="[1 => 'On', 0 => 'Off']" :placeholder="false" :value="setting('barcode_beep', '1')" required />
                <div class="form-actions"><button class="btn btn-primary btn-submit" type="submit">Save Settings</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header">Test your scanner</div>
            <div class="card-body">
                <p class="small text-muted">Click in the box and scan any barcode. This panel measures what the scanner really sends, without saving anything.</p>
                <input class="form-control mb-2" id="scan-test" placeholder="Click here, then scan a barcode" autocomplete="off">
                <div id="scan-result" class="alert alert-light border small mb-2">Waiting for a scan...</div>
                <div id="scan-advice" class="small"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Setup guide</div>
            <div class="card-body small">
                <ol class="mb-2">
                    <li>Connect the scanner to a USB port. Windows installs it as a keyboard; no driver or software is needed.</li>
                    <li>Open Notepad and scan a barcode. The digits should appear and the cursor should move to a new line. If so, the scanner is ready.</li>
                    <li>If the cursor does not move to a new line, either set the scanner to send a CR/Enter suffix using the configuration barcodes in its manual, or choose <strong>None</strong> above.</li>
                    <li>Keep the scanner in <strong>USB HID Keyboard</strong> mode and the computer on an English keyboard layout, because a Bangla layout changes the characters received.</li>
                </ol>
                <div class="text-muted">Products without a printed barcode can use the labels from Products &gt; Print Labels. SKU codes are recognised as well as barcodes.</div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    (function () {
        const input = document.getElementById('scan-test');
        const result = document.getElementById('scan-result');
        const advice = document.getElementById('scan-advice');
        let keys = [];
        let code = '';
        let timer = null;

        function report(suffix) {
            if (keys.length < 2) {
                keys = [];
                code = '';
                return;
            }
            const gaps = keys.slice(1).map(function (time, index) { return time - keys[index]; });
            const slowest = Math.ceil(Math.max.apply(null, gaps));
            const notes = [];

            result.className = 'alert alert-success small mb-2';
            result.textContent = 'Code: ' + code + ' | Length: ' + code.length + ' | Slowest key gap: ' + slowest + ' ms | Suffix: ' + suffix;

            if (slowest > window.APP.scanner.maxInterval) {
                notes.push('Your scanner is slower than the current key gap. Set Max key gap to ' + (slowest + 20) + ' ms.');
            }
            if (suffix !== window.APP.scanner.suffix) {
                notes.push('Set Suffix key to "' + suffix + '" to match this scanner.');
            }
            if (code.length < window.APP.scanner.minLength) {
                notes.push('This code is shorter than the minimum length. Lower Minimum code length to ' + code.length + '.');
            }
            advice.textContent = notes.length ? notes.join(' ') : 'These settings match your scanner.';
            advice.className = 'small ' + (notes.length ? 'text-danger' : 'text-success');
            input.value = '';
            keys = [];
            code = '';
        }

        // Listening on window runs before scanner.js, which takes the Enter key for itself.
        window.addEventListener('keydown', function (event) {
            if (event.target !== input) {
                return;
            }
            clearTimeout(timer);
            if (event.key.length === 1) {
                keys.push(event.timeStamp);
                code += event.key;
                timer = setTimeout(function () { report('none'); }, 400);
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                report(event.key.toLowerCase());
            }
        }, true);

        // This page measures scans itself. A handler is needed so the scanner does not submit anything.
        window.Scanner.on(function () { window.Scanner.beep(true); });
    })();
</script>
@endpush
