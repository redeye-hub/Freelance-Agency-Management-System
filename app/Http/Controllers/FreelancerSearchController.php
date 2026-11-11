<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FreelancerSearchController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'skill' => ['nullable', 'string', 'max:200'],
            'min_rate' => ['nullable', 'numeric', 'min:0'],
            'max_rate' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['nullable', Rule::in(FreelancerProfile::AVAILABILITY)],
        ]);

        $query = User::role('freelancer')
            ->where('status', 'active')
            ->with('freelancerProfile.skills');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // every skill typed (comma separated) must match
        $skills = collect(explode(',', (string) $request->skill))->map(fn ($s) => trim($s))->filter();
        foreach ($skills as $skill) {
            $query->whereHas('freelancerProfile.skills', fn ($s) => $s->where('name', 'like', "%{$skill}%"));
        }

        if ($request->filled('min_rate')) {
            $query->whereHas('freelancerProfile', fn ($p) => $p->where('hourly_rate', '>=', $request->min_rate));
        }

        if ($request->filled('max_rate')) {
            $query->whereHas('freelancerProfile', fn ($p) => $p->where('hourly_rate', '<=', $request->max_rate));
        }

        if ($request->filled('availability')) {
            $query->whereHas('freelancerProfile', fn ($p) => $p->where('availability', $request->availability));
        }

        return view('freelancers.index', [
            'freelancers' => $query->orderBy('name')->paginate(9)->withQueryString(),
        ]);
    }

    public function show(User $user)
    {
        abort_unless($user->hasRole('freelancer'), 404);

        $user->load('freelancerProfile.skills');

        return view('freelancers.show', ['freelancer' => $user]);
    }
}