<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract {{ $contract->number }}</title>
    {{-- dompdf cannot read Tailwind, so this page uses plain inline CSS --}}
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #3730a3; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 6px; vertical-align: top; }
        td.label { width: 32%; color: #6b7280; }
        .terms { white-space: pre-line; }
        .sign { margin-top: 36px; }
        .sign td { width: 50%; padding-top: 28px; border-top: 1px solid #9ca3af; }
        .muted { color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $a = $contract->assignment;
    @endphp

    <h1>Freelancer Services Contract</h1>
    <p class="muted">Contract no. {{ $contract->number }} · issued {{ $contract->sent_at?->format('d M Y') }}</p>

    <h2>Parties</h2>
    <table>
        <tr><td class="label">Agency</td><td>{{ config('app.name') }}</td></tr>
        <tr><td class="label">Freelancer</td><td>{{ $a->user->name }} ({{ $a->user->email }})</td></tr>
        <tr><td class="label">Client</td><td>{{ $a->project->client->company_name }}</td></tr>
    </table>

    <h2>Engagement</h2>
    <table>
        <tr><td class="label">Project</td><td>{{ $a->project->name }}</td></tr>
        <tr><td class="label">Start date</td><td>{{ $contract->start_date->format('d M Y') }}</td></tr>
        <tr><td class="label">End date</td><td>{{ $contract->end_date?->format('d M Y') ?? 'Until project completion' }}</td></tr>
        <tr><td class="label">Planned hours per week</td><td>{{ $contract->weekly_hours }}</td></tr>
        <tr><td class="label">Hourly rate</td><td>${{ number_format((float) $contract->hourly_rate, 2) }}</td></tr>
        <tr><td class="label">Agency commission</td><td>{{ rtrim(rtrim(number_format((float) $contract->commission_percent, 2), '0'), '.') }}%</td></tr>
    </table>

    <h2>Terms</h2>
    <p class="terms">{{ $contract->terms }}</p>

    <h2>Status</h2>
    <p>
        @if ($contract->status === 'accepted')
            Accepted electronically by {{ $a->user->name }} on {{ $contract->responded_at?->format('d M Y, H:i') }}.
        @elseif ($contract->status === 'declined')
            Declined on {{ $contract->responded_at?->format('d M Y') }}.
        @else
            Awaiting the freelancer's acceptance in the FAMS platform.
        @endif
    </p>

    <table class="sign">
        <tr><td>Agency representative</td><td>Freelancer: {{ $a->user->name }}</td></tr>
    </table>
</body>
</html>
