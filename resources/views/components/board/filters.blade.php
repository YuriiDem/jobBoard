@props(['options', 'currentUrl'])
<nav aria-label="Quick filters" class="flex items-center gap-2">
    <div class="no-scrollbar flex min-w-0 gap-2 overflow-x-auto py-1">
        @foreach ($options as $label => $url)
            <a href="{{ $url }}" hx-get="{{ $url }}" hx-target="#directory" hx-select="#directory"
               hx-swap="outerHTML" hx-push-url="true" hx-indicator="#filter-busy"
               @if ($url === $currentUrl) aria-current="page" @endif
               @class(['btn btn-sm min-h-11 shrink-0 rounded-full', 'btn-primary' => $url === $currentUrl])>{{ $label }}</a>
        @endforeach
    </div>
    <span id="filter-busy" role="status" class="htmx-indicator loading loading-spinner size-4 shrink-0"><span class="sr-only">Loading results</span></span>
</nav>
