<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2') }}">
<link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('node_modules/@fontsource-variable/mona-sans/files/mona-sans-latin-wght-normal.woff2') }}">
<title>{{ $meta['title'] }}</title>
<meta name="description" content="{{ $meta['description'] }}">
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">
@stack('schema')
<script>try { const theme = localStorage.getItem('board-theme'); if (['dark','light'].includes(theme)) document.documentElement.dataset.theme = theme } catch {}</script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head><body class="flex min-h-screen flex-col bg-base-200 text-base-content">
<x-board.navbar />
<main class="mx-auto grid w-full max-w-6xl flex-1 content-start gap-5 px-4 py-6 sm:px-6 sm:py-10">
@empty($isPeekPreviewModal)
<x-board.breadcrumbs :items="App\Services\Breadcrumbs::forPage(request()->path(), $heading, $termKind ?? null)" />
@endempty
@unless(View::hasSection('directory-heading'))
<h1 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ $heading }}</h1>
@endunless
@yield('content')
</main><x-board.footer /></body></html>
