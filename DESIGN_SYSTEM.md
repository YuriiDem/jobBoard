# Job board design system

## Visual references

TrueUp’s home screen pairs a restrained white navigation bar and a single blue action with a dark section of compact company metrics. Carry the alignment, contrast and numeric emphasis into the board. DEVjobs places the vacancy title and employer above a compact row of work attributes, followed by technologies and an overflow count. Our mobile card gives salary its own row and keeps the title first. The reference captures are local to `refs/` at 1440 × 1000 and 390 × 844. The DEVjobs captures render its server-delivered search HTML and stylesheet. [TrueUp](https://www.trueup.io/) · [DEVjobs search](https://devjobs.de/jobs/search)

## 1. Theme and startup

Use `resources/css/app.css` as the token source. The light theme uses white, near-black ink and TrueUp blue (#1452f0). The dark theme uses DEVjobs charcoal (#0a0a0a), raised surfaces (#161618), borders (#2a2a2e) and white primary actions. Both themes expose the same semantic roles. Use base-100 for cards, base-200 for the page canvas, base-300 for separators, base-content for readable text, primary for actions and focus, and success for salary and positive change. Use labels or arrows alongside status colors. Borders are 1px, panels have an 8px radius, and controls have a 6px radius.

Place this in the public Blade layout head before first paint. When there is no saved preference, the daisyUI themes follow the OS. Load the public entry once per document. Filament manages its own assets.

```blade
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>
    try {
        const preference = localStorage.getItem('board-theme');
        if (['light', 'dark'].includes(preference)) document.documentElement.dataset.theme = preference;
    } catch {}
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

## 2. Typography

Inter is the UI face and Mona Sans is the display face. Both ship locally as variable Latin WOFF2 files with optional display. Metrics and keyboard hints use the system monospace stack. Numeric alignment inherits from the root. Apply `font-mono tabular-nums` explicitly to metrics, salaries, ranks, deltas and counts.

| Role | Mobile | From sm (640px) |
| --- | --- | --- |
| Metadata, pill, keyboard hint | text-xs leading-4 | same |
| Body, filters, table cells | text-sm leading-5 | same |
| Vacancy title | text-base leading-6 font-semibold | same |
| Section heading | text-lg leading-6 font-semibold | text-xl leading-7 |
| Compact hero or KPI value | text-xl leading-7 font-semibold | text-2xl leading-8 |

## 3. Layout and filtering

Use `mx-auto w-full max-w-6xl px-4 sm:px-6` for the content shell. Stack modules with `gap-3 sm:gap-4`. Cards use `p-3.5 sm:p-4`. The job grid has a fixed 40px logo column, flexible title column and intrinsic salary column. The title spans the final two columns on mobile. Reserve logo space for missing assets. The leaderboard uses a separate fixed 32px logo slot.

Use short, server-formatted salary ranges. Keep the same card geometry for pending states. Reserve space for every asynchronous control, progress indicator and chart. Variable-length results below the swap boundary can move content, so measure CLS on the composed pages at mobile and desktop sizes. Phase 2 acceptance targets are CLS 0.00 and a Lighthouse performance score of 100. Verify field LCP, INP and CLS separately.

```blade
<section id="directory" aria-label="Job directory">
    <x-board.filters :options="$filterUrls" :current-url="request()->fullUrl()" />
    {{-- Server-render the listing and its pagination here. --}}
</section>
```

Supply an ordered label-to-absolute-URL map built from eligible filters. Keep normalized query order and the same clean canonical/noindex rules as the current directory. Include the selected option and a reset link. Each link works as a normal GET. HTMX selects and replaces the directory from the response and pushes its URL into history. Keep the wrapper in every response. The strip scrolls horizontally, has 44px touch targets and a permanently allocated spinner slot. Use one directory per document.

## 4. Blade atoms and navigation

```blade
<x-board.navbar />
<x-board.badge>Laravel</x-board.badge>
<x-board.badge kind="salary">$85k–105k / yr</x-board.badge>
<x-board.badge kind="work">Remote</x-board.badge>
<x-board.badge kind="work">Hybrid</x-board.badge>
<x-board.badge kind="up">↑ 12.4%</x-board.badge>
<x-board.badge kind="overflow" aria-label="4 more technologies">+4</x-board.badge>

<x-board.job-card title="Backend Engineer" :url="$jobUrl" company="Example Studio"
    :logo="$companyLogo" salary="$85k–105k / yr" mode="Remote" :tags="$technologies" />
```

The navigation uses Alpine event handlers and native modal dialogs for focus containment, Escape dismissal and focus restoration. The Search button opens the dialog. Its form submits a GET to the existing keyword endpoint. Include one navbar per document. Use the right-hand sheet on mobile and compact More panel on desktop and the centered command palette for keyword search.

## 5. Server-rendered data primitives

```blade
<x-board.kpi label="Active vacancies" :value="number_format($activeCount)" :samples="$dailyCounts" />
<x-board.salary-band :p25="$p25" :p50="$median" :p75="$p75" currency="USD" />
<div class="overflow-x-auto">
    <table class="table table-sm w-full">
        <caption class="text-left font-semibold">Companies by active vacancies</caption>
        <thead><tr><th>Rank</th><th>Company</th><th class="text-right">Jobs</th><th>Relative volume</th></tr></thead>
        <tbody>
            @foreach ($leaders as $leader)
                <x-board.ranking-row :rank="$loop->iteration" :name="$leader->name"
                    :logo="$leader->webp_url" :count="$leader->jobs_count" :maximum="$largestCount" />
            @endforeach
        </tbody>
    </table>
</div>
```

Supply daily observations oldest first, one finite numeric observation per day. The sparkline takes the latest 30, scales within its own series and centers a flat series. Keep missing observations distinct from measured zeroes. Show an unavailable state if the data cannot represent the period. The salary band spans the interquartile range, with the median positioned proportionally between P25 and P75. Use ordered, non-null percentiles from a single currency and period. For an empty population, show “Salary data unavailable”. Ranking bars share the leading count as their denominator and have an adjacent exact count. All three primitives render without JavaScript.
