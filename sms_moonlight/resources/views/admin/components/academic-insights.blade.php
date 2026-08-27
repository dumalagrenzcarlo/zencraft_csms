<section class="dashboard-section col-span-12" aria-labelledby="academic-insights-title">
    <div class="dashboard-panel overflow-hidden rounded-xl border shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <h2 id="academic-insights-title" class="dashboard-heading text-lg font-bold">Academic intelligence</h2>
            <p class="dashboard-muted mt-1 text-sm">Grade coverage, learners needing support, and quiz participation for the current filters.</p>
        </div>

        <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
            <article class="dashboard-card rounded-xl border p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Grade coverage</p>
                <p class="dashboard-value mt-2 text-2xl font-bold">{{ number_format($summary['grade_coverage'], 1) }}%</p>
                <p class="dashboard-muted mt-1 text-sm">{{ number_format($summary['grade_records']) }} of {{ number_format($summary['expected_grade_records']) }} expected student-subject records.</p>
            </article>
            <article class="dashboard-card rounded-xl border p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">At-risk learners</p>
                <p class="mt-2 text-2xl font-bold {{ $summary['at_risk_students'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-emerald-700 dark:text-emerald-300' }}">{{ number_format($summary['at_risk_students']) }}</p>
                <p class="dashboard-muted mt-1 text-sm">Students with a recorded subject average below 75.</p>
            </article>
            <article class="dashboard-card rounded-xl border p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Quiz participation</p>
                <p class="dashboard-value mt-2 text-2xl font-bold">{{ $summary['quiz_enabled'] ? number_format($summary['quiz_participation'], 1).'%' : 'Disabled' }}</p>
                <p class="dashboard-muted mt-1 text-sm">{{ $summary['quiz_enabled'] ? number_format($summary['quiz_participants']).' of '.number_format($summary['enrolled_students']).' enrolled students submitted an answer.' : 'Enable the quiz module to track student participation.' }}</p>
            </article>
            <article class="dashboard-card rounded-xl border p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Quiz accuracy</p>
                <p class="dashboard-value mt-2 text-2xl font-bold">{{ ! $summary['quiz_enabled'] ? 'Disabled' : ($summary['quiz_accuracy'] === null ? 'No data' : number_format($summary['quiz_accuracy'], 1).'%') }}</p>
                <p class="dashboard-muted mt-1 text-sm">{{ $summary['quiz_enabled'] ? 'Based on '.number_format($summary['quiz_answers']).' submitted quiz answers.' : 'Quiz accuracy is hidden while the module is disabled.' }}</p>
            </article>
        </div>

        <div class="border-t border-slate-200 dark:border-slate-700">
            <div class="px-5 py-3">
                <h3 class="dashboard-heading font-bold">Lowest recorded student averages</h3>
            </div>
            @if (empty($students))
                <p class="px-5 pb-5 text-sm text-slate-600 dark:text-slate-300">No grades are recorded for the selected students.</p>
            @else
                <div class="dashboard-table-scroll">
                    <table class="dashboard-table dashboard-table-medium w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                            <tr><th class="px-5 py-3">Student</th><th class="px-4 py-3">Class</th><th class="px-4 py-3 text-right">Average</th><th class="px-4 py-3">Status</th><th class="px-5 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach ($students as $student)
                                <tr>
                                    <td class="dashboard-value px-5 py-3 font-bold">{{ $student['name'] }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $student['class'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($student['average'], 2) }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $student['tone'] === 'danger' ? 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-200' : ($student['tone'] === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-200' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-200') }}">{{ $student['status'] }}</span></td>
                                    <td class="px-5 py-3 text-right"><a href="{{ $student['url'] }}" class="dashboard-table-action font-bold text-[var(--primary-color)] hover:underline">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>
