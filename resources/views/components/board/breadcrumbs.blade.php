@props(['items'])
@if(count($items) > 1)
    <nav aria-label="Breadcrumb" class="min-w-0 text-sm">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-base-content/70">
            @foreach($items as $item)
                <li class="inline-flex min-w-0 items-center gap-2">
                    @unless($loop->first)<span aria-hidden="true">/</span>@endunless
                    @if($loop->last)
                        <span aria-current="page" class="break-words text-base-content">{{ $item['name'] }}</span>
                    @elseif($loop->first && $item['name'] === 'Home')
                        <a href="{{ $item['url'] }}" aria-label="{{ $item['name'] }}" class="inline-flex size-6 items-center justify-center rounded-sm hover:text-base-content">
                            <x-heroicon-o-home width="18" height="18" aria-hidden="true" />
                        </a>
                    @else
                        <a href="{{ $item['url'] }}" class="hover:underline">{{ $item['name'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    @php
        $elements = [];
        foreach ($items as $index => $item) {
            $element = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name']];
            if ($item['url'] !== null) {
                $element['item'] = $item['url'];
            }
            $elements[] = $element;
        }
        $schema = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
@endif
