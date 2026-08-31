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
        <h2>Portal paths</h2>
        <ul class="domain-list">
            <li><a href="{{ url($school->slug) }}" target="_blank" rel="noreferrer">{{ url($school->slug) }} ↗</a></li>
            <li><a href="{{ url($school->slug.'/admin') }}" target="_blank" rel="noreferrer">Admin portal ↗</a></li>
            <li><a href="{{ url($school->slug.'/teacher') }}" target="_blank" rel="noreferrer">Teacher portal ↗</a></li>
            <li><a href="{{ url($school->slug.'/student') }}" target="_blank" rel="noreferrer">Student portal ↗</a></li>
        </ul>
        @if($school->domains->isNotEmpty())
        <h3>Optional domains</h3>
        <ul class="domain-list">
        @foreach($school->domains as $domain)
            <li><a href="{{ request()->getScheme() }}://{{ $domain->domain }}{{ request()->getPort() && !in_array(request()->getPort(), [80,443]) ? ':'.request()->getPort() : '' }}" target="_blank" rel="noreferrer">{{ $domain->domain }} ↗</a></li>
        @endforeach
        </ul>
        @endif
    </article>
</section>

<section class="card section-card admin-account-card">
    <div class="card-head">
        <div><span class="eyebrow">School administrator</span><h2>Admin account details</h2></div>
        <a class="button secondary" href="{{ url($school->slug.'/admin/login') }}" target="_blank" rel="noreferrer">Open admin login ↗</a>
    </div>
    @if($adminAccount)
        <div class="account-layout">
            <dl class="definition account-definition">
                <dt>Name</dt><dd>{{ $adminAccount['name'] }}</dd>
                <dt>Username</dt><dd><code>{{ $adminAccount['username'] }}</code></dd>
                <dt>Email</dt><dd>{{ $adminAccount['email'] }}</dd>
                <dt>Password status</dt><dd>{{ $adminAccount['must_change_password'] ? 'Temporary password · change required' : 'Password set by administrator' }}</dd>
                <dt>Last updated</dt><dd>{{ $adminAccount['updated_at']?->format('M j, Y g:i A') ?? '—' }}</dd>
            </dl>
            @if(auth()->user()->role === 'owner')
            <form class="reset-panel" method="post" action="{{ route('platform.schools.admin-account.update', $school) }}">
                @csrf @method('patch')
                <h3>Set a new temporary password</h3>
                <p>Passwords cannot be viewed after creation. Set a new one if the school administrator cannot sign in.</p>
                <div class="field"><label for="admin_password">New temporary password</label><input id="admin_password" name="admin_password" type="password" minlength="12" autocomplete="new-password" required>@error('admin_password')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="admin_password_confirmation">Confirm password</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
                <button class="button" type="submit">Update temporary password</button>
            </form>
            @endif
        </div>
    @else
        <div class="empty-state"><strong>No administrator account found</strong><p>This workspace needs an administrator account before the Admin Portal can be used.</p></div>
    @endif
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
        @if($supportUsers->isNotEmpty())
        <form class="stack-form" method="post" action="{{ route('platform.schools.support-access.store', $school) }}">@csrf<select name="support_user_id" required><option value="">Select support user</option>@foreach($supportUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>@endforeach</select><input name="reason" required maxlength="1000" placeholder="Reason for access"><button class="button" type="submit">Grant timed access</button></form>
        @else
        <div class="empty-state compact-empty"><strong>No support users yet</strong><p>Create a platform support account to assign temporary access.</p></div>
        @endif
        <details class="support-create" @if($supportUsers->isEmpty()) open @endif>
            <summary>{{ $supportUsers->isEmpty() ? 'Create the first support user' : 'Add another support user' }}</summary>
            <form class="stack-form" method="post" action="{{ route('platform.support-users.store') }}">@csrf
                <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Full name">
                <input name="email" value="{{ old('email') }}" type="email" required maxlength="190" placeholder="Email address">
                <input name="password" type="password" required minlength="12" autocomplete="new-password" placeholder="Temporary password (12+ characters)">
                <input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password" placeholder="Confirm temporary password">
                @error('name')<span class="error">{{ $message }}</span>@enderror @error('email')<span class="error">{{ $message }}</span>@enderror @error('password')<span class="error">{{ $message }}</span>@enderror
                <button class="button secondary" type="submit">Create support user</button>
            </form>
        </details>
        <div class="grant-list">@forelse($school->supportAccessGrants->sortByDesc('created_at')->take(8) as $grant)<div><span><strong>{{ $grant->supportUser?->name }}</strong><small>{{ $grant->revoked_at ? 'Revoked' : ($grant->expires_at->isPast() ? 'Expired' : 'Until '.$grant->expires_at->format('M j, g:i A')) }}</small></span>@if(!$grant->revoked_at && $grant->expires_at->isFuture())<form method="post" action="{{ route('platform.schools.support-access.destroy', [$school, $grant]) }}">@csrf @method('delete')<button class="text-danger" type="submit">Revoke</button></form>@endif</div>@empty<p>No access grants recorded.</p>@endforelse</div>
    </article>
</section>
@endif

<section class="card section-card"><span class="eyebrow">Recovery</span><h2>Verified backups</h2><div class="table-wrap embedded"><table class="table"><thead><tr><th>Created</th><th>Status</th><th>Tables</th><th>Rows</th><th>Size</th><th>Verified</th></tr></thead><tbody>@forelse($school->backups->sortByDesc('created_at')->take(10) as $backup)<tr><td>{{ $backup->created_at->format('M j, Y g:i A') }}</td><td><span class="badge">{{ $backup->status }}</span></td><td>{{ $backup->table_count }}</td><td>{{ number_format($backup->row_count) }}</td><td>{{ number_format($backup->size_bytes / 1024, 1) }} KB</td><td>{{ $backup->verified_at?->diffForHumans() ?? 'Not yet' }}</td></tr>@empty<tr><td colspan="6" class="empty">No backups recorded. Run the scheduled backup job after deployment.</td></tr>@endforelse</tbody></table></div></section>
@endsection
