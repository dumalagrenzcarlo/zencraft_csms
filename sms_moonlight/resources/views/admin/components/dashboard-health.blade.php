<section class="dashboard-section col-span-12" aria-labelledby="dashboard-health-title">
    <div class="dashboard-panel rounded-xl border p-5 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-muted text-xs font-semibold uppercase tracking-[0.16em]">{{ $schoolYear }}</p>
                <h2 id="dashboard-health-title" class="dashboard-heading text-lg font-bold">Records needing attention</h2>
            </div>
            <p class="dashboard-muted text-sm">Live checks from enrollment, classes, and attendance</p>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($items as $item)
                @php
                    $toneClasses = match ($item['tone']) {
                        'danger' => 'border-red-200 bg-red-50/80 dark:border-red-900/70 dark:bg-red-950/30',
                        'warning' => 'border-amber-200 bg-amber-50/80 dark:border-amber-900/70 dark:bg-amber-950/30',
                        'success' => 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-900/70 dark:bg-emerald-950/30',
                        default => 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40',
                    };
                    $badgeClasses = match ($item['tone']) {
                        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-100',
                        'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-100',
                        'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-100',
                        default => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-100',
                    };
                @endphp

                <article class="dashboard-card flex min-h-48 flex-col rounded-xl border p-4 {{ $toneClasses }}" data-dashboard-tone="{{ $item['tone'] }}">
                    <span class="w-fit rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeClasses }}">{{ $item['label'] }}</span>
                    <p class="dashboard-value mt-3 text-xl font-bold">{{ $item['value'] }}</p>
                    <p class="dashboard-muted mt-1 flex-1 text-sm leading-5">{{ $item['description'] }}</p>
                    <a href="{{ $item['action_url'] }}" class="dashboard-link mt-4 inline-flex items-center text-sm font-bold text-[var(--primary-color)] hover:underline">
                        {{ $item['action_label'] }}
                        <span aria-hidden="true" class="ml-1">&rarr;</span>
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
