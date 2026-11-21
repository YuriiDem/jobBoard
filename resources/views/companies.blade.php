@extends('layout')
@section('content')
<ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
@forelse($companies as $company)
    <li><x-board.directory-tile :url="App\Services\Seo::url('companies/'.$company->slug)" :name="$company->name">
        <x-board.company-logo :name="$company->name" :src="$company->logo_url" />
    </x-board.directory-tile></li>
@empty
    <li>No companies are currently hiring.</li>
@endforelse
</ul>
@include('pagination',['pages'=>$companies])
@endsection
