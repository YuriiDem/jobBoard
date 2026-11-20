@extends('layout')
@section('content')
<p><a href="{{ App\Services\Seo::url('companies/'.$job->company->slug) }}">{{ $job->company->name }}</a></p>
<p>{{ $job->description_plain ?: strip_tags($job->description_html ?? '') }}</p>
<p>Posted <time datetime="{{ $job->published_at->toAtomString() }}">{{ $job->published_at->toDateString() }}</time>
@if($job->expires_at) · Closes {{ $job->expires_at->toDateString() }} @endif</p>
<p>Source: {{ str($job->source)->headline() }}</p>
@if($job->salary_min || $job->salary_max)<p>Salary: {{ $job->salary_currency }} {{ $job->salary_min }} – {{ $job->salary_max }} per {{ $job->salary_period }}</p>@endif
@if($job->primaryLocation)<p>{{ $job->primaryLocation->name }} {{ $job->primaryLocation->country_code }}</p>@else<p>{{ $job->raw_location }}</p>@endif
<nav aria-label="Related jobs">@foreach($hubs as $slug=>$name)<a href="{{ App\Services\Seo::url('jobs/'.$slug) }}">{{ $name }}</a> @endforeach</nav>
<a rel="nofollow sponsored" href="{{ App\Services\Seo::url('jobs/'.$job->slug.'/apply') }}">Apply</a>
@endsection
@if($schema)
@push('schema')
<script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
@endpush
@endif
