@props(['job'])
<article class="grid min-w-0 grid-cols-[40px_minmax(0,1fr)] items-start gap-3 border-b border-base-300 py-4">
    <x-board.company-logo :name="$job->company->name" :src="$job->company->logo_url" />
    <div class="min-w-0">
        <div class="flex items-start justify-between gap-2">
            <h3 class="font-semibold tracking-tight sm:text-lg"><a href="{{ url('jobs/'.$job->slug) }}" class="hover:underline">{{ $job->title }}</a></h3>
            @if($job->is_featured)<span class="badge badge-sm rounded-full bg-base-content text-base-100">Top</span>@endif
        </div>
        <p class="text-sm"><a href="{{ url('companies/'.$job->company->slug) }}" class="font-medium hover:underline">{{ $job->company->name }}</a>
            @if($place = $job->primaryLocation?->name ?? $job->raw_location)<span class="text-base-content/70"> · {{ $place }}</span>@endif
        </p>
        <p class="mt-2 flex flex-wrap items-center gap-x-2 text-sm text-base-content/70">
            @if($salary = $job->salaryLabel())<strong class="text-base-content tabular-nums">{{ $salary }}</strong>@endif
            {{ collect([$job->workplace_type, $job->employment_type, $job->seniority_level])->filter()->map(fn ($item) => Str::headline($item))->implode(' · ') }}
        </p>
        @if($job->tags)
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @foreach(array_slice($job->tags, 0, 4) as $tag)<x-board.tech-pill :tag="$tag" />@endforeach
                @if(count($job->tags) > 4)
                    <a href="{{ url('jobs/'.$job->slug) }}" class="tech-pill tabular-nums" aria-label="See all technologies for {{ $job->title }}">
                        <span class="tech-tile"><x-board.tech-icon :tag="$job->tags[4]" :size="26" /></span>+{{ count($job->tags) - 4 }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</article>
