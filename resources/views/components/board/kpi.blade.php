@props(['label', 'value', 'samples' => [], 'icon' => null])
@php
    $series = collect($samples)->take(-30)->values();
    $low = $series->min();
    $range = $series->max() - $low;
    $points = $series->map(function ($sample, $i) use ($series, $low, $range) {
        $x = 2 + 116 * $i / max(1, $series->count() - 1);
        $y = $range > 0 ? 30 - 28 * ($sample - $low) / $range : 16;
        return round($x, 2).','.round($y, 2);
    })->implode(' ');
@endphp
<div class="stats border border-base-300 bg-base-100">
    <div class="stat min-w-0 p-3.5 sm:p-4">
        <div class="stat-title flex items-center gap-2 text-sm">
            @if ($icon)
                <x-dynamic-component :component="'heroicon-o-'.$icon" width="18" height="18" class="shrink-0" aria-hidden="true" />
            @endif
            {{ $label }}
        </div>
        <div class="stat-value font-mono text-xl tabular-nums sm:text-2xl">{{ $value }}</div>
        <svg width="120" height="32" viewBox="0 0 120 32" class="mt-2 text-primary" role="img" aria-label="{{ $label }} trend, oldest to newest: {{ $series->implode(', ') }}">
            @if ($series->count() > 1)<polyline points="{{ $points }}" fill="none" stroke="currentColor" stroke-width="2"/>@endif
        </svg>
    </div>
</div>
