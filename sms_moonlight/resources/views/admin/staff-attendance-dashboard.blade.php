<div class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-dark-800">
    <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-6">
        <label>
            <span class="mb-1 block text-sm font-semibold">Start date</span>
            <input class="form-input" type="date" name="start_date" value="{{ $startDate }}">
        </label>
        <label>
            <span class="mb-1 block text-sm font-semibold">End date</span>
            <input class="form-input" type="date" name="end_date" value="{{ $endDate }}">
        </label>
        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-semibold">Staff member</span>
            <input
                class="form-input"
                type="search"
                name="search"
                value="{{ $filters['search'] }}"
                placeholder="Search name, position, or department"
            >
        </label>
        <label>
            <span class="mb-1 block text-sm font-semibold">Type</span>
            <select class="form-select" name="staff_type">
                <option value="">All types</option>
                <option value="{{ \App\Models\Adviser::TYPE_TEACHER }}" @selected($filters['staff_type'] === \App\Models\Adviser::TYPE_TEACHER)>Teacher</option>
                <option value="{{ \App\Models\Adviser::TYPE_STAFF }}" @selected($filters['staff_type'] === \App\Models\Adviser::TYPE_STAFF)>Staff</option>
            </select>
        </label>
        <label>
            <span class="mb-1 block text-sm font-semibold">Status</span>
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <option value="On time" @selected($filters['status'] === 'On time')>On time</option>
                <option value="Late" @selected($filters['status'] === 'Late')>Late</option>
            </select>
        </label>
        <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-6">
            <button class="btn btn-primary" type="submit">Apply filters</button>
            <a class="btn btn-secondary" href="{{ url()->current() }}">Reset</a>
            <a
                class="btn btn-secondary"
                href="{{ route('admin.staff-attendance.export', array_filter([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    ...$filters,
                ])) }}"
            >Export to Excel</a>
        </div>
    </form>
</div>
