@extends('layout')
@section('directory-heading', 'jobs')
@section('content')
@php
    $path = App\Services\Seo::url(request()->path());
    $groups = [
        'tag' => ['Tech stack', 'Find a technology'],
        'workplace' => ['Work model', null],
        'employment' => ['Employment type', null],
        'level' => ['Seniority', null],
        'role' => ['Roles', 'Find a role'],
        'location' => ['Locations', 'Find a location'],
        'min_salary' => ['Minimum annual salary', 'Find a salary threshold'],
        'stage' => ['Funding stage', 'Find a funding stage'],
    ];
    $defaults = [
        'workplace' => ['remote', 'hybrid', 'onsite'],
        'employment' => array_values(config('jobboard.employment')),
        'level' => ['junior', 'mid', 'senior', 'staff_principal', 'lead_manager', 'executive'],
        'stage' => ['bootstrapped', 'pre_seed', 'seed', 'series_a', 'series_b', 'series_c_plus', 'public', 'acquired'],
        'min_salary' => App\Services\JobFilterOptions::SALARY_THRESHOLDS,
    ];
@endphp
<div hx-history="false" class="grid min-w-0 gap-5">
    <form id="job-filters" action="{{ $path }}" method="get" hx-get="{{ $path }}"
          hx-trigger="submit, change[event.target.matches('input[type=checkbox],input[type=radio],select')], input[event.target.matches('[data-live]')] delay:350ms"
          hx-sync="this:replace" hx-target="#directory" hx-select="#directory" hx-swap="outerHTML" hx-push-url="true" hx-indicator="#search-busy"
          class="grid items-start gap-x-6 gap-y-4 md:grid-cols-[15rem_minmax(0,1fr)]">
        <x-board.job-search :options="$options" :query="$filters['q'] ?? ''" directory />
        <aside class="min-w-0 md:row-start-2">
            <details class="peer" aria-controls="filter-panel"><summary class="btn btn-outline btn-sm min-h-11 md:hidden"><x-heroicon-o-adjustments-horizontal width="17" height="17" /> Search filters</summary></details>
            <div id="filter-panel" class="filter-panel invisible grid grid-rows-[0fr] peer-open:visible peer-open:grid-rows-[1fr] md:visible md:grid-rows-[1fr]">
                <div class="min-h-0 overflow-hidden"><div class="mt-2 rounded-box border border-base-300 bg-base-100 p-4 md:mt-0">
                <div class="mb-3 flex items-center justify-between"><h2 class="font-semibold">Filters</h2><a href="{{ $path }}" class="link text-sm">Clear all</a></div>
                @foreach ($groups as $key => [$label, $search])
                    @php
                        $selected = $key === 'tag' ? (array) ($filters[$key] ?? []) : array_filter(explode(',', $filters[$key] ?? ''), 'strlen');
                        $counts = $options[$key] ?? [];
                        $choices = array_unique(array_merge($defaults[$key] ?? array_keys($counts), $selected));
                        if ($key === 'min_salary') { sort($choices, SORT_NUMERIC); }
                    @endphp
                    <details x-data="{ term: '' }" class="board-disclosure border-t border-base-300 py-2" @if($selected || in_array($key, ['tag', 'workplace'])) open @endif>
                        <summary class="cursor-pointer py-1 text-xs font-semibold tracking-widest text-base-content/70 uppercase">{{ $label }}</summary>
                        @if($search)
                            <input x-model="term" type="search" placeholder="{{ $search }}" aria-label="{{ $search }}" x-on:keydown.enter.prevent class="input input-sm my-2 w-full">
                        @endif
                        <div class="filter-options max-h-56 overflow-y-auto">
                            @if($key === 'min_salary')
                                <label class="filter-option">
                                    <input type="radio" name="min_salary" value="" @checked(!$selected)> Any salary
                                </label>
                            @endif
                            @foreach ($choices as $choice)
                                @php
                                    $name = $counts[$choice]['label'] ?? ($key === 'tag' ? $choice : str($choice)->headline());
                                    if ($key === 'min_salary') {
                                        $name = (config('jobboard.default_currency') === 'EUR' ? '€' : '$').number_format($choice).'+';
                                    }
                                @endphp
                                <label class="filter-option" title="{{ $name }}" @if($search) data-search="{{ $name }} {{ $choice }}" x-show="$el.dataset.search.toLowerCase().includes(term.trim().toLowerCase())" @endif>
                                    <input type="{{ $key === 'min_salary' ? 'radio' : 'checkbox' }}" name="{{ $key === 'min_salary' ? $key : $key.'[]' }}" value="{{ $choice }}" @checked(in_array((string) $choice, $selected, true))>
                                    @if($key === 'tag')<x-board.tech-icon :tag="$choice" />@endif
                                    <span class="min-w-0">{{ $name }}</span>
                                    @unless($key === 'min_salary' && !in_array($choice, $defaults[$key]))
                                        <span class="ms-auto text-xs text-base-content/55 tabular-nums">{{ number_format($counts[$choice]['count'] ?? 0) }}</span>
                                    @endunless
                                </label>
                            @endforeach
                            @if($search)<p class="filter-empty px-2 py-1 text-xs text-base-content/65">No matching options.</p>@endif
                        </div>
                    </details>
                @endforeach
                <noscript><button class="btn btn-primary mt-3">Apply filters</button></noscript>
                </div></div>
            </div>
        </aside>
        <section id="directory" data-robots="{{ $meta['robots'] }}" data-canonical="{{ $meta['canonical'] }}" class="grid min-w-0 gap-3 md:col-start-2 md:row-start-2">
            <div class="flex items-center justify-between gap-2">
                <div><h1 class="flex items-center gap-3 font-display text-2xl font-bold tracking-tight">
                    @if($company)<x-board.company-logo :name="$company->name" :src="$company->logo_url" />@elseif(isset($termKind))<x-board.term-icon :kind="$termKind" :name="$termName" :size="26" />@endif
                    {{ request()->is('jobs') ? ($filters['q'] ?? 'Latest').' jobs' : $heading }}</h1>
                    <p role="status" class="mt-1 text-sm text-base-content/70">
                        @if($jobs->hasMorePages()) Showing {{ $jobs->firstItem() }}–{{ $jobs->lastItem() }} openings
                        @else {{ number_format($jobs->lastItem() ?? 0) }} {{ Str::plural('opening', $jobs->lastItem() ?? 0) }} found @endif
                    </p>
                </div>
                <span id="search-busy" class="htmx-indicator loading loading-spinner size-5" aria-label="Loading"></span>
            </div>
            @if(array_intersect_key($filters, $groups))
                <p class="flex flex-wrap items-center gap-1.5 text-sm" aria-live="polite">
                    <span>Active filters:</span>
                    @foreach(array_intersect_key($filters, $groups) as $key => $value)
                        @foreach($key === 'tag' ? (array) $value : explode(',', $value) as $item)
                            <span class="badge badge-soft badge-sm">
                                @if($key === 'tag')<x-board.tech-icon :tag="$item" :size="14" />{{ $item }}
                                @elseif($key === 'min_salary'){{ config('jobboard.default_currency') === 'EUR' ? '€' : '$' }}{{ number_format($item) }}+
                                @else{{ $options[$key][$item]['label'] ?? str($item)->headline() }}
                                @endif
                            </span>
                        @endforeach
                    @endforeach
                </p>
            @endif
            @isset($intro)<p class="text-sm text-base-content/75">{{ $intro }}</p>@endisset
            @if($company)<p>{{ $company->description ?? $company->tagline }}</p>@endif
            <div class="grid gap-3">
                @forelse($jobs as $job)
                    <x-board.job-card :job="$job" />
                @empty
                    <p class="rounded-box border border-base-300 bg-base-100 p-5">No current openings.</p>
                @endforelse
            </div>
            @include('pagination',['pages'=>$jobs])
        </section>
    </form>
</div>
@endsection
