<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: freesans; font-size: {{ $width === 58 ? '7.5pt' : '8.5pt' }}; color: #000; }
    table { border-collapse: collapse; width: 100%; }
    td { padding: 0.6mm 0; vertical-align: top; }
    .center { text-align: center; }
    .right { text-align: right; }
    .shop { font-size: {{ $width === 58 ? '11pt' : '13pt' }}; }
    .small { font-size: {{ $width === 58 ? '6.5pt' : '7.5pt' }}; }
    .rule { border-top: 0.3mm dashed #000; margin: 1.8mm 0; height: 0; }
    .item { padding-top: 1.2mm; }
    .grand td { font-size: {{ $width === 58 ? '10pt' : '11.5pt' }}; font-weight: bold; padding-top: 1.2mm; }
    .symbol { font-weight: normal; }
    .cancelled { border: 0.4mm solid #000; text-align: center; font-weight: bold; padding: 1mm; margin: 1.5mm 0; }
</style>
</head>
<body>
{{-- Bold text in this font has no Taka sign and no Bangla letters, so names and currency symbols stay regular weight. --}}
@php
    $symbol = e(setting('currency_symbol', ''));
    $amount = fn ($value) => '<span class="symbol">' . $symbol . '</span> ' . number_format((float) $value, 2);
    $methods = $sale->payments->where('status', 'posted')->pluck('method')->unique()->map(fn ($method) => ucfirst($method))->join(', ');
@endphp

@if ($logoPath)
    <div class="center"><img src="{{ $logoPath }}" style="max-height: 14mm; max-width: 40mm;"></div>
@endif
<div class="center shop">{{ setting('site_name', config('app.name')) }}</div>
@if (setting('site_address'))<div class="center small">{{ setting('site_address') }}</div>@endif
@if (setting('site_phone') || setting('site_email'))<div class="center small">{{ collect([setting('site_phone'), setting('site_email')])->filter()->join(' | ') }}</div>@endif

<div class="rule"></div>
<table>
    <tr><td>Invoice</td><td class="right"><b>{{ $sale->reference_no }}</b></td></tr>
    <tr><td>Date</td><td class="right">{{ $sale->sale_date->format('d M Y') }} {{ $sale->created_at?->format('h:i A') }}</td></tr>
    <tr><td>Customer</td><td class="right">{{ $sale->customer?->name }}</td></tr>
    @if ($sale->customer?->mobile)<tr><td>Mobile</td><td class="right">{{ $sale->customer->mobile }}</td></tr>@endif
    <tr><td>Served by</td><td class="right">{{ $sale->creator?->name }}</td></tr>
</table>

@if ($sale->isCancelled())
    <div class="cancelled">CANCELLED</div>
@endif

<div class="rule"></div>
<table>
    @foreach ($sale->details as $detail)
        <tr><td colspan="2" class="item">{{ $detail->product?->name }}</td></tr>
        <tr>
            <td class="small">{{ qty($detail->quantity) }} {{ $detail->product?->unit?->short_name }} x {{ number_format($detail->unit_price, 2) }}</td>
            <td class="right">{{ number_format($detail->total, 2) }}</td>
        </tr>
    @endforeach
</table>

<div class="rule"></div>
<table>
    <tr><td>Items / quantity</td><td class="right">{{ $sale->details->count() }} / {{ qty($sale->total_quantity) }}</td></tr>
    <tr><td>Subtotal</td><td class="right">{!! $amount($sale->subtotal) !!}</td></tr>
    @if ((float) $sale->discount > 0)<tr><td>Discount</td><td class="right">- {!! $amount($sale->discount) !!}</td></tr>@endif
    <tr class="grand"><td>TOTAL</td><td class="right">{!! $amount($sale->grand_total) !!}</td></tr>
    <tr><td>Paid{{ $methods ? ' (' . $methods . ')' : '' }}</td><td class="right">{!! $amount($sale->paid_amount) !!}</td></tr>
    @if ((float) $sale->returned_amount > 0)<tr><td>Returned</td><td class="right">{!! $amount($sale->returned_amount) !!}</td></tr>@endif
    <tr><td><b>Due</b></td><td class="right"><b>{!! $amount($sale->due_amount) !!}</b></td></tr>
</table>

<div class="rule"></div>
<div class="center"><barcode code="{{ $sale->reference_no }}" type="C128A" size="{{ $width === 58 ? '0.62' : '0.8' }}" height="0.7" /></div>
@if (setting('invoice_footer'))<div class="center small" style="margin-top: 1.5mm;">{{ setting('invoice_footer') }}</div>@endif
<div class="center small" style="margin-top: 1mm;">Printed {{ now()->format('d M Y h:i A') }}</div>
</body>
</html>
