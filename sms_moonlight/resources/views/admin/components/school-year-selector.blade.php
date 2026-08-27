<div class="col-span-12 flex justify-end">
    <form method="GET" action="{{ url()->current() }}" class="box flex w-full max-w-xs items-center gap-3 rounded-lg bg-white p-3 shadow-sm">
        <label for="school_year_id" class="whitespace-nowrap text-sm font-semibold text-slate-700">
            School Year
        </label>

        <select
            id="school_year_id"
            name="school_year_id"
            class="min-w-0 flex-1 rounded-md border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
            onchange="this.form.submit()"
        >
            @foreach($schoolYears as $id => $label)
                <option value="{{ $id }}" @selected((int) $id === (int) $selectedSchoolYearId)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </form>
</div>
