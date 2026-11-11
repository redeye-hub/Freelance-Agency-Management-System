@props(['role'])
@php
    $color = match ($role) {
        'super-admin' => 'rose',
        'project-manager' => 'indigo',
        'finance' => 'amber',
        'freelancer' => 'emerald',
        'client' => 'sky',
        default => 'gray',
    };
@endphp
<x-badge :color="$color">{{ $role }}</x-badge>