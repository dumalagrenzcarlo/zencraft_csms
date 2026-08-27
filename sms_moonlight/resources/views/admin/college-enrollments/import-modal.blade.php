@php($activeSchoolYear = \App\Models\SchoolYear::query()->where('active', true)->first())
@php($activeCourseCodes = \App\Models\CollegeProgram::query()->where('active', true)->orderBy('code')->pluck('code'))

<div class="college-enrolment-import">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <strong>Import failed</strong>
            <ul class="mt-2 ml-4 list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-secondary import-instructions">
        <ol class="list-decimal">
            <li><a href="{{ route('admin.college-enrollments.template') }}" class="text-primary font-semibold">Download the Excel template</a>.</li>
            <li>Add one enrolment per row without changing the column headings.</li>
            <li>Save the completed file as XLSX or XLS.</li>
            <li>Select the file below and click <strong>Import Enrolments</strong>.</li>
        </ol>
    </div>

    <div class="active-school-year rounded-lg border">
        <strong>Active School Year:</strong>
        @if($activeSchoolYear)
            {{ $activeSchoolYear->school_year }}
            <p class="text-muted">This school year is applied to every imported row, regardless of the value shown in the file.</p>
        @else
            <span class="text-danger">None configured</span>
            <p class="text-danger">Set an active school year before importing.</p>
        @endif
    </div>

    <div class="expected-values overflow-x-auto">
    <table class="table w-full">
        <thead><tr><th>Field</th><th>Expected value</th></tr></thead>
        <tbody>
            <tr><td><strong>LRN</strong></td><td>Required; an existing student's exact LRN. Keep it as text if it starts with zero.</td></tr>
            <tr><td><strong>Student Name</strong></td><td>Optional; for reference only. LRN is used to find the student.</td></tr>
            <tr>
                <td><strong>Course Code</strong></td>
                <td>
                    Required; one of the active college course codes:
                    @forelse($activeCourseCodes as $courseCode)
                        <code>{{ $courseCode }}</code>@if(!$loop->last), @endif
                    @empty
                        <span class="text-danger">No active course codes are configured.</span>
                    @endforelse
                </td>
            </tr>
            <tr><td><strong>School Year</strong></td><td>Automatically uses the active school year shown above.</td></tr>
            <tr><td><strong>Semester</strong></td><td><code>First Semester</code> or <code>Second Semester</code>. Values <code>1</code> and <code>2</code> are also accepted.</td></tr>
            <tr><td><strong>Year Level</strong></td><td>A whole number from <code>1</code> up to the selected course's duration.</td></tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>@include('admin.college-enrollments.status-explanation')</td>
            </tr>
        </tbody>
    </table>
    </div>

    <form class="import-form" method="POST" action="{{ route('admin.college-enrollments.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="file-picker-row">
            <label for="college-enrolment-import-file" class="btn btn-primary cursor-pointer">Select Excel File</label>
            <input id="college-enrolment-import-file" type="file" name="file" accept=".xlsx,.xls" required class="hidden">
            <span id="college-enrolment-selected-file" class="text-muted">No file selected</span>
        </div>

        <div class="form-actions flex justify-end">
            <button type="submit" class="btn btn-primary import-submit-button" @disabled(!$activeSchoolYear)>Import Enrolments</button>
        </div>
    </form>
</div>

<style>
    .college-enrolment-import {
        display: flex;
        flex-direction: column;
        gap: 18px;
        font-size: 14px;
        line-height: 1.5;
    }

    .college-enrolment-import .alert {
        margin: 0;
        font-size: 14px;
    }

    .college-enrolment-import .import-instructions {
        padding: 14px 16px;
    }

    .college-enrolment-import .import-instructions ol {
        margin: 0 0 0 20px;
    }

    .college-enrolment-import .import-instructions li + li {
        margin-top: 5px;
    }

    .college-enrolment-import .active-school-year {
        padding: 12px 14px;
    }

    .college-enrolment-import .active-school-year p {
        margin: 4px 0 0;
        font-size: 13px;
        line-height: 1.45;
    }

    .college-enrolment-import .expected-values table {
        margin: 0;
        font-size: 13px;
        line-height: 1.4;
    }

    .college-enrolment-import .expected-values th,
    .college-enrolment-import .expected-values td {
        padding: 10px 12px;
        vertical-align: middle;
    }

    .college-enrolment-import .expected-values th:first-child,
    .college-enrolment-import .expected-values td:first-child {
        width: 145px;
    }

    .college-enrolment-import .import-form {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding-top: 2px;
    }

    .college-enrolment-import .file-picker-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .college-enrolment-import .file-picker-row .btn,
    .college-enrolment-import .form-actions .btn {
        flex: 0 0 auto;
        font-size: 14px;
        line-height: 1.25;
    }

    .college-enrolment-import #college-enrolment-selected-file {
        min-width: 0;
        font-size: 13px;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .college-enrolment-import .form-actions {
        padding-top: 2px;
    }

    .college-enrolment-import .is-processing {
        cursor: wait;
    }

    .college-enrolment-import .is-processing .file-picker-row label,
    .college-enrolment-import .import-submit-button:disabled {
        cursor: not-allowed;
        opacity: .65;
        pointer-events: none;
    }

    @media (max-width: 640px) {
        .college-enrolment-import {
            gap: 14px;
        }

        .college-enrolment-import .expected-values th:first-child,
        .college-enrolment-import .expected-values td:first-child {
            width: 110px;
        }
    }
</style>

<script>
document.getElementById('college-enrolment-import-file')?.addEventListener('change', function () {
    document.getElementById('college-enrolment-selected-file').textContent = this.files.length ? this.files[0].name : 'No file selected';
});

document.querySelector('.college-enrolment-import .import-form')?.addEventListener('submit', function (event) {
    if (this.dataset.processing === 'true') {
        event.preventDefault();
        return;
    }

    this.dataset.processing = 'true';
    this.classList.add('is-processing');

    const submitButton = this.querySelector('.import-submit-button');
    const fileLabel = this.querySelector('.file-picker-row label');

    submitButton.disabled = true;
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.textContent = 'Importing…';
    fileLabel.setAttribute('aria-disabled', 'true');
});
</script>
