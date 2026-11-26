<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Contract;
use App\Models\HrAuditLog;
use App\Models\LeaveRequest;
use App\Models\Client;
use App\Models\FreelancerProfile;
use App\Models\Lead;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $stats = null;
        $recent = collect();
        $crm = null;
        $stageCounts = collect();
        $profile = null;

        // Finance users: Redirect to Finance Reports
        if ($user->hasRole('finance')) {
            return redirect()->route('reports.finance');
        }

    // HR (Hazrat): HR staff get their own dashboard
    if ($user->hasRole('hr') && ! $user->hasRole('super-admin')) {
        return view('dashboard-hr', $this->hrData());
    }

        if ($user->hasRole('super-admin')) {
            $stats = [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'suspended' => User::where('status', 'suspended')->count(),
                'archived' => User::where('status', 'archived')->count(),
                'freelancers' => User::role('freelancer')->count(),
                'clients' => User::role('client')->count(),
                'managers' => User::role('project-manager')->count(),
                'finance' => User::role('finance')->count(),
                'hr' => User::role('hr')->count(),
            ];

            $recent = User::with('roles')->latest()->take(5)->get();
        }

        if ($user->hasAnyRole(['super-admin', 'project-manager'])) {
            $crm = [
                'leads_total' => Lead::count(),
                'leads_open' => Lead::whereNotIn('stage', ['won', 'lost'])->count(),
                'leads_won' => Lead::where('stage', 'won')->count(),
                'clients' => Client::where('status', 'active')->count(),
                'available' => FreelancerProfile::where('availability', 'available')->count(),
            ];

            $stageCounts = Lead::selectRaw('stage, count(*) as total')
                ->groupBy('stage')
                ->pluck('total', 'stage');
        }

        if ($user->hasRole('freelancer')) {
            $profile = $user->freelancerProfile?->load('skills');
        }

        return view('dashboard', [
            'stats' => $stats,
            'recent' => $recent,
            'crm' => $crm,
            'stageCounts' => $stageCounts,
            'stages' => Lead::STAGES,
            'profile' => $profile,
        ]);
    }
    private function hrData(): array
{
    $data = [
        'stages' => Candidate::STAGES,
        'stageCounts' => Candidate::selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage'),
        'interviews' => Candidate::where('stage', 'interview')->whereNotNull('interview_at')
            ->where('interview_at', '>=', now())->orderBy('interview_at')->take(5)->get(),
        'activeFreelancers' => User::role('freelancer')->where('status', 'active')->count(),
        'recentActivity' => HrAuditLog::with('user')->latest()->take(6)->get(),
        // Module 2 values default to zero so the page still works if Module 2 is not merged yet
        'pendingLeave' => 0,
        'onLeaveToday' => 0,
        'contractsAwaiting' => 0,
        'leaveQueue' => collect(),
    ];

    if (class_exists(LeaveRequest::class)) {
        $data['pendingLeave'] = LeaveRequest::where('status', 'pending')->count();
        $data['onLeaveToday'] = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count();
        $data['leaveQueue'] = LeaveRequest::with('user')->where('status', 'pending')->oldest()->take(5)->get();
    }
    if (class_exists(Contract::class)) {
        $data['contractsAwaiting'] = Contract::where('status', 'sent')->count();
    }

    return $data;
}
}