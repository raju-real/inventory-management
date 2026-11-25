<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Labels - {{ $product->name }}</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    <style>
        body { background: #f1f5f9; }
        .label-toolbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: .75rem 1.25rem; position: sticky; top: 0; z-index: 5; }
        .label-options .form-check { margin-bottom: .35rem; }
        .option-title { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin: 1rem 0 .5rem; }
        .option-title:first-child { margin-top: 0; }

        /* Preview: a sheet of paper with the labels on it. */
        .paper { background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(15, 23, 42, .12); padding: 8mm; min-height: 60vh; }
        .sheet { display: flex; flex-wrap: wrap; gap: 2mm; align-content: flex-start; }

        /* One label. Size comes from --w / --h, set by the size option. */
        .label {
            --w: 50mm; --h: 30mm; --name: 7.5pt; --price: 10pt; --bars: 11mm;
            width: var(--w); height: var(--h); padding: 1.2mm 1.5mm; box-sizing: border-box;
            border: 1px dashed #cbd5e1; border-radius: 1.5mm; background: #fff; color: #000;
            display: flex; flex-direction: column; align-items: center; justify-content: space-between;
            text-align: center; overflow: hidden; font-family: Arial, Helvetica, sans-serif; line-height: 1.15;
            break-inside: avoid; page-break-inside: avoid;
        }
        .label .shop { font-size: 5.5pt; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #334155; }
        .label .name { font-size: var(--name); font-weight: 700; width: 100%; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .label .bars { width: 100%; height: var(--bars); display: flex; justify-content: center; }
        .label .bars svg { width: 100%; height: 100%; }
        .label .code { font-family: 'Courier New', monospace; font-size: 6.5pt; letter-spacing: 1px; }
        .label .price { font-size: var(--price); font-weight: 800; }
        .label .price small { font-size: .6em; font-weight: 600; }

        .size-38x25 .label { --w: 38mm; --h: 25mm; --name: 6.5pt; --price: 8.5pt; --bars: 9mm; }
        .size-50x25 .label { --w: 50mm; --h: 25mm; --name: 7pt; --price: 9pt; --bars: 9mm; }
        .size-60x40 .label { --w: 60mm; --h: 40mm; --name: 9pt; --price: 12pt; --bars: 15mm; }

        .hide-price .label .price, .hide-name .label .name, .hide-shop .label .shop { display: none; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .print-area { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            .paper { box-shadow: none; padding: 0; min-height: 0; border-radius: 0; }
            .label { border: 0; border-radius: 0; }
            .layout-roll .sheet { display: block; }
            .layout-roll .label { break-after: page; page-break-after: always; }
            .layout-roll .label:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>
    <style id="page-rule">@page { size: A4; margin: 8mm; }</style>
</head>
<body>
@php
    $shop = setting('site_name', config('app.name'));
    $classes = 'size-' . $size . ' layout-' . $layout . ($showPrice ? '' : ' hide-price') . ($showName ? '' : ' hide-name') . ($showShop ? '' : ' hide-shop');
@endphp
<div class="label-toolbar no-print d-flex flex-wrap align-items-center gap-2">
    <div class="me-auto">
        <div class="fw-semibold"><i class="bi bi-upc-scan me-1 text-primary"></i>Barcode labels</div>
        <div class="small text-muted">{{ $product->name }} &middot; {{ $product->label_code }}</div>
    </div>
    <a class="btn btn-light" href="{{ route('products.show', $product) }}"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <button class="btn btn-primary" type="button" id="print-labels" title="Print (Ctrl+P)"><i class="bi bi-printer me-1"></i>Print <span id="print-count">{{ $copies }}</span> labels</button>
</div>

<div class="container-fluid py-3 print-area">
    <div class="row g-3">
        <div class="col-lg-3 no-print">
            {{-- Works without JavaScript too: Apply reloads the page with these options. --}}
            <form class="card card-body label-options" method="GET" id="label-options">
                <div class="option-title">Quantity</div>
                <label class="form-label small" for="copies">Number of labels</label>
                <div class="input-group">
                    <button class="btn btn-outline-secondary" type="button" data-step="-1" aria-label="One less"><i class="bi bi-dash"></i></button>
                    <input class="form-control text-center" id="copies" type="number" name="copies" min="1" max="200" value="{{ $copies }}" placeholder="e.g. 10" required>
                    <button class="btn btn-outline-secondary" type="button" data-step="1" aria-label="One more"><i class="bi bi-plus"></i></button>
                </div>
                <div class="form-text">Up to 200 at a time.</div>

                <div class="option-title">Label</div>
                <label class="form-label small" for="size">Label size</label>
                <select class="form-select mb-2" id="size" name="size">
                    @foreach ($sizes as $key => $text)
                        <option value="{{ $key }}" @selected($size === $key)>{{ $text }}</option>
                    @endforeach
                </select>
                <label class="form-label small" for="layout">Printer</label>
                <select class="form-select" id="layout" name="layout">
                    <option value="sheet" @selected($layout === 'sheet')>A4 sheet (normal printer)</option>
                    <option value="roll" @selected($layout === 'roll')>Label roll (one label per page)</option>
                </select>

                <div class="option-title">Show on label</div>
                @foreach (['show_shop' => ['Shop name', $showShop], 'show_name' => ['Product name', $showName], 'show_price' => ['Price', $showPrice]] as $key => [$text, $on])
                    <input type="hidden" name="{{ $key }}" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $key }}" name="{{ $key }}" value="1" @checked($on)>
                        <label class="form-check-label" for="{{ $key }}">{{ $text }}</label>
                    </div>
                @endforeach
                <noscript><button class="btn btn-light mt-2 btn-submit" type="submit">Apply</button></noscript>
                <div class="form-text mt-2">Changes show at once in the preview.</div>
            </form>
        </div>

        <div class="col-lg-9">
            <div class="paper">
                <div class="sheet {{ $classes }}" id="sheet">
                    @for ($i = 0; $i < $copies; $i++)
                        <div class="label">
                            <div class="shop">{{ $shop }}</div>
                            <div class="name">{{ $product->name }}</div>
                            <div class="bars">{!! $barcodeSvg !!}</div>
                            <div class="code">{{ $product->label_code }}</div>
                            <div class="price"><small>Price </small>{{ money($product->sales_price) }}</div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    const form = document.getElementById('label-options');
    const sheet = document.getElementById('sheet');
    const copies = document.getElementById('copies');
    const pageRule = document.getElementById('page-rule');
    const template = sheet.querySelector('.label').cloneNode(true);

    // The barcode keeps its shape whatever the label size.
    sheet.querySelectorAll('.bars svg').forEach(function (svg) { svg.setAttribute('preserveAspectRatio', 'none'); });
    template.querySelector('.bars svg').setAttribute('preserveAspectRatio', 'none');

    function count() {
        return Math.min(200, Math.max(1, parseInt(copies.value, 10) || 1));
    }

    function apply() {
        const size = form.size.value;
        const layout = form.layout.value;
        sheet.className = 'sheet size-' + size + ' layout-' + layout;
        ['price', 'name', 'shop'].forEach(function (part) {
            sheet.classList.toggle('hide-' + part, !document.getElementById('show_' + part).checked);
        });

        const wanted = count();
        while (sheet.children.length < wanted) { sheet.appendChild(template.cloneNode(true)); }
        while (sheet.children.length > wanted) { sheet.lastElementChild.remove(); }
        document.getElementById('print-count').textContent = wanted;

        // A roll prints each label on its own page, the size of the label.
        const [w, h] = size.split('x');
        pageRule.textContent = layout === 'roll' ? '@page { size: ' + w + 'mm ' + h + 'mm; margin: 0; }' : '@page { size: A4; margin: 8mm; }';

        // Keep the choices in the address, so a reload or bookmark keeps them.
        const params = new URLSearchParams({ copies: wanted, size: size, layout: layout });
        ['price', 'name', 'shop'].forEach(function (part) { params.set('show_' + part, document.getElementById('show_' + part).checked ? 1 : 0); });
        history.replaceState(null, '', location.pathname + '?' + params.toString());
    }

    form.addEventListener('input', apply);
    form.addEventListener('change', apply);
    form.addEventListener('submit', function (event) { event.preventDefault(); apply(); });
    form.querySelectorAll('[data-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            copies.value = Math.min(200, Math.max(1, count() + parseInt(button.dataset.step, 10)));
            apply();
        });
    });
    copies.addEventListener('blur', function () { copies.value = count(); });
    document.getElementById('print-labels').addEventListener('click', function () { apply(); window.print(); });
    apply();
})();
</script>
</body>
</html>
