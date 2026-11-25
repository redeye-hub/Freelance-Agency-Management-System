<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** HR-scoped suspend / archive / reactivate. Only touches freelancer accounts. */
class OffboardingController extends Controller
{
    public function index(Request $request)
    {
        $freelancers = User::role('freelancer')
            ->with('freelancerProfile')
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            ))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('hr.offboarding.index', ['freelancers' => $freelancers]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole('freelancer'), 403, 'HR can only manage freelancer accounts.');

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'archived'])],
            'reason' => ['required_unless:status,active', 'nullable', 'string', 'max:2000'],
        ]);

        $from = $user->status;
        $leaving = $data['status'] !== 'active';

        $user->forceFill([
            'status' => $data['status'],
            'offboarding_reason' => $leaving ? $data['reason'] : null,
            'offboarded_at' => $data['status'] === 'archived' ? now() : null,
        ])->save();

        if ($leaving) {
            $user->freelancerProfile?->update(['availability' => 'unavailable']);
        }

        HrAuditLog::record('freelancer.status', $user, [
            'from' => $from,
            'to' => $data['status'],
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', "{$user->name} is now {$data['status']}.");
    }
}
