@extends('layout')
@section('content')
<p>Median advertised minimum annual salary in {{ $currency }}, based on current openings with a disclosed annual minimum.</p>
<p>{{ $median === null ? 'No qualifying salaries available.' : $currency.' '.number_format($median) }}</p>
@endsection
