@extends('layout')
@section('content')
@php
    $searchOptions = App\Services\JobFilterOptions::forListing(App\Models\Job::active(), 'jobs');
    $quickTags = collect(array_keys($searchOptions['tag'] ?? []))->shuffle()->take(6);
@endphp
<section class="py-4 sm:py-6">
    <h2 class="mb-4 text-xl font-semibold">Find your next {{ config('jobboard.niche_name') }} role</h2>
    <form action="/jobs" method="get"><x-board.job-search :options="$searchOptions" /></form>
    <nav aria-label="Suggested technologies" class="mt-4 flex flex-wrap items-center gap-2.5">
        @foreach($quickTags as $tag)
            <a class="inline-flex min-h-11 items-center justify-center rounded-[10px] border border-base-300 bg-base-100 px-4 text-base font-bold text-base-content hover:bg-base-300 focus-visible:bg-base-300" href="{{ url('/jobs').'?'.http_build_query(['tag' => $tag], '', '&', PHP_QUERY_RFC3986) }}">{{ $tag }}</a>
        @endforeach
        <a class="board-menu-item gap-2 font-semibold" href="/jobs">All jobs <x-heroicon-o-arrow-right width="16" height="16" aria-hidden="true" /></a>
    </nav>
</section>
@include('home-sections')
<section aria-label="Market overview" class="grid gap-3 sm:grid-cols-3">
    <x-board.kpi label="Active vacancies" icon="briefcase" :value="number_format($overview->jobs)" :samples="$history->pluck('total_active_jobs')->all()" />
    <x-board.kpi label="Hiring companies" icon="building-office-2" :value="number_format($overview->companies)" />
    <x-board.kpi label="Remote openings" icon="globe-alt" :value="number_format($overview->remote)" />
</section>
@if ($leaders->isNotEmpty())
<section class="overflow-x-auto rounded-box border border-base-300 bg-base-100 p-4">
    <h2 class="mb-3 font-semibold">Top hiring companies</h2>
    <table class="table table-sm"><thead><tr><th>Rank</th><th>Company</th><th class="text-right">Jobs</th><th>Relative volume</th></tr></thead><tbody>
    @foreach ($leaders as $leader)
        <x-board.ranking-row :rank="$loop->iteration" :name="$leader->name" :logo="$leader->logo_url" :count="$leader->jobs_count" :maximum="$leaders->first()->jobs_count" />
    @endforeach
    </tbody></table>
</section>
@endif
@endsection
