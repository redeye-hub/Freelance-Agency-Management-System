<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\InviteUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            ))
            ->when($request->role, fn ($q, $r) => $q->role($r))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::pluck('name'),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => Role::pluck('name')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(Role::pluck('name')->all())],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);
        $user->assignRole($data['role']);

        $this->sendInvite($user);

        return redirect()->route('admin.users.index')
            ->with('success', "Invitation sent to {$user->email}");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(Role::pluck('name')->all())],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        // protect yourself from removing your own admin role
        if ($user->isNot($request->user())) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 403, 'You cannot change your own status.');

        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'archived'])],
        ]);

        $user->update(['status' => $data['status']]);

        return back()->with('success', "{$user->name} is now {$data['status']}.");
    }

    public function resendInvite(User $user)
    {
        $this->sendInvite($user);

        return back()->with('success', "Invitation re-sent to {$user->email}");
    }

    private function sendInvite(User $user): void
    {
        $user->notify(new InviteUserNotification(Password::createToken($user)));
    }
}