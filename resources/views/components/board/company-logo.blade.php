@props(['name', 'src' => null, 'size' => 40])
<span aria-hidden="true" class="relative inline-grid shrink-0 place-items-center overflow-hidden rounded-field border border-base-300 bg-base-200" style="width: {{ (int) $size }}px; height: {{ (int) $size }}px">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" class="text-primary" focusable="false">
        <rect width="40" height="40" fill="currentColor" opacity=".09"/>
        <path d="M0 36 36 0h4L0 40Z" fill="currentColor" opacity=".12"/>
        <text x="20" y="21" text-anchor="middle" dominant-baseline="middle" fill="currentColor" font-size="18" font-weight="700">{{ mb_strtoupper(mb_substr(trim($name),0,1)) }}</text>
    </svg>
    @if($src)
        <img src="{{ $src }}" width="{{ $size }}" height="{{ $size }}" alt="" x-data
             x-init="if ($el.complete && !$el.naturalWidth) $el.hidden = true" x-on:error="$el.hidden = true"
             class="absolute inset-0 size-full bg-white object-contain">
    @endif
</span>
