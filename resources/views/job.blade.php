@extends('layout')
@section('content')
<article class="card max-w-3xl gap-3 border border-base-300 bg-base-100 p-4 sm:p-6">
<p><a class="inline-flex items-center gap-3 font-semibold" href="{{ App\Services\Seo::url('companies/'.$job->company->slug) }}"><x-board.company-logo :name="$job->company->name" :src="$job->company->logo_url" />{{ $job->company->name }}</a></p>
<p>{{ $job->description_plain ?: strip_tags($job->description_html ?? '') }}</p>
<p>Posted <time datetime="{{ $job->published_at->toAtomString() }}">{{ $job->published_at->toDateString() }}</time>
@if($job->expires_at) · Closes {{ $job->expires_at->toDateString() }} @endif</p>
<p>Source: {{ str($job->source)->headline() }}</p>
@if($salary = $job->salaryLabel())<p><strong class="text-base font-bold text-base-content tabular-nums">{{ $salary }}</strong></p>@endif
@if($job->primaryLocation)<p>{{ $job->primaryLocation->name }} {{ $job->primaryLocation->country_code }}</p>@else<p>{{ $job->raw_location }}</p>@endif
<nav aria-label="Related jobs" class="flex flex-wrap gap-2">
    @foreach($hubs as $path => $hub)
        <a class="btn btn-sm btn-outline gap-2" href="{{ App\Services\Seo::url($path) }}"><x-board.term-icon :kind="$hub['kind']" :name="$hub['name']" :size="16" />{{ $hub['name'] }}</a>
    @endforeach
</nav>
<a class="btn btn-primary w-fit" rel="nofollow sponsored" href="{{ App\Services\Seo::url('jobs/'.$job->slug.'/apply') }}">Apply</a>
</article>
@endsection
@if($schema)
@push('schema')
<script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
@endpush
@endif
