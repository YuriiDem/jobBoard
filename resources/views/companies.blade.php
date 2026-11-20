@extends('layout')
@section('content')
<ul>@forelse($companies as $company)<li><a href="{{ App\Services\Seo::url('companies/'.$company->slug) }}">{{ $company->name }}</a></li>@empty<li>No companies are currently hiring.</li>@endforelse</ul>
@include('pagination',['pages'=>$companies])
@endsection
