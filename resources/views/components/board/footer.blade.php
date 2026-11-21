@php
    $facets = App\Services\JobFilterOptions::forListing(App\Models\Job::active(), 'jobs');
    $technologies = [];
    foreach (array_slice(array_keys($facets['tag'] ?? []), 0, 8) as $tag) {
        $technologies['/jobs?'.http_build_query(['tag' => $tag], '', '&', PHP_QUERY_RFC3986)] = $tag;
    }
    $places = [];
    foreach (array_slice($facets['location'] ?? [], 0, 8, true) as $slug => $place) {
        $places['/locations/'.$slug] = $place['label'];
    }
    $sections = [
        'Technologies' => $technologies + ['/tags' => 'All technologies'],
        'Locations' => $places + ['/remote-jobs' => 'Remote jobs', '/locations' => 'All locations'],
        'Find work' => ['/jobs' => 'All vacancies', '/roles' => 'Roles', '/departments' => 'Departments', '/collections' => 'Collections'],
        'Employment' => ['/jobs/full-time' => 'Full time', '/jobs/contracts' => 'Contracts', '/jobs/internships' => 'Internships', '/jobs/working-student' => 'Working students'],
        'Resources' => ['/companies' => 'Hiring companies', '/salary-guide' => 'Salary guide', '/' => 'Market overview'],
    ];
@endphp
<footer id="footer" class="mt-10 border-t border-base-300 bg-base-100 text-base-content">
    <section class="border-b border-base-300">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-5 px-4 py-10 sm:px-6 md:flex-row md:items-end md:justify-between md:py-14">
            <h2 class="max-w-xl font-display text-2xl font-bold tracking-tight sm:text-4xl">
                Explore IT salaries
                <span class="mt-1 block text-base-content/60">Compare pay across current openings.</span>
            </h2>
            <a href="/salary-guide" class="btn btn-primary shrink-0">Read the salary guide <x-heroicon-o-arrow-right width="17" height="17" aria-hidden="true" /></a>
        </div>
    </section>
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid gap-9 py-10 lg:grid-cols-[minmax(12rem,1.6fr)_minmax(0,5fr)] lg:py-14">
            <div>
                <a href="/" class="inline-flex items-center gap-2 font-display text-xl font-bold">
                    <x-heroicon-o-briefcase width="24" height="24" aria-hidden="true" />{{ config('jobboard.site_name') }}
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-base-content/75">Find developer jobs by technology, profession and location. Explore hiring teams and advertised salaries.</p>
            </div>
            <nav aria-label="Footer" class="grid grid-cols-2 items-start gap-x-4 gap-y-7 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($sections as $section => $links)
                    <div class="min-w-0">
                        <h2 class="mb-2 font-bold lg:text-sm">{{ $section }}</h2>
                        <ul class="text-sm">
                            @foreach($links as $url => $label)
                                <li><a class="inline-block max-w-full break-words py-2 text-base-content/80 hover:text-base-content hover:underline lg:py-1.5" href="{{ $url }}">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </div>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-base-300 py-5 text-sm">
            <span class="text-base-content/65">© {{ now()->year }} {{ config('jobboard.site_name') }}</span>
            <a href="/sitemap.xml" class="hover:underline">Sitemap</a>
            <div class="ms-auto"><x-board.theme-switch /></div>
        </div>
    </div>
</footer>
