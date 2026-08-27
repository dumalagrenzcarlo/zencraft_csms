@extends('platform.layouts.app')

@section('title', 'Provision school')

@section('content')
<div class="page-head"><div><span class="eyebrow">New tenant</span><h1>Provision a school</h1><p>A dedicated database and four portal domains will be created automatically.</p></div></div>
<form class="card form-card" method="post" action="{{ route('platform.schools.store') }}">
    @csrf
    <div class="form-grid">
        <div class="field full"><label for="name">School name</label><input id="name" name="name" value="{{ old('name') }}" required>@error('name')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="slug">Workspace slug</label><input id="slug" name="slug" value="{{ old('slug') }}" placeholder="sample-academy" required>@error('slug')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="timezone">Timezone</label><input id="timezone" name="timezone" value="{{ old('timezone', 'Asia/Manila') }}" required>@error('timezone')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field full"><label for="plan_id">Plan</label><select id="plan_id" name="plan_id" required><option value="">Choose a plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }} · {{ number_format($plan->max_users ?? 0) }} users</option>@endforeach</select>@error('plan_id')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field full"><h2>Initial school administrator</h2></div>
        <div class="field"><label for="admin_name">Full name</label><input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>@error('admin_name')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="admin_email">Email</label><input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required>@error('admin_email')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="admin_password">Temporary password</label><input id="admin_password" name="admin_password" type="password" minlength="12" required>@error('admin_password')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="admin_password_confirmation">Confirm password</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" required></div>
    </div>
    <div class="actions"><button class="button orange" type="submit">Create workspace</button><a class="button secondary" href="{{ route('platform.schools.index') }}">Cancel</a></div>
</form>
@endsection
