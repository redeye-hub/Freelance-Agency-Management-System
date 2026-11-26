<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    {{-- dompdf cannot read Tailwind, so this page uses plain inline CSS --}}
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; color: #3730a3; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #eef2ff; color: #3730a3; padding: 7px 8px; font-size: 11px; }
        td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .right { text-align: right; }
        .meta td { border: 0; padding: 2px 0; }
        .total td { border: 0; padding: 3px 8px; }
        .grand { font-size: 15px; font-weight: bold; color: #3730a3; }
        .muted { color: #6b7280; font-size: 10px; }
        .stamp { display: inline-block; border: 2px solid #059669; color: #059669; padding: 2px 10px; font-weight: bold; }
    </style>
</head>
<body>
    <table class="meta">
        <tr>
            <td><h1>INVOICE</h1><span class="muted">{{ config('app.name') }}</span></td>
            <td class="right">
                <strong>{{ $invoice->number }}</strong><br>
                Issued: {{ $invoice->issue_date->format('d M Y') }}<br>
                Due: {{ $invoice->due_date->format('d M Y') }}<br>
                @if ($invoice->status === 'paid') <span class="stamp">PAID</span> @endif
            </td>
        </tr>
    </table>

    <p style="margin-top: 18px;">
        <span class="muted">BILL TO</span><br>
        <strong>{{ $invoice->client->company_name }}</strong><br>
        {{ $invoice->client->contact_name }}<br>
        {{ $invoice->client->email }}
    </p>
    <p><span class="muted">PROJECT</span><br>{{ $invoice->project->name }}</p>

    <table>
        <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $it)
                <tr>
                    <td>{{ $it->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $it->quantity, 2), '0'), '.') }}</td>
                    <td class="right">${{ number_format((float) $it->unit_price, 2) }}</td>
                    <td class="right">${{ number_format((float) $it->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total" style="margin-top: 10px;">
        <tr><td></td><td class="right" style="width: 25%;">Subtotal</td><td class="right" style="width: 20%;">${{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        <tr><td></td><td class="right">Tax ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</td><td class="right">${{ number_format((float) $invoice->tax_amount, 2) }}</td></tr>
        <tr class="grand"><td></td><td class="right">Total due</td><td class="right">${{ number_format((float) $invoice->total, 2) }}</td></tr>
    </table>

    @if ($invoice->notes) <p style="margin-top: 16px;">{{ $invoice->notes }}</p> @endif

    @if ($invoice->status === 'sent')
        <p class="muted" style="margin-top: 20px;">Pay online (sandbox demo): {{ route('pay.show', $invoice->public_token) }}</p>
    @endif
</body>
</html>
