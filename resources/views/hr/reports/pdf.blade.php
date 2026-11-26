<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HR report</title>
    {{-- dompdf cannot read Tailwind, so this page uses plain inline CSS --}}
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 19px; color: #3730a3; margin: 0; }
        h2 { font-size: 13px; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #eef2ff; color: #3730a3; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        .r { text-align: right; }
        .muted { color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <h1>HR report</h1>
    <p class="muted">{{ config('app.name') }} · {{ $r['from']->format('d M Y') }} to {{ $r['to']->format('d M Y') }} · generated {{ now()->format('d M Y H:i') }}</p>

    <h2>Onboarding funnel ({{ $r['funnel']['total'] }} applications, {{ $r['funnel']['approval_rate'] }}% approved)</h2>
    <table>
        <thead><tr><th>Stage</th><th class="r">Candidates</th></tr></thead>
        <tbody>
            @foreach ($r['funnel']['stages'] as $stage => $n)
                <tr><td>{{ ucfirst($stage) }}</td><td class="r">{{ $n }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Leave ({{ $r['leave']['total_days'] }} approved days)</h2>
    <table>
        <thead><tr><th>Type</th><th class="r">Days</th></tr></thead>
        <tbody>
            @forelse ($r['leave']['by_type'] as $type => $days)
                <tr><td>{{ ucfirst($type) }}</td><td class="r">{{ $days }}</td></tr>
            @empty
                <tr><td colspan="2">No approved leave.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Utilization (capacity {{ $r['utilization']['capacity'] }} h/week, average {{ $r['utilization']['average'] }}%)</h2>
    <table>
        <thead><tr><th>Freelancer</th><th class="r">Planned h/week</th><th class="r">Planned %</th><th class="r">Hours logged</th></tr></thead>
        <tbody>
            @foreach ($r['utilization']['rows'] as $row)
                <tr><td>{{ $row['name'] }}</td><td class="r">{{ $row['planned'] }}</td><td class="r">{{ $row['percent'] }}%</td><td class="r">{{ number_format($row['logged'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Ratings ({{ $r['ratings']['count'] }} reviews, average {{ $r['ratings']['overall'] ?? 'N/A' }})</h2>
    <table>
        <thead><tr><th>Freelancer</th><th class="r">Reviews</th><th class="r">Quality</th><th class="r">Communication</th><th class="r">Timeliness</th><th class="r">Overall</th></tr></thead>
        <tbody>
            @forelse ($r['ratings']['people'] as $p)
                <tr><td>{{ $p['name'] }}</td><td class="r">{{ $p['reviews'] }}</td><td class="r">{{ $p['quality'] }}</td><td class="r">{{ $p['communication'] }}</td><td class="r">{{ $p['timeliness'] }}</td><td class="r">{{ $p['overall'] }}</td></tr>
            @empty
                <tr><td colspan="6">No reviews in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
