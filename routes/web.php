<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::controller(BoardController::class)->group(function () {
    Route::get('/', 'home');
    Route::get('/jobs', 'feed');
    Route::get('/jobs/{slug}/apply', 'apply');
    Route::get('/jobs/{topic}/{location}', 'pair')->where('location', '^(?!apply$).+');
    Route::get('/jobs/{slug}', 'single');
    Route::get('/companies', 'companies');
    Route::get('/companies/{company:slug}', 'company');
    Route::get('/salary-guide', 'salary');
    Route::get('/{directory}', 'termDirectory')->whereIn('directory', array_keys(\App\Services\TaxonomyResolver::DIRECTORIES));
    Route::get('/{directory}/{slug}', 'termPage')->whereIn('directory', ['roles', 'locations', 'collections']);
    Route::get('/{slug}-jobs', 'topicPage')->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
});

Route::controller(SeoController::class)->group(function () {
    Route::get('/sitemap.xml', 'sitemap');
    Route::get('/sitemaps/{part}.xml', 'part')->where('part', '[a-f0-9-]+');
    Route::get('/robots.txt', 'robots');
    Route::get('/llms.txt', 'llms');
});
