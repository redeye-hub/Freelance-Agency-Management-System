@props(['value'])
@php
    $color = match ($value) {
        'active', 'available' => 'emerald',
        'suspended', 'busy' => 'amber',
        'unavailable' => 'rose',
        default => 'gray',
    };
@endphp
<x-badge :color="$color">{{ ucfirst($value) }}</x-badge>