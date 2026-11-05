<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;">
    <tr><td style="background:#0f766e;color:#ffffff;padding:20px 26px;font-size:18px;font-weight:bold;">{{ setting('site_name', config('app.name')) }}</td></tr>
    <tr><td style="padding:26px;font-size:14px;line-height:1.6;">
        <p style="margin-top:0;">Hello {{ $party->name }},</p>
        <p>Please find attached {{ $isSale ? 'sales' : 'purchase' }} invoice <strong>{{ $invoice->reference_no }}</strong>.</p>
        @if ($customMessage)<p style="white-space:pre-line;">{{ $customMessage }}</p>@endif
        <table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafc;border-radius:8px;font-size:13px;">
            <tr><td>Invoice date</td><td align="right">{{ $date->format('d M Y') }}</td></tr>
            <tr><td>Grand total</td><td align="right"><strong>{{ money($invoice->grand_total) }}</strong></td></tr>
            <tr><td>Paid</td><td align="right">{{ money($invoice->paid_amount) }}</td></tr>
            <tr><td>Balance due</td><td align="right"><strong>{{ money($invoice->due_amount) }}</strong></td></tr>
        </table>
        <p style="margin-bottom:0;">{{ setting('invoice_footer') }}</p>
    </td></tr>
    <tr><td style="padding:14px 26px;background:#f8fafc;font-size:12px;color:#64748b;">
        {{ collect([setting('site_address'), setting('site_phone'), setting('site_email')])->filter()->join(' | ') }}
    </td></tr>
</table>
</body>
</html>
