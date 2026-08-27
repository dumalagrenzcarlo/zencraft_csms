@extends('platform.layouts.app')

@section('title', 'Overview')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Platform overview</span><h1>School workspaces</h1></div>
    <a class="button orange" href="{{ route('platform.schools.create') }}">Provision a school</a>
</div>
<section class="grid stats" aria-label="Platform totals">
    <article class="card stat"><small>Total schools</small><strong>{{ number_format($schoolCount) }}</strong></article>
    <article class="card stat"><small>Available</small><strong>{{ number_format($activeCount) }}</strong></article>
    <article class="card stat"><small>On trial</small><strong>{{ number_format($trialCount) }}</strong></article>
    <article class="card stat"><small>Billable users</small><strong>{{ number_format($billableUsers) }}</strong></article>
</section>
<div class="page-head"><div><span class="eyebrow">Recently provisioned</span><h2>Latest schools</h2></div><a href="{{ route('platform.schools.index') }}">View all →</a></div>
@include('platform.schools._table', ['schools' => $schools])
@endsection
