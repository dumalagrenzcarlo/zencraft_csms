@extends('platform.layouts.app')

@section('title', $school->name)

@section('content')
<div class="page-head"><div><span class="eyebrow">School workspace</span><h1>{{ $school->name }}</h1></div><span class="badge {{ $school->status }}">{{ $school->status }}</span></div>
<section class="grid detail-grid">
    <article class="card">
        <h2>Workspace details</h2>
        <dl class="definition">
            <dt>Tenant ID</dt><dd>{{ $school->id }}</dd>
            <dt>Slug</dt><dd>{{ $school->slug }}</dd>
            <dt>Plan</dt><dd>{{ $school->currentPlan?->name ?? 'Unassigned' }}</dd>
            <dt>Timezone</dt><dd>{{ $school->timezone }}</dd>
            <dt>Trial ends</dt><dd>{{ $school->trial_ends_at?->format('M j, Y') ?? '—' }}</dd>
            <dt>Provisioned</dt><dd>{{ $school->provisioned_at?->format('M j, Y g:i A') ?? 'Pending' }}</dd>
        </dl>
    </article>
    <article class="card">
        <h2>Portal domains</h2>
        <ul class="domain-list">
        @foreach($school->domains as $domain)
            <li><a href="{{ request()->getScheme() }}://{{ $domain->domain }}{{ request()->getPort() && !in_array(request()->getPort(), [80,443]) ? ':'.request()->getPort() : '' }}" target="_blank" rel="noreferrer">{{ $domain->domain }} ↗</a></li>
        @endforeach
        </ul>
    </article>
</section>
@endsection
