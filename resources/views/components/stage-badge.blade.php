@props(['stage'])
@php
    $color = match ($stage) {
        'new' => 'sky',
        'contacted' => 'indigo',
        'qualified' => 'violet',
        'proposal' => 'amber',
        'won' => 'emerald',
        'lost' => 'rose',
        default => 'gray',
    };
@endphp
<x-badge :color="$color">{{ ucfirst($stage) }}</x-badge>