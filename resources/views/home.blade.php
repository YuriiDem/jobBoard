@extends('layout')
@section('content')
<a href="{{ App\Services\Seo::url('jobs') }}">Browse {{ config('jobboard.niche_name') }} jobs</a>
@endsection
