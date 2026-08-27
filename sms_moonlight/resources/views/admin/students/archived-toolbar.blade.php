<div class="archived-students-toolbar">
    <form method="GET" action="{{ request()->url() }}" class="archived-students-search" role="search">
        @foreach ((array) request()->query('filter', []) as $filter => $value)
            @if (is_array($value))
                @foreach ($value as $key => $nestedValue)
                    @if (filled($nestedValue))
                        <input type="hidden" name="filter[{{ $filter }}][{{ $key }}]" value="{{ $nestedValue }}">
                    @endif
                @endforeach
            @elseif (filled($value))
                <input type="hidden" name="filter[{{ $filter }}]" value="{{ $value }}">
            @endif
        @endforeach

        <label for="archived-student-search">Search archived students</label>
        <div class="archived-students-search__controls">
            <input
                id="archived-student-search"
                name="search"
                type="search"
                value="{{ request()->query('search') }}"
                placeholder="Student number or student name"
            >
            <button type="submit" class="btn btn-primary">Search</button>
            @if (request()->filled('search'))
                <a href="{{ request()->url() }}" class="btn btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    <a href="{{ route('admin.students.archived.export', request()->query()) }}" class="btn btn-secondary archived-students-export">
        <x-moonshine::icon icon="arrow-down-tray" />
        Export
    </a>
</div>

<style>
    .archived-students-toolbar {
        display: grid;
        grid-template-columns: minmax(20rem, 1fr) auto;
        gap: 1rem;
        align-items: end;
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid var(--color-base-stroke);
        border-radius: .75rem;
        background: var(--color-base);
    }

    .archived-students-search {
        display: grid;
        gap: .4rem;
    }

    .archived-students-search label {
        font-size: .875rem;
        font-weight: 700;
    }

    .archived-students-search__controls {
        display: flex;
        gap: .6rem;
    }

    .archived-students-search input[type="search"] {
        width: 100%;
        min-height: 2.75rem;
        padding: .6rem .8rem;
        border: 1px solid color-mix(in srgb, var(--color-base-text) 32%, var(--color-base-stroke));
        border-radius: .55rem;
        background: var(--color-base);
        color: var(--color-base-text);
        box-shadow: 0 1px 2px rgb(15 23 42 / .05);
    }

    .archived-students-search input[type="search"]:focus {
        border-color: var(--color-primary);
        outline: 3px solid color-mix(in srgb, var(--color-primary) 18%, transparent);
        outline-offset: 1px;
    }

    .archived-students-export {
        min-height: 2.75rem;
        white-space: nowrap;
    }

    @media (max-width: 760px) {
        .archived-students-toolbar {
            grid-template-columns: 1fr;
        }

        .archived-students-search__controls {
            flex-wrap: wrap;
        }

        .archived-students-search input[type="search"] {
            flex-basis: 100%;
        }

        .archived-students-export {
            width: 100%;
        }
    }
</style>
