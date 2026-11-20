@extends('layout')
@section('content')
@if($company)<p>{{ $company->description ?? $company->tagline }}</p>@endif
<form method="get" action="{{ App\Services\Seo::url(request()->path()) }}">
@foreach(['q'=>'Search jobs','stage'=>'Funding stage','workplace'=>'Workplace','tag'=>'Tag','min_salary'=>'Minimum annual salary ('.config('jobboard.default_currency').')'] as $key=>$label)
<label>{{ $label }} <input name="{{ $key }}" value="{{ $filters[$key] ?? '' }}" type="{{ $key==='min_salary'?'number':'text' }}"></label>
@endforeach
<button>Filter jobs</button></form>
<ul>@forelse($jobs as $job)
<li><a href="{{ App\Services\Seo::url('jobs/'.$job->slug) }}">{{ $job->title }}</a> at
<a href="{{ App\Services\Seo::url('companies/'.$job->company->slug) }}">{{ $job->company->name }}</a>
@if($job->primaryLocation)<a href="{{ App\Services\Seo::url('jobs/'.$job->primaryLocation->slug) }}">{{ $job->primaryLocation->name }}</a>@else {{ $job->raw_location }} @endif</li>
@empty<li>No current openings.</li>@endforelse</ul>
@include('pagination',['pages'=>$jobs])
@endsection
