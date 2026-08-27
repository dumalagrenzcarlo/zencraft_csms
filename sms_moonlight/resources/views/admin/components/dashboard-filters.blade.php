<section class="dashboard-section col-span-12" aria-labelledby="dashboard-filter-title">
    <form method="GET" action="{{ url()->current() }}" class="box dashboard-panel rounded-xl p-4 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="dashboard-filter-title" class="dashboard-heading text-base font-bold">Dashboard filters</h2>
                @if ($boundaryMessage)
                    <p class="dashboard-warning-text mt-1 text-xs font-medium">{{ $boundaryMessage }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ request()->fullUrlWithQuery(['refresh' => 1]) }}" class="dashboard-link dashboard-muted text-sm font-bold hover:underline">Refresh data</a>
                <a href="{{ url()->current() }}" class="dashboard-link text-sm font-bold text-[var(--primary-color)] hover:underline">Reset filters</a>
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <label class="grid gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                School Year
                <select name="school_year_id" class="dashboard-control rounded-md border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    @foreach ($schoolYears as $id => $label)
                        <option value="{{ $id }}" @selected((int) $id === (int) $selected['school_year_id'])>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                Grade
                <select name="grade_id" class="dashboard-control rounded-md border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All grades</option>
                    @foreach ($grades as $id => $label)
                        <option value="{{ $id }}" @selected((int) $id === (int) $selected['grade_id'])>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                Class
                <select name="class_id" class="dashboard-control rounded-md border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All classes</option>
                    @foreach ($classes as $id => $label)
                        <option value="{{ $id }}" @selected((int) $id === (int) $selected['class_id'])>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                From
                <input type="date" name="date_from" value="{{ $selected['date_from'] }}" class="dashboard-control rounded-md border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
            </label>

            <label class="grid gap-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                To
                <input type="date" name="date_to" value="{{ $selected['date_to'] }}" class="dashboard-control rounded-md border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
            </label>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn btn-primary dashboard-control px-5">Apply filters</button>
        </div>
    </form>
</section>
