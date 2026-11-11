@props(['color' => 'gray'])
@php
    $map = [
        'gray' => 'bg-gray-100 text-gray-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'rose' => 'bg-rose-100 text-rose-700',
        'sky' => 'bg-sky-100 text-sky-700',
        'violet' => 'bg-violet-100 text-violet-700',
        'teal' => 'bg-teal-100 text-teal-700',
        'orange' => 'bg-orange-100 text-orange-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-block px-2.5 py-0.5 rounded-full text-xs font-medium ' . ($map[$color] ?? $map['gray'])]) }}>{{ $slot }}</span>