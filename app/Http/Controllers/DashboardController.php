<?php

namespace App\Http\Controllers;

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
            $stageCounts = Lead::selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage');
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
}