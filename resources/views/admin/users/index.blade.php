<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Management</h2>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">+ Invite user</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap gap-3">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or email"
                       class="flex-1 min-w-[12rem] rounded-lg border-gray-300 text-sm">
                <select name="role" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}" @selected(request('role') === $r)>{{ $r }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach (['active', 'suspended', 'archived'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">Reset</a>
            </form>

            <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-indigo-50 text-indigo-700 text-left">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Joined</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($users as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$u" />
                                        <div>
                                            <p class="font-medium text-gray-800">{{ $u->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-role-badge :role="$u->getRoleNames()->first()" /></td>
                                <td class="px-4 py-3"><x-status-badge :value="$u->status" /></td>
                                <td class="px-4 py-3 text-gray-500">{{ $u->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <a href="{{ route('admin.users.edit', $u) }}" class="text-sky-600 hover:underline">Edit</a>

                                        @if ($u->id !== auth()->id())
                                            @foreach ([
                                                ['active', 'Activate', 'text-emerald-600'],
                                                ['suspended', 'Suspend', 'text-amber-600'],
                                                ['archived', 'Archive', 'text-rose-600'],
                                            ] as [$newStatus, $label, $color])
                                                @if ($u->status !== $newStatus)
                                                    <form method="POST" action="{{ route('admin.users.status', $u) }}"
                                                          @if ($newStatus === 'archived') onsubmit="return confirm('Archive this user?')" @endif>
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $newStatus }}">
                                                        <button class="{{ $color }} hover:underline">{{ $label }}</button>
                                                    </form>
                                                @endif
                                            @endforeach

                                            <form method="POST" action="{{ route('admin.users.invite', $u) }}">
                                                @csrf
                                                <button class="text-violet-600 hover:underline">Re-invite</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>