<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $byStage = Lead::with('assignee')
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
            ))
            ->latest()
            ->get()
            ->groupBy('stage');

        return view('leads.index', ['byStage' => $byStage, 'stages' => Lead::STAGES]);
    }

    public function show(Lead $lead)
    {
        $lead->load('assignee', 'client');

        return view('leads.show', [
            'lead' => $lead,
            'stages' => Lead::STAGES,
            'managers' => User::role(['super-admin', 'project-manager'])
                ->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead->update($data);

        return back()->with('success', 'Lead updated.');
    }

    public function updateStage(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in(Lead::STAGES)],
        ]);

        $lead->update(['stage' => $data['stage']]);

        return back()->with('success', "{$lead->name} moved to {$data['stage']}.");
    }

    public function convert(Lead $lead)
    {
        if ($lead->client_id) {
            return redirect()->route('clients.show', $lead->client_id)
                ->with('success', 'This lead was already converted.');
        }

        if ($lead->stage !== 'won') {
            return back()->withErrors(['stage' => 'Only leads in the "won" stage can be converted to clients.']);
        }

        $client = Client::create([
            'company_name' => $lead->company ?: $lead->name,
            'contact_name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'notes' => "Converted from lead #{$lead->id}.",
        ]);

        $lead->update(['client_id' => $client->id]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Lead converted to a client. Please complete the billing details.');
    }
}