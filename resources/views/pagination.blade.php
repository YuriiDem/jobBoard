<nav aria-label="Pagination" class="flex gap-3 text-sm font-semibold text-primary">
@if($pages->previousPageUrl())<a rel="prev" href="{{ $pages->currentPage()===2 ? App\Services\Seo::url(request()->path()).(empty($filters)?'':'?'.http_build_query($filters,'','&',PHP_QUERY_RFC3986)) : $pages->previousPageUrl() }}">Previous</a>@endif
@if($pages->nextPageUrl())<a rel="next" href="{{ $pages->nextPageUrl() }}">Next</a>@endif
</nav>
