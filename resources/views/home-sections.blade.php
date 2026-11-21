@if($settings['stack_enabled'])
<section class="border-t border-base-300 py-5 sm:py-8">
    <x-board.home-section-heading :title="$settings['stack_title']" :description="$settings['stack_description']" url="/tags" :link="$settings['stack_link']" />
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
        @forelse($technologies as $tag => $technology)
            <a href="{{ $technology['url'] }}" class="min-w-0 rounded-2xl border border-base-300 p-3.5 hover:border-base-content/50 focus-visible:outline-primary sm:p-4">
                <span class="mb-3 inline-grid size-11 -rotate-3 place-items-center overflow-hidden rounded-xl border-2 border-white bg-white sm:size-12"><x-board.tech-icon :tag="$tag" :size="40" /></span>
                <h3 class="break-words font-semibold">{{ $tag }}</h3>
                <p class="text-sm text-base-content/70 tabular-nums">{{ number_format($technology['count']) }} {{ Str::plural('job', $technology['count']) }}</p>
            </a>
        @empty
            <p class="col-span-full text-sm text-base-content/70">No openings with a listed technology yet.</p>
        @endforelse
    </div>
</section>
@endif
@if($settings['latest_enabled'])
<section class="border-t border-base-300 py-5 sm:py-8">
    <x-board.home-section-heading :title="$settings['latest_title']" :description="$settings['latest_description']" url="/jobs" :link="$settings['latest_link']" primary />
    <div class="grid gap-x-8 md:grid-cols-2">
        @forelse($latest as $job)
            <x-board.latest-job :job="$job" />
        @empty
            <p class="text-sm text-base-content/70">No current openings.</p>
        @endforelse
    </div>
</section>
@endif
@if($settings['directory_enabled'])
<section class="grid gap-6 border-t border-base-300 py-5 sm:gap-9 sm:py-8">
    <h2 class="font-display text-2xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $settings['directory_title'] }}</h2>
    @foreach(['roles' => $roles, 'locations' => $locations] as $group => $items)
        <div>
            <h3 class="mb-3 text-lg font-bold sm:text-2xl">{{ $settings[$group.'_title'] }}</h3>
            <ul class="grid gap-x-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($items as $slug => $item)
                    <li class="border-b border-base-300">
                        <a href="{{ $slug === '@remote' ? '/remote-jobs' : url($group.'/'.$slug) }}" class="flex items-baseline justify-between gap-2 py-3 text-sm hover:underline">
                            <span class="font-semibold">{{ $group === 'roles' ? $item['label'] : ($slug === '@remote' ? 'Remote IT jobs' : 'IT jobs in '.$item['label']) }}</span>
                            <span class="text-base-content/70 tabular-nums">{{ number_format($item['count']) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="col-span-full text-sm text-base-content/70">No current openings.</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</section>
@endif
