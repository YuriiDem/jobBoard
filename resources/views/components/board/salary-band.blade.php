@props(['p25', 'p50', 'p75', 'currency' => config('jobboard.default_currency')])
@php
    $medianPosition = $p75 > $p25 ? 100 * ($p50 - $p25) / ($p75 - $p25) : 50;
@endphp
<figure class="card gap-3 border border-base-300 p-3.5 sm:p-4">
    <figcaption class="text-sm font-semibold">Annual salary · {{ $currency }}</figcaption>
    <div aria-hidden="true" class="relative mx-1 h-2 rounded-field bg-primary/25">
        @foreach ([$p25 == $p75 ? 50 : 0, $medianPosition, $p25 == $p75 ? 50 : 100] as $position)
            <i class="absolute -top-1 h-4 w-0.5 bg-primary" style="left: {{ (float) $position }}%"></i>
        @endforeach
    </div>
    <dl class="grid grid-cols-3 gap-2 text-xs">
        @foreach (['P25' => $p25, 'Median P50' => $p50, 'P75' => $p75] as $label => $amount)
            <div><dt>{{ $label }}</dt><dd class="font-mono tabular-nums">{{ number_format($amount) }}</dd></div>
        @endforeach
    </dl>
</figure>
