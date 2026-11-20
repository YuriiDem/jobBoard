<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $meta['title'] }}</title>
<meta name="description" content="{{ $meta['description'] }}">
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">
@stack('schema')
</head><body>
<nav aria-label="Main"><a href="{{ App\Services\Seo::url('/') }}">{{ config('jobboard.site_name') }}</a>
<a href="{{ App\Services\Seo::url('jobs') }}">Jobs</a>
<a href="{{ App\Services\Seo::url('companies') }}">Companies</a>
<a href="{{ App\Services\Seo::url('salary-guide') }}">Salary guide</a></nav>
<main><h1>{{ $heading }}</h1>@yield('content')</main></body></html>
