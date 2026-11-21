<button type="button" x-data class="btn btn-ghost btn-sm min-h-11" @click="
    const root = document.documentElement;
    root.dataset.theme = getComputedStyle(root).colorScheme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('board-theme', root.dataset.theme) } catch {}
">
    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor"><circle cx="8" cy="8" r="6"/><path d="M8 2a6 6 0 0 1 0 12Z" fill="currentColor"/></svg>
    Switch theme
</button>
