@extends('platform.layouts.app')

@section('title', 'Provision school')

@section('content')
<div class="page-head provision-head">
    <div>
        <a class="back-link" href="{{ route('platform.schools.index') }}">← Back to schools</a>
        <span class="eyebrow">Tenant management</span>
        <h1>Create a school workspace</h1>
        <p>Set up the workspace, subscription, and first administrator in one guided form.</p>
    </div>
</div>

<form method="post" action="{{ route('platform.schools.store') }}">
    @csrf
    <div class="provision-layout">
        <div class="provision-main">
            <section class="card form-section">
                <div class="section-heading"><span class="step-number">1</span><div><h2>School details</h2><p>These details identify the tenant throughout the platform.</p></div></div>
                <div class="form-grid">
                    <div class="field full"><label for="name">School name</label><input id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Sample Academy" required>@error('name')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="slug">Workspace slug</label><div class="input-affix"><input id="slug" name="slug" value="{{ old('slug') }}" placeholder="sample-academy" required><span>/admin</span></div><small>Lowercase letters, numbers, dashes, or underscores.</small>@error('slug')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="timezone">Timezone</label><input id="timezone" name="timezone" value="{{ old('timezone', 'Asia/Manila') }}" required><small>Used for attendance, deadlines, and reports.</small>@error('timezone')<span class="error">{{ $message }}</span>@enderror</div>
                </div>
            </section>

            <section class="card form-section">
                <div class="section-heading"><span class="step-number">2</span><div><h2>Subscription</h2><p>Choose the school’s starting plan.</p></div></div>
                <div class="field"><label for="plan_id">Plan</label><select id="plan_id" name="plan_id" required><option value="">Choose a plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }} · up to {{ number_format($plan->max_users ?? 0) }} users</option>@endforeach</select>@error('plan_id')<span class="error">{{ $message }}</span>@enderror</div>
            </section>

            <section class="card form-section">
                <div class="section-heading"><span class="step-number">3</span><div><h2>Initial school administrator</h2><p>This account signs in at the school’s Admin Portal.</p></div></div>
                <div class="form-grid">
                    <div class="field"><label for="admin_name">Full name</label><input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" autocomplete="name" required>@error('admin_name')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" autocomplete="email" required>@error('admin_email')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="admin_password">Temporary password</label><input id="admin_password" name="admin_password" type="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters. The administrator must change it after signing in.</small>@error('admin_password')<span class="error">{{ $message }}</span>@enderror</div>
                    <div class="field"><label for="admin_password_confirmation">Confirm temporary password</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required></div>
                </div>
            </section>
        </div>

        <aside class="card provision-summary">
            <span class="eyebrow">What happens next</span>
            <h2>Ready in one step</h2>
            <ul class="summary-list">
                <li><span>✓</span><div><strong>Isolated workspace</strong><small>A dedicated tenant database is provisioned.</small></div></li>
                <li><span>✓</span><div><strong>Admin access</strong><small>Username <code>admin</code> is created with the email above.</small></div></li>
                <li><span>✓</span><div><strong>Portal links</strong><small>Admin, teacher, and student paths are generated automatically.</small></div></li>
            </ul>
            <button class="button orange wide" type="submit">Create school workspace</button>
            <a class="button secondary wide" href="{{ route('platform.schools.index') }}">Cancel</a>
        </aside>
    </div>
</form>
@endsection
