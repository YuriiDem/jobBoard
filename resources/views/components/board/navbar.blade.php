@props(['name' => config('jobboard.site_name')])
<header class="sticky top-0 z-30 border-b border-base-300 bg-base-100" x-data>
    <nav aria-label="Main" class="mx-auto flex h-16 max-w-7xl items-center gap-2 px-4 xl:gap-6 lg:px-8">
        <a href="/" class="mr-auto flex min-w-0 items-center gap-2 font-display text-xl font-bold tracking-tight lg:mr-3">
            <span class="grid size-8 shrink-0 place-items-center rounded-field bg-primary text-primary-content">
                <x-heroicon-o-briefcase width="19" height="19" aria-hidden="true" />
            </span>
            <span class="truncate">{{ $name }}</span>
        </a>
        <div class="hidden items-center gap-1 text-base font-medium lg:flex">
            <details class="group relative" x-data @click.outside="$el.open = false" @keydown.escape.stop="$el.open = false; $refs.trigger.focus()">
                <summary x-ref="trigger" class="board-menu-item gap-1.5" @if(request()->is('jobs','jobs/*','*-jobs','roles','roles/*','locations','locations/*','collections','collections/*','tags','departments','employment-types')) aria-current="location" @endif>
                    Browse jobs <x-heroicon-o-chevron-down width="14" height="14" class="group-open:rotate-180" aria-hidden="true" />
                </summary>
                <div class="absolute left-0 top-full max-h-[75dvh] w-[26rem] overflow-y-auto rounded-box border border-base-300 bg-base-100 p-2 shadow-lg">
                    <x-board.browse-links class="grid-cols-2" />
                </div>
            </details>
            @foreach (['/companies' => 'Companies', '/salary-guide' => 'Salary guide', '/' => 'Market overview'] as $href => $label)
                <a href="{{ $href }}" class="board-menu-item" @if(request()->url() === url($href)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            @if(auth()->user()?->is_admin)
                <a class="board-menu-item" href="{{ route('filament.admin.pages.dashboard') }}">Back Office</a>
            @endif
            <x-board.theme-switch />
        </div>
        <button type="button" class="inline-flex size-11 shrink-0 cursor-pointer items-center justify-center gap-3 rounded-[10px] border border-base-300 bg-base-100 text-base font-medium text-base-content hover:border-base-content/35 lg:ml-auto lg:h-[50px] lg:w-auto lg:px-[14px]" aria-label="Search jobs" aria-haspopup="dialog" @click="$refs.palette.showModal()">
            <x-heroicon-o-magnifying-glass width="20" height="20" stroke-width="2" class="shrink-0 text-base-content/60" aria-hidden="true" />
            <span class="hidden lg:inline">Search</span>
        </button>
        <button type="button" class="inline-flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-[10px] border border-base-300 bg-base-100 p-0 text-base-content hover:border-base-content/35 lg:hidden" aria-label="Open menu" aria-haspopup="dialog" @click="$refs.drawer.showModal()">
            <x-heroicon-o-bars-3 width="24" height="24" stroke-width="2" class="shrink-0" aria-hidden="true" />
        </button>
    </nav>
    <dialog x-ref="palette" class="modal" aria-label="Search jobs">
        <form action="/jobs" method="get" class="modal-box grid gap-3">
            <h2 class="font-semibold">Search jobs</h2>
            <x-board.job-search modal />
            <button type="button" class="btn justify-self-end" @click="$refs.palette.close()">Close</button>
        </form>
    </dialog>
    <dialog x-ref="drawer" class="modal modal-end lg:items-start lg:justify-items-center lg:pt-16" aria-label="Directory menu" @click="if ($event.target === $el) $el.close()">
        <div class="modal-box flex h-dvh max-h-none w-80 max-w-[90vw] lg:h-auto lg:max-h-[80vh] lg:rounded-box lg:shadow-lg flex-col gap-6 rounded-none bg-base-100 p-5">
            <div class="flex items-center justify-between border-b border-base-300 pb-3">
                <span class="font-display text-lg font-bold">{{ $name }}</span>
                <button autofocus type="button" class="btn btn-ghost btn-square" aria-label="Close menu" @click="$refs.drawer.close()"><x-heroicon-o-x-mark width="20" height="20" class="shrink-0" aria-hidden="true" /></button>
            </div>
            <nav aria-label="Browse jobs">
                <h2 class="mb-2 px-3 text-sm font-semibold text-base-content/70">Browse jobs</h2>
                <x-board.browse-links />
            </nav>
            <nav aria-label="Resources" class="grid gap-1 border-t border-base-300 pt-3">
                @foreach (['/' => ['Overview', 'squares-2x2'], '/salary-guide' => ['Salary guide', 'banknotes']] as $url => [$label, $icon])
                    <a href="{{ $url }}" class="board-menu-item gap-3 font-medium" @if(request()->url() === url($url)) aria-current="page" @endif>
                        <x-dynamic-component :component="'heroicon-o-'.$icon" width="20" height="20" class="shrink-0 text-base-content/70" aria-hidden="true" />{{ $label }}</a>
                @endforeach
            </nav>
            @if(auth()->user()?->is_admin)
                <a class="btn btn-outline mt-auto" href="{{ route('filament.admin.pages.dashboard') }}"><x-heroicon-o-key width="16" height="16" class="shrink-0" aria-hidden="true" /> Back Office</a>
            @endif
            <x-board.theme-switch />
        </div>
    </dialog>
</header>
