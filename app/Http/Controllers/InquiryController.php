<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class InquiryController extends Controller
{
    public function create()
    {
        return view('inquiry');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'service' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $lead = Lead::create($data + ['source' => 'website', 'stage' => 'new']);

        $staff = User::role(['super-admin', 'project-manager'])
            ->where('status', 'active')
            ->get()
            ->filter(fn ($u) => $u->wantsEmail());

        Notification::send($staff, new NewLeadNotification($lead));

        return redirect()->route('inquiry.create')
            ->with('success', 'Thank you! We received your inquiry and will contact you soon.');
    }
}