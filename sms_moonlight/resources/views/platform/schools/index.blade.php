@extends('platform.layouts.app')

@section('title', 'Schools')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Tenant management</span><h1>Schools</h1></div>
    <a class="button orange" href="{{ route('platform.schools.create') }}">Provision a school</a>
</div>
@include('platform.schools._table', ['schools' => $schools])
<div style="margin-top:18px">{{ $schools->links() }}</div>
@endsection
