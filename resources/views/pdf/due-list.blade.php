<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: freesans; font-size: 9.5pt; color: #1e293b; }
    table { border-collapse: collapse; width: 100%; }
    .band { background-color: #0f766e; color: #ffffff; }
    .band td { padding: 24px 34px; vertical-align: middle; }
    .company { font-size: 17pt; color: #ffffff; }
    .company-line { font-size: 8.5pt; color: #ccfbf1; }
    .doc-title { font-size: 15pt; font-weight: bold; text-align: right; color: #ffffff; letter-spacing: 1px; }
    .doc-ref { font-size: 9pt; text-align: right; color: #ccfbf1; }
    .body { padding: 0 34px; }
    .meta td { padding: 16px 0 12px; vertical-align: top; font-size: 9pt; }
    .label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; letter-spacing: .6px; font-weight: bold; }
    .muted { color: #64748b; font-size: 8.5pt; }
    .cards td.card { background-color: #f8fafc; border: 0.5px solid #e2e8f0; padding: 8px 10px; vertical-align: top; }
    .cards td.gap { width: 2%; padding: 0; }
    .card-value { font-size: 12pt; color: #0f172a; padding-top: 2px; }
    .cards td.card-due { background-color: #fef2f2; border: 0.5px solid #fecaca; }
    .card-due .card-value { color: #b91c1c; }
    .grid th { background-color: #f1f5f9; color: #475569; font-size: 7.5pt; text-transform: uppercase; padding: 8px 7px; text-align: left; border-bottom: 1.5px solid #0f766e; }
    .grid th.right, .right { text-align: right; }
    .grid td { padding: 6px 7px; border-bottom: 0.5px solid #e2e8f0; font-size: 9pt; vertical-align: top; }
    .party td { background-color: #f0fdfa; border-top: 1px solid #99f6e4; border-bottom: 0.5px solid #99f6e4; padding: 8px 7px; }
    .party-name { font-size: 10.5pt; color: #0f172a; }
    .other td { color: #475569; font-style: italic; }
    .party-total td { background-color: #fff7ed; font-weight: bold; border-bottom: 1px solid #fdba74; }
    .party-total .amount { color: #b91c1c; font-size: 10pt; }
    .due { color: #b91c1c; }
    .grand td { background-color: #0f766e; color: #ffffff; font-weight: bold; font-size: 10pt; padding: 9px 7px; }
    .spacer td { border: 0; padding: 5px; }
    .days { font-size: 8pt; color: #64748b; }
    .days-late { color: #b91c1c; }
    .empty { text-align: center; color: #64748b; padding: 30px; }
    .note { font-size: 8pt; color: #64748b; padding-top: 10px; }
    .footer { font-size: 8pt; color: #94a3b8; text-align: center; border-top: 0.5px solid #e2e8f0; padding-top: 6px; }
</style>
</head>
<body>
{{-- The bold font has no Taka sign, so the currency symbol only appears in regular text. --}}
@php
    $n = fn ($value) => number_format((float) $value, 2);
    $currency = setting('currency_symbol', '');
    $period = $from || $to
        ? 'Invoices dated ' . ($from ? \Illuminate\Support\Carbon::parse($from)->format('d M Y') : 'start') . ' to ' . ($to ? \Illuminate\Support\Carbon::parse($to)->format('d M Y') : 'today')
        : 'All invoices with a due, as of today';
@endphp
<htmlpagefooter name="dueFooter">
    <div class="footer" style="margin: 0 34px;">{{ setting('site_name', config('app.name')) }} &nbsp;|&nbsp; {{ ucwords(strtolower($title)) }} &nbsp;|&nbsp; Printed {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Page {PAGENO} of {nbpg}</div>
</htmlpagefooter>
<sethtmlpagefooter name="dueFooter" value="on" />

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
            <div class="doc-ref">{{ now()->format('d M Y') }}</div>
        </td>
    </tr>
</table>

<div class="body">
    <table class="meta">
        <tr>
            <td style="width:60%">
                <div class="label">Report</div>
                <div>{{ $view === 'invoice' ? $t['label'] . ' wise statement of due invoices' : 'Total due of every ' . strtolower($t['label']) }}</div>
                <div class="muted">{{ $view === 'invoice' ? $period : 'What each ' . strtolower($t['label']) . ' owes today, including opening balance' }}</div>
                <div class="muted">@if ($view === 'invoice')Days: how long ago the invoice was made. {{ $from || $to ? 'Only invoices in the chosen dates are included.' : 'Total due includes the opening balance and payments not linked to an invoice.' }}@else Total due = due on invoices + opening balance and payments not linked to an invoice.@endif</div>
            </td>
            <td class="right">
                <div class="label">Amounts in</div>
                <div style="font-size:12pt">{{ $currency }}</div>
            </td>
        </tr>
    </table>

    @if ($view === 'invoice')
        @php
            $totalDue = $parties->sum('total_due');
            $invoiceCount = $parties->sum(fn ($party) => $party->invoices->count());
        @endphp
        <table class="cards" style="margin-bottom:14px;">
            <tr>
                <td class="card" style="width:23.5%"><div class="label">{{ $t['plural'] }}</div><div class="card-value">{{ number_format($parties->count()) }}</div></td><td class="gap"></td>
                <td class="card" style="width:23.5%"><div class="label">Due invoices</div><div class="card-value">{{ number_format($invoiceCount) }}</div></td><td class="gap"></td>
                <td class="card" style="width:23.5%"><div class="label">Invoice total</div><div class="card-value">{{ $n($parties->sum('invoice_total')) }}</div></td><td class="gap"></td>
                <td class="card card-due" style="width:23.5%"><div class="label">Total due</div><div class="card-value">{{ $n($totalDue) }}</div></td>
            </tr>
        </table>

        <table class="grid">
            <thead>
                <tr><th style="width:20%">Invoice</th><th style="width:15%">Date</th><th class="right" style="width:9%">Days</th><th class="right">Invoice total</th><th class="right">{{ $t['paid'] }}</th><th class="right">Returned</th><th class="right">Due</th></tr>
            </thead>
            <tbody>
            @forelse ($parties as $party)
                <tr class="party">
                    <td colspan="7">
                        <span class="party-name">{{ $party->name }}</span>
                        @if ($party->mobile)<span class="muted"> &nbsp;·&nbsp; {{ $party->mobile }}</span>@endif
                        @if ($party->address)<span class="muted"> &nbsp;·&nbsp; {{ $party->address }}</span>@endif
                    </td>
                </tr>
                @foreach ($party->invoices as $invoice)
                    @php $date = \Illuminate\Support\Carbon::parse($invoice->invoice_date); $days = (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay()); @endphp
                    <tr>
                        <td>{{ $invoice->reference_no }}</td>
                        <td>{{ $date->format('d M Y') }}</td>
                        <td class="right days {{ $days > 60 ? 'days-late' : '' }}">{{ $days }}</td>
                        <td class="right">{{ $n($invoice->grand_total) }}</td>
                        <td class="right">{{ $n($invoice->paid) }}</td>
                        <td class="right">{{ $n($invoice->returned) }}</td>
                        <td class="right due">{{ $n($invoice->due) }}</td>
                    </tr>
                @endforeach
                @if ($party->other != 0)
                    <tr class="other">
                        <td colspan="6">{{ $party->other > 0 ? 'Opening balance and other amounts not linked to an invoice' : 'Advance or payments not linked to an invoice' }}</td>
                        <td class="right">{{ $n($party->other) }}</td>
                    </tr>
                @endif
                <tr class="party-total">
                    <td colspan="3">Total due: {{ $party->name }}</td>
                    <td class="right">{{ $n($party->invoice_total) }}</td>
                    <td class="right">{{ $n($party->paid) }}</td>
                    <td class="right">{{ $n($party->returned) }}</td>
                    <td class="right amount">{{ $n($party->total_due) }}</td>
                </tr>
                <tr class="spacer"><td colspan="7"></td></tr>
            @empty
                <tr><td colspan="7" class="empty">No dues. Everything is paid.</td></tr>
            @endforelse
            </tbody>
            @if ($parties->isNotEmpty())
                <tbody>
                    <tr class="grand">
                        <td colspan="3">Grand total ({{ number_format($parties->count()) }} {{ strtolower($t['plural']) }})</td>
                        <td class="right">{{ $n($parties->sum('invoice_total')) }}</td>
                        <td class="right">{{ $n($parties->sum('paid')) }}</td>
                        <td class="right">{{ $n($parties->sum('returned')) }}</td>
                        <td class="right">{{ $n($totalDue) }}</td>
                    </tr>
                </tbody>
            @endif
        </table>
    @else
        <table class="cards" style="margin-bottom:14px;">
            <tr>
                <td class="card" style="width:23.5%"><div class="label">{{ $t['plural'] }} with due</div><div class="card-value">{{ number_format($totals->parties) }}</div></td><td class="gap"></td>
                <td class="card" style="width:23.5%"><div class="label">Due invoices</div><div class="card-value">{{ number_format($totals->due_invoices) }}</div></td><td class="gap"></td>
                <td class="card" style="width:23.5%"><div class="label">Due on invoices</div><div class="card-value">{{ $n($totals->invoice_due) }}</div></td><td class="gap"></td>
                <td class="card card-due" style="width:23.5%"><div class="label">Total due</div><div class="card-value">{{ $n($totals->balance) }}</div></td>
            </tr>
        </table>

        <table class="grid">
            <thead>
                <tr><th style="width:5%">#</th><th style="width:27%">{{ $t['label'] }}</th><th class="right">Invoices</th><th class="right">Invoice total</th><th class="right">{{ $t['paid'] }}</th><th class="right">Returned</th><th class="right">Opening / unlinked</th><th class="right">Total due</th></tr>
            </thead>
            <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="muted">{{ $index + 1 }}</td>
                    <td><span class="party-name" style="font-size:9.5pt">{{ $row->name }}</span>@if ($row->mobile)<br><span class="muted">{{ $row->mobile }}</span>@endif</td>
                    <td class="right">{{ number_format($row->due_invoices) }}</td>
                    <td class="right">{{ $n($row->invoice_total) }}</td>
                    <td class="right">{{ $n($row->invoice_paid) }}</td>
                    <td class="right">{{ $n($row->invoice_returned) }}</td>
                    <td class="right">{{ abs($row->other) < 0.005 ? '-' : $n($row->other) }}</td>
                    <td class="right due"><b>{{ $n($row->balance) }}</b></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No dues. Everything is paid.</td></tr>
            @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tbody>
                    <tr class="grand">
                        <td colspan="2">Total ({{ number_format($totals->parties) }} {{ strtolower($t['plural']) }})</td>
                        <td class="right">{{ number_format($totals->due_invoices) }}</td>
                        <td class="right">{{ $n($totals->invoice_total) }}</td>
                        <td class="right">{{ $n($totals->invoice_paid) }}</td>
                        <td class="right">{{ $n($totals->invoice_returned) }}</td>
                        <td class="right">{{ $n($totals->other) }}</td>
                        <td class="right">{{ $n($totals->balance) }}</td>
                    </tr>
                </tbody>
            @endif
        </table>
    @endif
</div>
</body>
</html>
