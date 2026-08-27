<section class="dashboard-section col-span-12" aria-labelledby="attendance-attention-title">
    <div class="dashboard-panel overflow-hidden rounded-xl border shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <h2 id="attendance-attention-title" class="dashboard-heading text-lg font-bold">Students needing attendance follow-up</h2>
            <p class="dashboard-muted mt-1 text-sm">
                Students below {{ number_format($threshold, 0) }}% across {{ number_format($recordedDays) }} recorded attendance days.
            </p>
        </div>

        @if ($recordedDays === 0)
            <p class="p-5 text-sm text-slate-600 dark:text-slate-300">No recorded attendance days are available for the selected filters.</p>
        @elseif (empty($students))
            <p class="p-5 text-sm text-emerald-700 dark:text-emerald-300">No students are below the attendance threshold.</p>
        @else
            <div class="dashboard-table-scroll">
                <table class="dashboard-table dashboard-table-wide w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-4 py-3">Class</th>
                            <th class="px-4 py-3 text-right">Present</th>
                            <th class="px-4 py-3 text-right">Absent</th>
                            <th class="px-4 py-3 text-right">Rate</th>
                            <th class="px-4 py-3">Last attendance</th>
                            <th class="px-5 py-3"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach ($students as $student)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="dashboard-value font-bold">{{ $student['name'] }}</p>
                                    <p class="dashboard-muted text-xs">{{ $student['lrn'] }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $student['class'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format($student['present']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-red-700 dark:text-red-300">{{ number_format($student['absent']) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 font-bold text-red-700 dark:bg-red-950/50 dark:text-red-200">{{ number_format($student['rate'], 1) }}%</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $student['last_attendance'] }}</td>
                                <td class="px-5 py-3 text-right"><a href="{{ $student['url'] }}" class="dashboard-table-action font-bold text-[var(--primary-color)] hover:underline">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
