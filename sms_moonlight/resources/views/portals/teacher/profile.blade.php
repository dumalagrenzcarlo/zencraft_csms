@extends('portals.layout')

@php
    $title = 'Teacher Profile';
    $portal = 'Teacher Portal';
    $heading = 'Profile';
@endphp

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-xl font-semibold">{{ $teacher->name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $teacher->rank }} | {{ $teacher->major }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-slate-500">Username</p>
                <p class="font-medium">{{ $teacher->user->username ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Role</p>
                <p class="font-medium">{{ $portalContext === 'instructor' ? 'College Instructor' : 'Teacher / Adviser' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Rank</p>
                <p class="font-medium">{{ $teacher->rank }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Major</p>
                <p class="font-medium">{{ $teacher->major }}</p>
            </div>
        </div>
    </div>
@endsection
