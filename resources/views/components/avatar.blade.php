@props(['user', 'size' => 'h-9 w-9'])
@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="" class="{{ $size }} rounded-full object-cover">
@else
    <span class="{{ $size }} inline-flex items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-sm font-semibold">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
@endif