@props(['job' => null, 'title' => '', 'url' => '', 'company' => '', 'companyUrl' => null, 'place' => null, 'placeUrl' => null, 'logo' => null, 'salary' => null, 'mode' => null, 'tags' => []])
@php
    if ($job) {
        $title = $job->title;
        $url = App\Services\Seo::url('jobs/'.$job->slug);
        $company = $job->company->name;
        $companyUrl = App\Services\Seo::url('companies/'.$job->company->slug);
        $logo = $job->company->logo_url;
        $place = $job->primaryLocation?->name ?? $job->raw_location;
        $placeUrl = $job->primaryLocation ? App\Services\Seo::url('locations/'.$job->primaryLocation->slug) : null;
        $mode = $job->workplace_type;
        $tags = $job->tags ?? [];
        $salary = $job->salaryLabel();
    }
    $attributesLine = collect([$mode, $job?->employment_type, $job?->seniority_level])->filter()->map(fn($v) => str($v)->headline())->implode(' · ');
@endphp
<article class="grid grid-cols-[40px_minmax(0,1fr)] items-start gap-x-3 gap-y-2 rounded-box border border-base-300 bg-base-100 p-3.5 sm:gap-x-4 sm:p-5">
    <x-board.company-logo :name="$company" :src="$logo" />
    <div class="min-w-0">
        <h2 class="text-base leading-snug font-bold sm:text-lg">
            <a class="hover:underline" href="{{ $url }}">{{ $title }}</a>
            @if($job?->published_at?->gte(now()->subDays(7)))
                <span class="badge ms-1 h-6 rounded-full border-0 bg-(--job-new-bg) px-2.5 align-middle text-sm font-medium text-(color:--job-new-text)">New</span>
            @endif
        </h2>
        <p class="mt-1 text-sm font-medium">@if($companyUrl)<a href="{{ $companyUrl }}" class="hover:underline">{{ $company }}</a>@else{{ $company }}@endif
            @if($place) · @if($placeUrl)<a href="{{ $placeUrl }}">{{ $place }}</a>@else{{ $place }}@endif @endif
        </p>
    </div>
    <div class="col-span-2 grid gap-3 sm:col-span-1 sm:col-start-2">
        @if($attributesLine || $salary)<p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-base-content/75 sm:text-sm">
            @if($salary)<strong class="text-base font-bold whitespace-nowrap text-base-content tabular-nums">{{ $salary }}</strong>@endif
            {{ $attributesLine }}
        </p>@endif
        @if($job?->excerpt)<p class="line-clamp-2 text-sm leading-relaxed text-base-content/75">{{ $job->excerpt }}</p>@endif
        @if(count($tags))
            <div class="flex min-w-0 flex-wrap items-start gap-1.5 min-[400px]:ml-[52px] sm:ml-0">
                @foreach(array_slice($tags, 0, 5) as $tag)
                    <x-board.tech-pill :tag="$tag" />
                @endforeach
                @if(count($tags) > 5)
                    <details class="board-disclosure max-w-full">
                        <summary class="tech-pill cursor-pointer tabular-nums" aria-label="{{ count($tags) - 5 }} additional technologies">
                            <span aria-hidden="true" class="isolate inline-flex items-center -space-x-3">
                                @foreach(array_slice($tags, 5, 3) as $tag)
                                    <span class="tech-tile"><x-board.tech-icon :tag="$tag" :size="26" /></span>
                                @endforeach
                            </span>
                            +{{ count($tags) - 5 }}
                        </summary>
                        <div class="mt-1.5 flex flex-wrap items-start gap-1.5">
                            @foreach(array_slice($tags, 5) as $tag)
                                <x-board.tech-pill :tag="$tag" />
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        @endif
        <div class="flex flex-wrap items-center gap-2 border-t border-base-300 pt-2">
            <a class="job-card-action" href="{{ $url }}"><x-heroicon-o-arrow-right width="20" height="20" />View job</a>
            @if($job)<a href="{{ $url }}/apply" rel="nofollow sponsored" class="job-card-action ml-auto"><x-heroicon-o-arrow-top-right-on-square width="20" height="20" />Apply</a>@endif
        </div>
    </div>
</article>
