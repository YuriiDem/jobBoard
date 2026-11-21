@props(['tag', 'size' => 18])
@php
    $key = strtolower(trim($tag));
    $key = ['golang'=>'go','postgres'=>'postgresql','js'=>'javascript','ts'=>'typescript','k8s'=>'kubernetes'][$key] ?? $key;
@endphp
@if(in_array($key, ['go','python','postgresql','redis','php','laravel','javascript','typescript','docker','linux','react','git','kubernetes'],true))
    <img src="{{ asset('icons/tech/'.$key.'.svg') }}" width="{{ $size }}" height="{{ $size }}" class="aspect-square shrink-0 rounded-sm bg-white object-contain p-px" alt="">
@else
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" class="shrink-0 text-accent" aria-hidden="true">
        <rect x="1" y="1" width="22" height="22" rx="5" fill="currentColor" opacity=".13"/>
        <text x="12" y="13" dominant-baseline="middle" text-anchor="middle" font-size="11" font-weight="700" fill="currentColor">{{ mb_strtoupper(mb_substr(trim($tag),0,2)) }}</text>
    </svg>
@endif
