@php
    $totalStudents = $classStudents->count();
    $submittedCount = $submissions->count();
@endphp

<div class="space-y-5">
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Assignment</p>
            <p class="mt-2 font-bold text-slate-900">{{ $assignment->title }}</p>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Deadline</p>
            <p class="mt-2 font-bold text-slate-900">{{ $assignment->deadline?->format('F j, Y g:i A') ?? '-' }}</p>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status</p>
            <p class="mt-2 font-bold {{ $assignment->sent_at ? 'text-emerald-700' : 'text-slate-700' }}">
                {{ $assignment->sent_at ? 'Sent' : 'Draft' }}
            </p>
            <p class="mt-1 text-xs text-slate-500">{{ $submittedCount }} / {{ $totalStudents }} submitted</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="min-w-[820px] w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Student</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Submitted At</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">File</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($classStudents as $classStudent)
                    @php
                        $student = $classStudent->student;
                        $submission = $submissions->get($classStudent->student_id);
                    @endphp
                    <tr>
                        <td class="px-4 py-4">
                            <p class="font-semibold text-slate-900">
                                {{ strtoupper($student->lastname ?? '-') }}, {{ strtoupper($student->firstname ?? '-') }}
                            </p>
                            <p class="text-xs text-slate-500">{{ $student->lrn ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            @if($submission)
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Submitted</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-sm text-slate-600">
                            {{ $submission?->submitted_at?->format('F j, Y g:i A') ?? '-' }}
                        </td>
                        <td class="px-4 py-4 text-sm text-slate-600">
                            {{ $submission?->notes ?? '-' }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            @if($submission)
                                <a href="{{ route('teacher.assignment-submissions.download', ['submission' => $submission]) }}"
                                    class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                                    Download
                                </a>
                            @else
                                <span class="text-sm text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                            No students found for this assignment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
