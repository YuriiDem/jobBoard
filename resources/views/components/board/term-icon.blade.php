@props(['kind', 'name', 'size' => 22])
@if($kind === 'tags')
    <x-board.tech-icon :tag="$name" :size="$size" />
@else
    @php
        $categories = [
            'roles' => ['user-circle', 'text-primary'],
            'departments' => ['squares-2x2', 'text-accent'],
            'markets' => ['rectangle-stack', 'text-success'],
            'locations' => ['map-pin', 'text-info'],
            'remote' => ['globe-alt', 'text-info'],
            'employment' => ['briefcase', 'text-warning'],
        ];
        [$icon, $color] = $categories[$kind] ?? ['folder-open', 'text-secondary-content'];
        $specific = [
            'software-engineer' => 'code-bracket-square', 'engineering' => 'wrench-screwdriver',
            'design' => 'swatch', 'marketing' => 'megaphone', 'finance' => 'chart-bar',
            'operations' => 'adjustments-horizontal', 'cryptocurrency' => 'currency-dollar',
            'internship' => 'academic-cap', 'working-student' => 'academic-cap',
            'part-time' => 'clock', 'contract' => 'document-text',
        ];
        $icon = $specific[Str::slug($name)] ?? $icon;
    @endphp
    <x-dynamic-component :component="'heroicon-o-'.$icon" :width="$size" :height="$size" :class="'shrink-0 '.$color" aria-hidden="true" />
@endif
