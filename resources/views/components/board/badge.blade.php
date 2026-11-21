@props(['kind' => 'tech'])
@php
    $style = match ($kind) {
        'salary', 'up' => 'badge-success badge-soft font-mono tabular-nums',
        'down' => 'badge-error badge-soft font-mono tabular-nums',
        'work' => 'badge-neutral badge-soft',
        'overflow' => 'badge-ghost font-mono tabular-nums',
        default => 'badge-outline',
    };
@endphp
<span {{ $attributes->class(['badge badge-sm whitespace-nowrap', $style]) }}>{{ $slot }}</span>
