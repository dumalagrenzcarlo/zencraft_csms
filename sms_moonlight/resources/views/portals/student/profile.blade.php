@extends('portals.layout')

@php
    $title = 'Student Profile';
    $portal = 'Student Portal';
    $heading = 'Profile';
@endphp

@section('content')
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-xl font-semibold">{{ $student->firstname }} {{ $student->lastname }}</h2>
        <p class="mt-1 text-sm text-slate-500">Student Number: {{ $student->lrn }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-slate-500">Portal Login</p>
                <p class="font-medium">{{ $student->lrn }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Gender</p>
                <p class="font-medium">{{ $student->gender }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Date of Birth</p>
                <p class="font-medium">{{ $student->dob }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Address</p>
                <p class="font-medium">{{ $student->address }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Parent/Guardian</p>
                <p class="font-medium">{{ $student->parent_guardian }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-500">Relationship</p>
                <p class="font-medium">{{ $student->parent_guardian_relationship }}</p>
            </div>
        </div>
    </div>
@endsection
