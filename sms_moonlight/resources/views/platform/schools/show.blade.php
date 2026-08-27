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

<section class="card section-card">
    <div class="card-head"><div><span class="eyebrow">Guided onboarding</span><h2>{{ $readiness['percent'] }}% complete</h2></div><strong>{{ $readiness['completed'] }}/{{ $readiness['total'] }}</strong></div>
    <div class="progress"><span style="width: {{ $readiness['percent'] }}%"></span></div>
    <ul class="checklist">@foreach($readiness['items'] as $item)<li class="{{ $item['complete'] ? 'complete' : '' }}"><span>{{ $item['complete'] ? '✓' : '○' }}</span>{{ $item['label'] }}</li>@endforeach</ul>
    <form method="post" action="{{ route('platform.schools.onboarding', $school) }}">@csrf<button class="button secondary" type="submit">Recheck onboarding</button></form>
</section>

@if(auth()->user()->role === 'owner')
<section class="grid detail-grid commercial-grid">
    <article class="card">
        <span class="eyebrow">Subscription</span><h2>Billing lifecycle</h2>
        <dl class="definition compact"><dt>Status</dt><dd>{{ $school->currentSubscription?->status ?? 'none' }}</dd><dt>Billable users</dt><dd>{{ number_format($school->currentSubscription?->billable_users ?? 0) }}</dd><dt>Renews</dt><dd>{{ $school->currentSubscription?->renews_at?->format('M j, Y') ?? '—' }}</dd><dt>Grace ends</dt><dd>{{ $school->currentSubscription?->grace_ends_at?->format('M j, Y') ?? '—' }}</dd></dl>
        <form class="stack-form" method="post" action="{{ route('platform.schools.billing', $school) }}">@csrf @method('patch')
            <select name="plan_id"><option value="">Select plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($school->current_plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach</select>
            <select name="action" required><option value="sync_usage">Synchronize user count</option><option value="activate">Activate subscription</option><option value="change_plan">Change plan</option><option value="past_due">Mark past due</option><option value="cancel_at_period_end">Cancel at period end</option><option value="cancel_now">Cancel now</option></select>
            <button class="button" type="submit">Apply billing action</button>
        </form>
        <form method="post" action="{{ route('platform.schools.lifecycle', $school) }}">@csrf @method('patch')<input type="hidden" name="action" value="{{ $school->status === 'suspended' ? 'reactivate' : 'suspend' }}"><button class="button secondary" type="submit">{{ $school->status === 'suspended' ? 'Reactivate school' : 'Suspend school' }}</button></form>
    </article>
    <article class="card">
        <span class="eyebrow">Support operations</span><h2>Temporary access</h2>
        <form class="stack-form" method="post" action="{{ route('platform.schools.support-access.store', $school) }}">@csrf<select name="support_user_id" required><option value="">Select support user</option>@foreach($supportUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach</select><input name="reason" required maxlength="1000" placeholder="Reason for access"><button class="button" type="submit">Grant timed access</button></form>
        <div class="grant-list">@forelse($school->supportAccessGrants->sortByDesc('created_at')->take(8) as $grant)<div><span><strong>{{ $grant->supportUser?->name }}</strong><small>{{ $grant->revoked_at ? 'Revoked' : ($grant->expires_at->isPast() ? 'Expired' : 'Until '.$grant->expires_at->format('M j, g:i A')) }}</small></span>@if(!$grant->revoked_at && $grant->expires_at->isFuture())<form method="post" action="{{ route('platform.schools.support-access.destroy', [$school, $grant]) }}">@csrf @method('delete')<button class="text-danger" type="submit">Revoke</button></form>@endif</div>@empty<p>No access grants recorded.</p>@endforelse</div>
    </article>
</section>
@endif

<section class="card section-card"><span class="eyebrow">Recovery</span><h2>Verified backups</h2><div class="table-wrap embedded"><table class="table"><thead><tr><th>Created</th><th>Status</th><th>Tables</th><th>Rows</th><th>Size</th><th>Verified</th></tr></thead><tbody>@forelse($school->backups->sortByDesc('created_at')->take(10) as $backup)<tr><td>{{ $backup->created_at->format('M j, Y g:i A') }}</td><td><span class="badge">{{ $backup->status }}</span></td><td>{{ $backup->table_count }}</td><td>{{ number_format($backup->row_count) }}</td><td>{{ number_format($backup->size_bytes / 1024, 1) }} KB</td><td>{{ $backup->verified_at?->diffForHumans() ?? 'Not yet' }}</td></tr>@empty<tr><td colspan="6" class="empty">No backups recorded. Run the scheduled backup job after deployment.</td></tr>@endforelse</tbody></table></div></section>
@endsection
