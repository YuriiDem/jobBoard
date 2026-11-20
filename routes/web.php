<?php
use App\Http\Controllers\BoardController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;
Route::controller(BoardController::class)->group(function(){
 Route::get('/','home'); Route::get('/jobs','feed'); Route::get('/jobs/{slug}/apply','apply'); Route::get('/jobs/{topic}/{location}','pair'); Route::get('/jobs/{slug}','single');
 Route::get('/companies','companies'); Route::get('/companies/{company:slug}','company'); Route::get('/salary-guide','salary');
 Route::get('/locations/{slug}','locationAlias');
});
Route::controller(SeoController::class)->group(function(){
 Route::get('/sitemap.xml','sitemap');Route::get('/sitemaps/{part}.xml','part')->where('part','[a-f0-9-]+');
 Route::get('/robots.txt','robots'); Route::get('/llms.txt','llms');
});
