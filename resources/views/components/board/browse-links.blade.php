<ul {{ $attributes->class('grid gap-1') }}>
    @foreach ([
        '/jobs' => ['All jobs', 'briefcase'],
        '/remote-jobs' => ['Remote jobs', 'globe-alt'],
        '/roles' => ['Roles', 'user-group'],
        '/locations' => ['Locations', 'map-pin'],
        '/tags' => ['Technology tags', 'code-bracket'],
        '/collections' => ['Collections by industry', 'rectangle-stack'],
        '/departments' => ['Departments', 'squares-2x2'],
        '/employment-types' => ['Employment types', 'clock'],
        '/companies' => ['Hiring companies', 'building-office-2'],
    ] as $href => [$label, $icon])
        <li>
            <a href="{{ $href }}" class="board-menu-item gap-3 text-sm font-medium"
               @if(request()->path() === ltrim($href, '/')) aria-current="page" @endif>
                <x-dynamic-component :component="'heroicon-o-'.$icon" width="18" height="18" class="shrink-0 text-base-content/65" aria-hidden="true" />
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>
