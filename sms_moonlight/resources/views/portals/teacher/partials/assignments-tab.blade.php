<div class="border-b border-slate-200 p-4 sm:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-900">Assignments and Activities</h3>
            <p class="text-xs text-slate-500">Create drafts, send files to class, and review submissions.</p>
        </div>
        @if($selectedClass?->enable_assignments)
            <button
                type="button"
                onclick="openCreateAssignmentModal()"
                class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                Create Assignment
            </button>
        @endif
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if(! $selectedClass?->enable_assignments)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Enable Assignments and Activities for this class in the admin Class settings before sending items.
        </div>
    @endif
</div>

<div class="w-full overflow-x-auto">
    <table class="min-w-[860px] w-full text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Assignment</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Deadline</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Submissions</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($assignments as $assignmentItem)
                @php
                    $updateFormId = 'assignment-update-' . $assignmentItem->id;
                    $editKey = 'assignment-edit-' . $assignmentItem->id;
                @endphp
                <tr class="hover:bg-slate-50" data-assignment-edit-row="{{ $editKey }}">
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="max-w-[260px] truncate font-semibold text-slate-900">{{ $assignmentItem->title }}</p>
                            @if($assignmentItem->sent_at)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Sent</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Draft</span>
                            @endif
                        </div>
                        <p class="mt-0.5 max-w-[280px] truncate text-xs text-slate-500">{{ $assignmentItem->file_name }}</p>
                    </td>
                    <td class="px-3 py-2 text-xs text-slate-600">
                        <span data-assignment-display="{{ $editKey }}">
                            {{ $assignmentItem->deadline?->format('M j, Y g:i A') ?? '-' }}
                        </span>
                        <div data-assignment-editor="{{ $editKey }}" class="hidden">
                            <input
                                form="{{ $updateFormId }}"
                                name="deadline"
                                type="datetime-local"
                                required
                                min="{{ $assignmentItem->deadline?->format('Y-m-d\TH:i') }}"
                                value="{{ $assignmentItem->deadline?->format('Y-m-d\TH:i') }}"
                                class="w-full min-w-[180px] rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none">
                            <p class="mt-1 text-[11px] text-slate-400">Extension only</p>
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                            {{ $assignmentItem->submissions_count }} / {{ $studentCount }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-xs text-slate-600">
                        <span class="block max-w-[260px] truncate" data-assignment-display="{{ $editKey }}">
                            {{ $assignmentItem->notes ?? '-' }}
                        </span>
                        <textarea
                            form="{{ $updateFormId }}"
                            name="notes"
                            rows="2"
                            data-assignment-editor="{{ $editKey }}"
                            class="hidden w-full min-w-[220px] rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-xs focus:border-indigo-500 focus:outline-none"
                            placeholder="Notes">{{ $assignmentItem->notes }}</textarea>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <div class="flex flex-wrap justify-end gap-1.5">
                            <button
                                type="button"
                                title="Toggle edit"
                                aria-label="Toggle edit"
                                onclick="toggleAssignmentEdit('{{ $editKey }}', this)"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                                data-editing="false">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                            </button>
                            <form id="{{ $updateFormId }}" method="POST" action="{{ route('teacher.assignments.update', ['assignment' => $assignmentItem]) }}">
                                @csrf
                                @method('PUT')
                                <button
                                    type="submit"
                                    title="Save changes"
                                    aria-label="Save changes"
                                    data-assignment-editor="{{ $editKey }}"
                                    class="hidden h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white hover:bg-indigo-500">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" />
                                        <path d="M17 21v-8H7v8" />
                                        <path d="M7 3v5h8" />
                                    </svg>
                                </button>
                            </form>
                            <a href="{{ route('teacher.assignments.download', ['assignment' => $assignmentItem]) }}"
                                title="Download file"
                                aria-label="Download file"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 3v12" />
                                    <path d="m7 10 5 5 5-5" />
                                    <path d="M5 21h14" />
                                </svg>
                            </a>
                            <button type="button"
                                title="View summary"
                                aria-label="View summary"
                                onclick="openAssignmentSummary('{{ $assignmentItem->id }}')"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 19V5" />
                                    <path d="M8 19V9" />
                                    <path d="M12 19V7" />
                                    <path d="M16 19v-4" />
                                    <path d="M20 19V11" />
                                </svg>
                            </button>
                            @if(! $assignmentItem->sent_at)
                                <form method="POST" action="{{ route('teacher.assignments.send', ['assignment' => $assignmentItem]) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        title="Send to class"
                                        aria-label="Send to class"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-white hover:bg-emerald-500">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m22 2-7 20-4-9-9-4Z" />
                                            <path d="M22 2 11 13" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                            @if((int) $assignmentItem->submissions_count === 0)
                                <form method="POST" action="{{ route('teacher.assignments.delete', ['assignment' => $assignmentItem]) }}" onsubmit="return confirm('Delete this assignment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        title="Delete assignment"
                                        aria-label="Delete assignment"
                                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-red-200 bg-white text-red-600 hover:bg-red-50">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18" />
                                            <path d="M8 6V4h8v2" />
                                            <path d="M19 6 18 20H6L5 6" />
                                            <path d="M10 11v5" />
                                            <path d="M14 11v5" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach

            @if($assignments->isEmpty())
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                        No assignments or activities found for this class.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div id="createAssignmentModal" role="dialog" aria-modal="true" aria-labelledby="createAssignmentModalTitle"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h3 id="createAssignmentModalTitle" class="text-xl font-bold text-slate-900">Create Assignment</h3>
                <p class="mt-1 text-sm text-slate-500">Create a draft first. You can send it to students after review.</p>
            </div>
            <button type="button" onclick="closeCreateAssignmentModal()" data-dialog-close aria-label="Close create assignment dialog"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100">
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('teacher.assignments.store') }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClass?->id }}">

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Assignment Title</label>
                    <input
                        name="title"
                        type="text"
                        required
                        maxlength="200"
                        placeholder="Assignment title"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Due Date</label>
                    <input
                        name="deadline"
                        type="datetime-local"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">File</label>
                    <input
                        name="file"
                        type="file"
                        required
                        accept=".doc,.docx,.pdf,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Notes</label>
                    <textarea
                        name="notes"
                        rows="3"
                        placeholder="Notes"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeCreateAssignmentModal()" data-dialog-close class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                    Create Draft
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openCreateAssignmentModal = window.openCreateAssignmentModal || function() {
        const modal = document.getElementById('createAssignmentModal');
        window.portalDialog.open(modal);
    };

    window.closeCreateAssignmentModal = window.closeCreateAssignmentModal || function() {
        const modal = document.getElementById('createAssignmentModal');
        window.portalDialog.close(modal);
    };

    window.toggleAssignmentEdit = window.toggleAssignmentEdit || function(key, button) {
        const isEditing = button.dataset.editing === 'true';

        document.querySelectorAll(`[data-assignment-display="${key}"]`).forEach((element) => {
            element.classList.toggle('hidden', !isEditing);
        });

        document.querySelectorAll(`[data-assignment-editor="${key}"]`).forEach((element) => {
            element.classList.toggle('hidden', isEditing);
            element.classList.toggle('inline-flex', !isEditing && element.tagName === 'BUTTON');
        });

        button.dataset.editing = isEditing ? 'false' : 'true';
        button.classList.toggle('bg-slate-900', !isEditing);
        button.classList.toggle('text-white', !isEditing);
        button.classList.toggle('bg-white', isEditing);
        button.classList.toggle('text-slate-600', isEditing);
    };
</script>
