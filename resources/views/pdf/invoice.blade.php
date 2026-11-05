<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: freesans; font-size: 10pt; color: #1e293b; }
    table { border-collapse: collapse; width: 100%; }
    .band { background-color: #0f766e; color: #ffffff; }
    .band td { padding: 26px 34px; vertical-align: middle; }
    .company { font-size: 18pt; color: #ffffff; }
    .company-line { font-size: 8.5pt; color: #ccfbf1; }
    .doc-title { font-size: 16pt; font-weight: bold; text-align: right; color: #ffffff; letter-spacing: 1px; }
    .doc-ref { font-size: 10pt; text-align: right; color: #ccfbf1; }
    .body { padding: 0 34px; }
    .meta td { padding: 22px 0 16px; vertical-align: top; }
    .label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; letter-spacing: .6px; font-weight: bold; }
    .party-name { font-size: 13pt; color: #0f172a; padding-top: 3px; }
    .muted { color: #64748b; font-size: 9pt; }
    .meta-table td { padding: 2px 0; font-size: 9pt; }
    .items th { background-color: #f1f5f9; color: #475569; font-size: 7.5pt; text-transform: uppercase; padding: 9px 8px; text-align: left; border-bottom: 1.5px solid #0f766e; }
    .items th.right { text-align: right; }
    .symbol { font-weight: normal; }
    .items th.center { text-align: center; }
    .items td { padding: 9px 8px; border-bottom: 0.5px solid #e2e8f0; font-size: 9.5pt; vertical-align: top; }
    .right { text-align: right; }
    .symbol { font-weight: normal; }
    .center { text-align: center; }
    .sku { color: #64748b; font-size: 8pt; }
    .totals td { padding: 5px 8px; font-size: 9.5pt; }
    .totals .grand td { background-color: #0f766e; color: #ffffff; font-weight: bold; font-size: 11pt; padding: 9px 8px; }
    .totals .due td { font-weight: bold; border-top: 1px solid #cbd5e1; padding-top: 8px; }
    .badge { font-size: 8.5pt; font-weight: bold; padding: 3px 10px; }
    .notes { background-color: #f8fafc; padding: 10px 12px; font-size: 8.5pt; color: #475569; }
    .cancelled { color: #b91c1c; font-size: 13pt; font-weight: bold; text-align: center; padding: 10px; border: 1.5px solid #b91c1c; }
    .footer { font-size: 8pt; color: #94a3b8; text-align: center; border-top: 0.5px solid #e2e8f0; padding-top: 6px; }
</style>
</head>
<body>
{{-- The bold font has no Taka sign and no Bangla letters, so names and currency symbols stay regular weight. --}}
@php $amount = fn ($value) => '<span class="symbol">' . e(setting('currency_symbol', '')) . '</span> ' . number_format((float) $value, 2); @endphp
<htmlpagefooter name="invoiceFooter">
    <div class="footer" style="margin: 0 34px;">{{ setting('invoice_footer') }} &nbsp;|&nbsp; {{ $invoice->reference_no }} &nbsp;|&nbsp; Page {PAGENO} of {nbpg}</div>
</htmlpagefooter>
<sethtmlpagefooter name="invoiceFooter" value="on" />

<table class="band">
    <tr>
        <td style="width:58%">
            @if ($logoPath)
                <div style="background-color:#ffffff; padding:6px; width:130px; margin-bottom:6px;"><img src="{{ $logoPath }}" style="max-height:44px; max-width:130px;"></div>
            @endif
            <div class="company">{{ setting('site_name', config('app.name')) }}</div>
            <div class="company-line">
                {{ setting('site_address') }}<br>
                {{ collect([setting('site_phone'), setting('site_email'), setting('site_website')])->filter()->join('  |  ') }}
            </div>
        </td>
        <td>
            <div class="doc-title">{{ $title }}</div>
            <div class="doc-ref">{{ $invoice->reference_no }}</div>
        </td>
    </tr>
</table>

<div class="body">
    <table class="meta">
        <tr>
            <td style="width:55%">
                <div class="label">{{ $partyLabel }}</div>
                <div class="party-name">{{ $party->name }}</div>
                @if ($party->contact_person)<div class="muted">Attn: {{ $party->contact_person }}</div>@endif
                @if ($party->address)<div class="muted">{{ $party->address }}</div>@endif
                @if ($party->mobile)<div class="muted">{{ $party->mobile }}</div>@endif
                @if ($party->email)<div class="muted">{{ $party->email }}</div>@endif
            </td>
            <td style="width:45%; text-align:right;">
                <table class="meta-table">
                    <tr><td class="muted" style="text-align:right;">Invoice date</td><td style="text-align:right; width:45%; font-weight:bold;">{{ $date->format('d M Y') }}</td></tr>
                    <tr><td class="muted" style="text-align:right;">Prepared by</td><td style="text-align:right;">{{ $invoice->creator?->name }}</td></tr>
                    <tr><td class="muted" style="text-align:right;">Payment</td><td style="text-align:right; font-weight:bold; color:{{ ['Paid' => '#15803d', 'Partial' => '#b45309', 'Due' => '#b91c1c'][$invoice->payment_status] }};">{{ $invoice->isCancelled() ? 'Cancelled' : $invoice->payment_status }}</td></tr>
                </table>
                <div style="text-align:right; padding-top:8px;"><barcode code="{{ $invoice->reference_no }}" type="C128A" size="0.8" height="0.9" /></div>
            </td>
        </tr>
    </table>

    @if ($invoice->isCancelled())
        <div class="cancelled">THIS INVOICE HAS BEEN CANCELLED</div>
        <br>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%" class="center">#</th>
                <th style="width:45%">Product</th>
                <th style="width:15%" class="right">Quantity</th>
                <th style="width:17%" class="right">Unit Price</th>
                <th style="width:18%" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->details as $index => $detail)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $detail->product?->name }}<br><span class="sku">{{ $detail->product?->sku }}</span></td>
                    <td class="right">{{ qty($detail->quantity) }} {{ $detail->product?->unit?->short_name }}</td>
                    <td class="right">{{ number_format($detail->unit_price, 2) }}</td>
                    <td class="right">{{ number_format($detail->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top:14px;">
        <tr>
            <td style="width:52%; vertical-align:top; padding-right:18px;">
                @if ($invoice->notes)
                    <div class="label" style="padding-bottom:4px;">Notes</div>
                    <div class="notes">{{ $invoice->notes }}</div>
                @endif
            </td>
            <td style="width:48%; vertical-align:top;">
                <table class="totals">
                    <tr><td>Total quantity</td><td class="right">{{ qty($invoice->total_quantity) }}</td></tr>
                    <tr><td>Subtotal</td><td class="right">{{ money($invoice->subtotal) }}</td></tr>
                    @if ((float) $invoice->discount > 0)<tr><td>Discount</td><td class="right">- {{ money($invoice->discount) }}</td></tr>@endif
                    @if ((float) ($invoice->other_charges ?? 0) > 0)<tr><td>Other charges</td><td class="right">{{ money($invoice->other_charges) }}</td></tr>@endif
                    <tr class="grand"><td>Grand Total</td><td class="right">{!! $amount($invoice->grand_total) !!}</td></tr>
                    <tr><td>Paid</td><td class="right">{{ money($invoice->paid_amount) }}</td></tr>
                    @if ((float) $invoice->returned_amount > 0)<tr><td>Returned</td><td class="right">{{ money($invoice->returned_amount) }}</td></tr>@endif
                    <tr class="due"><td>Balance Due</td><td class="right">{!! $amount($invoice->due_amount) !!}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
