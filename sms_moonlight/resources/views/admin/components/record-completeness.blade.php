<section class="dashboard-section col-span-12" aria-labelledby="record-completeness-title">
    <div class="dashboard-panel overflow-hidden rounded-xl border shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <h2 id="record-completeness-title" class="dashboard-heading text-lg font-bold">Student record completeness</h2>
            <p class="dashboard-muted mt-1 text-sm">Required follow-up across {{ number_format($summary['students']) }} students in the current filters.</p>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => $summary['rfid_enabled'] ? 'Missing RFID' : 'RFID disabled', 'value' => $summary['missing_rfid']],
                ['label' => 'Missing profile photo', 'value' => $summary['missing_photo']],
                ['label' => 'Missing birthdate', 'value' => $summary['missing_dob']],
                ['label' => 'Missing guardian', 'value' => $summary['missing_guardian']],
            ] as $item)
                <article class="dashboard-card rounded-xl border {{ $item['value'] > 0 ? 'border-amber-200 bg-amber-50/70 dark:border-amber-900/70 dark:bg-amber-950/30' : 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/70 dark:bg-emerald-950/30' }} p-4" data-dashboard-tone="{{ $item['value'] > 0 ? 'warning' : 'success' }}">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $item['label'] }}</p>
                    <p class="dashboard-value mt-2 text-2xl font-bold">{{ number_format($item['value']) }}</p>
                </article>
            @endforeach
        </div>

        @if (! empty($students))
            <div class="border-t border-slate-200 dark:border-slate-700">
                <div class="dashboard-table-scroll">
                    <table class="dashboard-table dashboard-table-medium w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                            <tr><th class="px-5 py-3">Student</th><th class="px-4 py-3">Class</th><th class="px-4 py-3">Missing information</th><th class="px-5 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach ($students as $student)
                                <tr>
                                    <td class="dashboard-value px-5 py-3 font-bold">{{ $student['name'] }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $student['class'] }}</td>
                                    <td class="px-4 py-3 text-amber-700 dark:text-amber-300">{{ $student['issues'] }}</td>
                                    <td class="px-5 py-3 text-right"><a href="{{ $student['url'] }}" class="dashboard-table-action font-bold text-[var(--primary-color)] hover:underline">Complete record</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</section>
