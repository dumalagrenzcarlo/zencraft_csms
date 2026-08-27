@if(session('success'))
    <div class="alert alert-success mb-4">
        <strong>Success</strong><br>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error mb-4">
        <strong>Import Failed</strong>

        <ul class="mt-2 mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="alert alert-secondary p-3 mb-6">
    <ol class="mt-2 mb-0 ol-override">
        <li> <a href="{{ route('admin.students.template') }}" class="text-primary">Download</a> the template.</li>
        <li>Fill in the student details.</li>
        <li>Save the file as Excel (.xlsx).</li>
        <li>Upload the completed file and click Import. Accepted formats: XLSX, XLS.</li>
    </ol>
</div>

<form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
    @csrf

    <div class="flex flex-col items-center gap-3">
    <label for="student-import-file"
           class="btn btn-primary flex items-center gap-2 cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-5 w-5"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <span>Select Excel File</span>
    </label>

    <input
        id="student-import-file"
        type="file"
        name="file"
        accept=".xlsx,.xls"
        required
        class="hidden"
    >

    <div id="selected-file" class="text-sm text-primary mb-4">
        No file selected
    </div>
</div>


    <div class="mb-6">
        <em class="text-sm text-muted mb2">Note: The import process may take a few moments. Please do not refresh the page
        until you see a success message.</em>
    </div>
    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary"
            onclick="this.disabled=true;this.innerHTML='Importing...';this.form.submit();">
            Import Students
        </button>
    </div>
</form>

<style>
    .mb-2 {
        margin-bottom: 1rem;
    }

    .ol-override {
        list-style: auto;
        margin-left: 1rem;
    }
</style>


<script>
document.getElementById('student-import-file').addEventListener('change', function () {
    const fileName = this.files.length
        ? this.files[0].name
        : 'No file selected';

    document.getElementById('selected-file').textContent = fileName;
});
</script>