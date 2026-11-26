<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Finance report</title>
    {{-- dompdf cannot read Tailwind, so this page uses plain inline CSS --}}
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 19px; color: #3730a3; margin: 0; }
        h2 { font-size: 13px; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #eef2ff; color: #3730a3; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        .r { text-align: right; }
        .kpi td { border: 0; padding: 4px 6px; }
        .muted { color: #6b7280; font-size: 9px; }
        tfoot td { font-weight: bold; border-top: 2px solid #9ca3af; }
    </style>
</head>
<body>
    <h1>Finance report</h1>
    <p class="muted">{{ config('app.name') }} · {{ $r['from']->format('d M Y') }} to {{ $r['to']->format('d M Y') }} · generated {{ now()->format('d M Y H:i') }}</p>

    <table class="kpi">
        <tr>
            <td>Revenue (paid)<br><strong>${{ number_format($r['revenue'], 2) }}</strong></td>
            <td>Freelancer cost<br><strong>${{ number_format($r['cost'], 2) }}</strong></td>
            <td>Profit<br><strong>${{ number_format($r['profit'], 2) }}</strong></td>
            <td>Outstanding<br><strong>${{ number_format($r['outstanding'], 2) }}</strong></td>
            <td>Overdue<br><strong>${{ number_format($r['overdue'], 2) }}</strong></td>
        </tr>
    </table>

    <h2>Profit per project</h2>
    <table>
        <thead><tr><th>Project</th><th>Client</th><th class="r">Invoiced</th><th class="r">Revenue</th><th class="r">Cost</th><th class="r">Commission</th><th class="r">Profit</th></tr></thead>
        <tbody>
            @foreach ($r['projects'] as $p)
                <tr>
                    <td>{{ $p['project'] }}</td><td>{{ $p['client'] }}</td>
                    <td class="r">${{ number_format($p['invoiced'], 2) }}</td>
                    <td class="r">${{ number_format($p['revenue'], 2) }}</td>
                    <td class="r">${{ number_format($p['cost'], 2) }}</td>
                    <td class="r">${{ number_format($p['commission'], 2) }}</td>
                    <td class="r">${{ number_format($p['profit'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="2">Total</td><td class="r">${{ number_format($r['invoiced'], 2) }}</td><td class="r">${{ number_format($r['revenue'], 2) }}</td><td class="r">${{ number_format($r['cost'], 2) }}</td><td class="r">${{ number_format($r['commission'], 2) }}</td><td class="r">${{ number_format($r['profit'], 2) }}</td></tr>
        </tfoot>
    </table>

    <h2>Paid revenue by month</h2>
    <table>
        <thead><tr><th>Month</th><th class="r">Revenue</th></tr></thead>
        <tbody>
            @foreach ($r['monthly'] as $m)
                <tr><td>{{ $m['label'] }}</td><td class="r">${{ number_format($m['amount'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted" style="margin-top: 14px;">Revenue = invoice subtotal (tax excluded) of invoices paid in the period. Cost = net pay for hours in approved timesheets in the period. Outstanding and overdue are as of today.</p>
</body>
</html>
