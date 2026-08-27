<div class="space-y-4 p-4 sm:p-6">
    @forelse($assignments as $assignmentItem)
        @php
            $submission = $assignmentItem->submissions->first();
            $isPastDeadline = $assignmentItem->deadline && now()->greaterThan($assignmentItem->deadline);
        @endphp

        <div class="rounded-2xl border border-slate-200 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-900">{{ $assignmentItem->title }}</h3>
                        @if($submission)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Submitted</span>
                        @elseif($isPastDeadline)
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Past Deadline</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $assignmentItem->class->grade->grade ?? '-' }} -
                        {{ $assignmentItem->class->section ?? '-' }} -
                        Due {{ $assignmentItem->deadline?->format('F j, Y g:i A') ?? '-' }}
                    </p>
                    @if($assignmentItem->notes)
                        <p class="mt-3 text-sm text-slate-600">{{ $assignmentItem->notes }}</p>
                    @endif
                </div>

                <a href="{{ route('student.assignments.download', ['assignment' => $assignmentItem]) }}"
                    class="inline-flex min-h-11 items-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Download File
                </a>
            </div>

            @if($submission)
                <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Submitted {{ $submission->submitted_at?->format('F j, Y g:i A') ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">{{ $submission->file_name }}</p>
                            @if($submission->notes)
                                <p class="mt-2 text-sm text-slate-600">{{ $submission->notes }}</p>
                            @endif
                        </div>
                        <a href="{{ route('student.assignment-submissions.download', ['submission' => $submission]) }}"
                            class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                            Download Submission
                        </a>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('student.assignments.submit', ['assignment' => $assignmentItem]) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 lg:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input
                    name="file"
                    type="file"
                    required
                    accept=".doc,.docx,.pdf,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none">
                <input
                    name="notes"
                    type="text"
                    value="{{ $submission->notes ?? '' }}"
                    placeholder="Notes"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                <button
                    type="submit"
                    class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ $submission ? 'Resubmit' : 'Submit' }}
                </button>
            </form>
        </div>
    @empty
        <div class="px-6 py-16 text-center">
            <p class="text-lg font-semibold text-slate-900">No assignments or activities yet.</p>
            <p class="mt-2 text-sm text-slate-500">Assignments and activities from your adviser will appear here.</p>
        </div>
    @endforelse
</div>
