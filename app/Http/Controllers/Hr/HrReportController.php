<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\HrReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** HR reports: onboarding funnel, leave, utilization, ratings. Web, CSV and PDF. */
class HrReportController extends Controller
{
    public function index(Request $request, HrReport $report)
    {
        [$from, $to] = $this->range($request);

        return view('hr.reports.index', ['r' => $report->build($from, $to)]);
    }

    public function export(Request $request, HrReport $report)
    {
        [$from, $to] = $this->range($request);
        $data = $report->build($from, $to);
        $name = 'hr-report-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('hr.reports.pdf', ['r' => $data])->download("{$name}.pdf");
        }

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['HR report', $data['from']->toDateString(), $data['to']->toDateString()]);

            fputcsv($out, []);
            fputcsv($out, ['Onboarding funnel (applications in period, by current stage)']);
            fputcsv($out, ['Stage', 'Candidates']);
            foreach ($data['funnel']['stages'] as $stage => $n) {
                fputcsv($out, [ucfirst($stage), $n]);
            }
            fputcsv($out, ['Total', $data['funnel']['total']]);
            fputcsv($out, ['Approval rate %', $data['funnel']['approval_rate']]);

            fputcsv($out, []);
            fputcsv($out, ['Approved leave days in period']);
            fputcsv($out, ['Type', 'Days']);
            foreach ($data['leave']['by_type'] as $type => $days) {
                fputcsv($out, [ucfirst($type), $days]);
            }
            fputcsv($out, ['Total', $data['leave']['total_days']]);

            fputcsv($out, []);
            fputcsv($out, ['Utilization (capacity ' . $data['utilization']['capacity'] . ' h/week)']);
            fputcsv($out, ['Freelancer', 'Planned h/week', 'Planned %', 'Hours logged']);
            foreach ($data['utilization']['rows'] as $row) {
                fputcsv($out, [$row['name'], $row['planned'], $row['percent'], $row['logged']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Ratings (reviews saved in period)']);
            fputcsv($out, ['Freelancer', 'Reviews', 'Quality', 'Communication', 'Timeliness', 'Overall']);
            foreach ($data['ratings']['people'] as $p) {
                fputcsv($out, [$p['name'], $p['reviews'], $p['quality'], $p['communication'], $p['timeliness'], $p['overall']]);
            }
            fclose($out);
        }, "{$name}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Default: the last 90 days. */
    private function range(Request $request): array
    {
        try {
            $from = $request->filled('from') ? Carbon::parse($request->query('from')) : now()->subDays(90);
            $to = $request->filled('to') ? Carbon::parse($request->query('to')) : now();
        } catch (\Throwable) {
            $from = now()->subDays(90);
            $to = now();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
