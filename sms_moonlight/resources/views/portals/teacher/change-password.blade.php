@extends('portals.layout')

@php
    $title = 'Teacher Change Password';
    $portal = 'Teacher Portal';
    $heading = 'Change Password';
@endphp

@section('content')
<div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    @if (session('warning'))
        <div class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-700">{{ session('warning') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <form class="space-y-4" method="POST" action="{{ route('teacher.password.update') }}">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="current_password">Current Password</label>
            <div class="relative">
                <input class="w-full rounded-md border border-slate-300 py-3" style="padding-left: 1rem; padding-right: 5rem;" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                <button class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-sm font-semibold text-emerald-700 hover:text-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500" type="button" data-password-target="current_password" data-password-label="current password" aria-controls="current_password" aria-label="Show current password" aria-pressed="false">Show</button>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="password">New Password</label>
            <div class="relative">
                <input class="w-full rounded-md border border-slate-300 py-3" style="padding-left: 1rem; padding-right: 5rem;" id="password" name="password" type="password" autocomplete="new-password" required>
                <button class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-sm font-semibold text-emerald-700 hover:text-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500" type="button" data-password-target="password" data-password-label="new password" aria-controls="password" aria-label="Show new password" aria-pressed="false">Show</button>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="password_confirmation">Confirm New Password</label>
            <div class="relative">
                <input class="w-full rounded-md border border-slate-300 py-3" style="padding-left: 1rem; padding-right: 5rem;" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                <button class="password-visibility-toggle absolute inset-y-0 right-0 px-4 text-sm font-semibold text-emerald-700 hover:text-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500" type="button" data-password-target="password_confirmation" data-password-label="password confirmation" aria-controls="password_confirmation" aria-label="Show password confirmation" aria-pressed="false">Show</button>
            </div>
        </div>
        <button class="w-full rounded-md bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-500" type="submit">Update Password</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.password-visibility-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget);
            const isVisible = input.type === 'text';

            input.type = isVisible ? 'password' : 'text';
            button.textContent = isVisible ? 'Show' : 'Hide';
            button.setAttribute('aria-label', `${isVisible ? 'Show' : 'Hide'} ${button.dataset.passwordLabel}`);
            button.setAttribute('aria-pressed', String(!isVisible));
        });
    });
</script>
@endpush
