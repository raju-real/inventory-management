<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Labels - {{ $product->name }}</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <style>
        body { background: #fff; }
        .sheet { display: flex; flex-wrap: wrap; gap: 3mm; padding: 16px; }
        .label { width: 50mm; height: 30mm; background: #fff; border: 1px dashed #cbd5e1; padding: 1.5mm; text-align: center; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: Arial, sans-serif; page-break-inside: avoid; }
        .label .name { font-size: 8pt; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; }
        .label .bars svg { max-width: 46mm; height: 12mm; }
        .label .code { font-size: 7.5pt; letter-spacing: .5px; }
        .label .price { font-size: 9pt; font-weight: 700; }
        @media print { .no-print { display: none !important; } body { background: #fff; } .sheet { padding: 0; } .label { border: 0; } }
    </style>
</head>
<body>
<form class="no-print card card-body m-3" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-5"><strong>{{ $product->name }}</strong><div class="small text-muted">Code on label: {{ $product->label_code }}</div></div>
        <div class="col-auto">
            <label class="form-label small" for="copies">Number of labels</label>
            <input class="form-control" id="copies" type="number" name="copies" min="1" max="200" value="{{ $copies }}" placeholder="e.g. 10" required>
        </div>
        <div class="col-auto">
            <label class="form-label small" for="show_price">Price on label</label>
            <select class="form-select" id="show_price" name="show_price">
                <option value="1" @selected($showPrice)>Show price</option>
                <option value="0" @selected(! $showPrice)>Hide price</option>
            </select>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-light btn-submit" type="submit">Update</button>
            <button class="btn btn-dark" type="button" onclick="window.print()">Print</button>
            <a class="btn btn-light" href="{{ route('products.show', $product) }}">Back</a>
        </div>
    </div>
</form>
<div class="sheet">
    @for ($i = 0; $i < $copies; $i++)
        <div class="label">
            <div class="name">{{ $product->name }}</div>
            <div class="bars">{!! $barcodeSvg !!}</div>
            <div class="code">{{ $product->label_code }}</div>
            @if ($showPrice)<div class="price">{{ money($product->sales_price) }}</div>@endif
        </div>
    @endfor
</div>
<script src="{{ asset('assets/js/forms.js') }}"></script>
</body>
</html>
