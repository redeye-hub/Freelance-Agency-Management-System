<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FinanceReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Admin / finance dashboard and the finance report, with CSV and PDF export. */
class FinanceReportController extends Controller
{
    public function index(Request $request, FinanceReport $report)
    {
        [$from, $to] = $this->range($request);

        return view('reports.finance', ['r' => $report->build($from, $to)]);
    }

    public function export(Request $request, FinanceReport $report)
    {
        [$from, $to] = $this->range($request);
        $data = $report->build($from, $to);
        $name = 'finance-report-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('reports.finance-pdf', ['r' => $data])->download("{$name}.pdf");
        }

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Finance report', $data['from']->toDateString(), $data['to']->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['Project', 'Client', 'Invoiced', 'Revenue (paid)', 'Freelancer cost', 'Agency commission', 'Profit']);
            foreach ($data['projects'] as $p) {
                fputcsv($out, [$p['project'], $p['client'], $p['invoiced'], $p['revenue'], $p['cost'], $p['commission'], $p['profit']]);
            }
            fputcsv($out, ['TOTAL', '', $data['invoiced'], $data['revenue'], $data['cost'], $data['commission'], $data['profit']]);
            fputcsv($out, []);
            fputcsv($out, ['Outstanding invoices (today)', $data['outstanding']]);
            fputcsv($out, ['Overdue invoices (today)', $data['overdue']]);
            fclose($out);
        }, "{$name}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Default: the start of this year up to today. */
    private function range(Request $request): array
    {
        try {
            $from = $request->filled('from') ? Carbon::parse($request->query('from')) : now()->startOfYear();
            $to = $request->filled('to') ? Carbon::parse($request->query('to')) : now();
        } catch (\Throwable) {
            $from = now()->startOfYear();
            $to = now();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
