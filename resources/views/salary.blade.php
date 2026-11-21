@extends('layout')
@section('content')
<section class="grid max-w-2xl gap-4">
    <p class="text-sm">Advertised minimum annual salaries in {{ $currency }} for current openings with a disclosed annual minimum.</p>
    @if ($band->median !== null)
        <x-board.kpi label="Median advertised minimum" :value="$currency.' '.number_format($band->median)" />
        <x-board.salary-band :p25="$band->p25" :p50="$band->median" :p75="$band->p75" :currency="$currency" />
    @else
        <p>No qualifying salaries available.</p>
    @endif
</section>
@endsection
