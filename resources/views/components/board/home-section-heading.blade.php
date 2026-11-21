@props(['title', 'description', 'url', 'link', 'primary' => false])
<header class="mb-5 flex flex-wrap items-end justify-between gap-3 sm:mb-8">
    <div class="min-w-0">
        <h2 class="font-display text-2xl leading-tight font-bold tracking-tight sm:text-4xl">{{ $title }}</h2>
        @if(filled($description))<p class="mt-2 text-base text-base-content/70 sm:text-lg">{{ $description }}</p>@endif
    </div>
    <a href="{{ $url }}" @class(['btn btn-sm min-h-9', 'btn-primary' => $primary])>{{ $link }} <x-heroicon-o-arrow-right width="17" height="17" /></a>
</header>
