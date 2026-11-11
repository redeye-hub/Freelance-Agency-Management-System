<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FreelancerProfileController extends Controller
{
    public function edit(Request $request)
    {
        $profile = $request->user()->freelancerProfile()
            ->firstOrCreate([], ['availability' => 'available']);
        $profile->load('skills');

        return view('freelancer.profile', compact('profile'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'availability' => ['required', Rule::in(FreelancerProfile::AVAILABILITY)],
            'skills' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $request->user()->freelancerProfile()
            ->firstOrCreate([], ['availability' => 'available']);

        $profile->update([
            'headline' => $data['headline'] ?? null,
            'bio' => $data['bio'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'availability' => $data['availability'],
        ]);

        // "laravel, vue, figma" -> skill records (same name is reused, not duplicated)
        $skillIds = collect(explode(',', $data['skills'] ?? ''))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->map(fn ($s) => Skill::firstOrCreate(['name' => mb_substr($s, 0, 50)])->id)
            ->unique()
            ->values()
            ->all();

        $profile->skills()->sync($skillIds);

        return redirect()->route('freelancer.profile.edit')->with('success', 'Your freelancer profile was saved.');
    }
}