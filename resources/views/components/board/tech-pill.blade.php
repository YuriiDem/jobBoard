@props(['tag'])
<span class="tech-pill" title="{{ $tag }}">
    <span class="tech-tile"><x-board.tech-icon :tag="$tag" :size="26" /></span>
    <span class="truncate">{{ $tag }}</span>
</span>
