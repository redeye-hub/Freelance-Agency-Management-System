<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;

/** Public "Join as a freelancer" form. */
class ApplicationController extends Controller
{
    public function create()
    {
        return view('apply');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'headline' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string', 'max:500'],
            'expected_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'cover_note' => ['required', 'string', 'max:3000'],
        ]);

        // Already a user, or already has an open application with this email.
        $duplicate = User::where('email', $data['email'])->exists()
            || Candidate::where('email', $data['email'])
                ->whereNotIn('stage', ['rejected'])->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'email' => 'An account or application with this email already exists.',
            ]);
        }

        Candidate::create($data + ['stage' => 'applied']);

        return redirect()->route('apply.create')
            ->with('success', 'Thank you! Your application was received and our HR team will review it.');
    }
}
