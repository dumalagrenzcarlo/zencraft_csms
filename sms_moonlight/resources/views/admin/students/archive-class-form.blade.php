<section class="archive-class-panel" aria-labelledby="archive-class-title">
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <div class="archive-class-panel__heading">
        <div>
            <h2 id="archive-class-title">Archive an Entire Class</h2>
            <p>Archive every active student in a class and immediately disable their portal access.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.student-archive.class-selected') }}" class="archive-class-form">
        @csrf
        <div class="archive-class-field">
            <label for="archive-class-select">Class to archive</label>
            <select id="archive-class-select" name="class_id" required @disabled($classes->isEmpty())>
                <option value="">Select a class</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}">
                        {{ $class->grade?->grade }} — {{ $class->section }}
                        ({{ $class->schoolYear?->school_year }}, {{ $class->active_student_count }} active)
                    </option>
                @endforeach
            </select>
            <span>Selecting a class will archive all of its active students.</span>
        </div>

        <button type="submit" class="btn btn-primary" onclick="return confirm('Archive every active student in this class?')" @disabled($classes->isEmpty())>
            Archive Class Students
        </button>
    </form>
</section>

<style>
    .archive-class-panel {
        margin-bottom: 1.25rem;
        padding: 1.25rem;
        border: 1px solid color-mix(in srgb, var(--color-primary) 35%, var(--color-base-stroke));
        border-radius: .875rem;
        background: color-mix(in srgb, var(--color-primary) 4%, var(--color-base));
        box-shadow: 0 1px 2px rgb(15 23 42 / .05);
    }

    .archive-class-panel__heading h2 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 750;
    }

    .archive-class-panel__heading p {
        margin: .3rem 0 0;
        color: color-mix(in srgb, var(--color-base-text) 72%, transparent);
    }

    .archive-class-form {
        display: grid;
        grid-template-columns: minmax(18rem, 1fr) auto;
        gap: 1rem;
        align-items: start;
        margin-top: 1rem;
    }

    .archive-class-field {
        display: grid;
        gap: .4rem;
    }

    .archive-class-field label {
        color: var(--color-primary);
        font-size: .875rem;
        font-weight: 750;
        letter-spacing: .01em;
    }

    .archive-class-field select {
        width: 100%;
        min-height: 2.9rem;
        padding: .65rem 2.75rem .65rem .85rem;
        border: 1px solid rgb(100 116 139 / .65);
        border-radius: .55rem;
        background-color: var(--color-base);
        color: var(--color-base-text);
        box-shadow: 0 1px 2px rgb(15 23 42 / .06);
        appearance: auto;
    }

    .archive-class-field select:focus {
        border-color: var(--color-primary);
        outline: 3px solid color-mix(in srgb, var(--color-primary) 18%, transparent);
        outline-offset: 1px;
    }

    .archive-class-field > span {
        color: rgb(100 116 139);
        font-size: .78rem;
    }

    .archive-class-form > .btn {
        min-height: 2.9rem;
        margin-top: 1.65rem;
    }

    @media (max-width: 760px) {
        .archive-class-form {
            grid-template-columns: 1fr;
        }

        .archive-class-form .btn {
            width: 100%;
            margin-top: 0;
        }
    }
</style>
