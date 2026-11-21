@props(['url', 'name'])
<a href="{{ $url }}" class="group flex h-full min-h-[76px] items-center gap-3 rounded-box border border-base-300 bg-base-100 p-3.5 hover:border-primary sm:p-4">
    <span class="grid size-10 shrink-0 place-items-center rounded-field bg-base-200">{{ $slot }}</span>
    <span class="min-w-0 flex-1 break-words font-display font-semibold">{{ $name }}</span>
    <x-heroicon-o-chevron-right width="16" height="16" class="shrink-0 text-base-content/50 group-hover:text-primary" aria-hidden="true" />
</a>
