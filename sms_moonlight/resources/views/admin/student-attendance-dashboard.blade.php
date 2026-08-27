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
        <label class="md:col-span-2 xl:col-span-3">
            <span class="mb-1 block text-sm font-semibold">Student</span>
            <input
                class="form-input"
                type="search"
                name="search"
                value="{{ $filters['search'] }}"
                placeholder="Search student name or number"
            >
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
        </div>
    </form>
</div>
