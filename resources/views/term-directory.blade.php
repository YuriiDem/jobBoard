@extends('layout')
@section('content')
<ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($terms as $term)
        <li><x-board.directory-tile :url="App\Services\Seo::url(App\Services\TaxonomyResolver::path($term->kind, $term->slug))" :name="$term->name">
            <x-board.term-icon :kind="$term->kind" :name="$term->name" :size="26" />
        </x-board.directory-tile></li>
    @empty
        <li>No entries available yet.</li>
    @endforelse
</ul>
@include('pagination', ['pages' => $terms])
@endsection
