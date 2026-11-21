@props(['options' => null, 'query' => '', 'directory' => false, 'modal' => false])
@php
    $options ??= App\Services\JobFilterOptions::forListing(App\Models\Job::active(), 'jobs');
    $suggestions = [];
    foreach (['tag' => 'Tech stack', 'role' => 'Profession', 'location' => 'Place'] as $kind => $category) {
        foreach ($options[$kind] ?? [] as $value => $row) {
            $suggestions[] = ['kind' => $kind, 'category' => $category, 'value' => (string) $value, 'label' => $row['label'] ?? (string) $value, 'count' => $row['count']];
        }
    }
    $suggestions = collect($suggestions)->sortByDesc('count')->values()->all();
@endphp
<div x-data="{ open: false, term: @js($query), options: @js($suggestions),
        get matches() { return this.options.filter(o => o.label.toLowerCase().includes(this.term.trim().toLowerCase())).slice(0, 10) },
        select(o) {
            const form = this.$el.closest('form');
            if (!@js($directory)) {
                const target = new URL(form.action);
                target.searchParams.set(o.kind, o.value);
                window.location.assign(target.href);
                return;
            }
            const box = Array.from(form.elements).find(e => e.name === o.kind + '[]' && e.value === o.value);
            if (!box) return;
            this.term = ''; this.open = false; box.checked = true;
            this.$nextTick(() => form.requestSubmit());
        } }"
     @click.outside="open = false" @focusout="if (!$el.contains($event.relatedTarget)) open = false" @keydown.escape="open = false"
     class="relative z-20 flex flex-wrap items-center gap-2 rounded-none border border-base-300 bg-base-100 px-3 py-2 focus-within:border-base-content/60 md:col-span-2">
    <x-heroicon-o-magnifying-glass width="20" height="20" aria-hidden="true" />
    <input @if($directory) data-live @endif @if($modal) autofocus @endif x-model="term" @focus="open = true" @input="open = true" @keydown.enter="open = false" @keydown.down.prevent="$nextTick(() => $refs.suggestions.querySelector('button')?.focus())" type="search" autocomplete="off" name="q" value="{{ $query }}" aria-label="Search jobs" placeholder="Tech stack, profession, location…" class="min-w-0 flex-1 bg-transparent py-2 text-base focus:outline-none">
    <button class="btn btn-primary btn-sm min-h-10" @click="open = false">{{ $directory ? 'Search' : 'Find jobs' }}</button>
    <div x-ref="suggestions" x-show="open && matches.length" style="display:none" class="{{ $modal ? 'w-full' : 'absolute inset-x-0 top-full' }} max-h-80 overflow-y-auto rounded-box border border-base-300 bg-base-100 p-2 shadow-lg">
        <p class="px-2 py-1 text-xs text-base-content/70" x-text="term.trim() ? 'Suggestions' : 'Popular'"></p>
        <template x-for="o in matches" :key="o.kind + ':' + o.value">
            <button type="button" class="board-menu-item w-full gap-2 text-left" @click="select(o)">
                <span aria-hidden="true"><template x-if="o.kind === 'tag'"><x-heroicon-o-code-bracket width="18" height="18" /></template><template x-if="o.kind === 'role'"><x-heroicon-o-briefcase width="18" height="18" /></template><template x-if="o.kind === 'location'"><x-heroicon-o-map-pin width="18" height="18" /></template></span>
                <span x-text="o.label"></span><small class="ms-auto text-base-content/70" x-text="o.category"></small>
            </button>
        </template>
    </div>
</div>
