<section class="dashboard-section col-span-12" aria-labelledby="dashboard-actions-title">
    <div class="dashboard-panel rounded-xl border p-5 shadow-sm">
        <div>
            <h2 id="dashboard-actions-title" class="dashboard-heading text-lg font-bold">Quick actions</h2>
            <p class="dashboard-muted mt-1 text-sm">Go directly from an insight to the administrative task that resolves it.</p>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" class="dashboard-card group flex min-h-28 items-start gap-4 rounded-xl border p-4 transition hover:-translate-y-0.5 hover:border-[var(--primary-color)] hover:shadow-sm">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $action['tone'] === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-200' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-100' }}">
                        <x-moonshine::icon :icon="$action['icon']" class="h-5 w-5" />
                    </span>
                    <span>
                        <span class="dashboard-value font-bold group-hover:text-[var(--primary-color)]">{{ $action['label'] }}</span>
                        <span class="dashboard-muted mt-1 block text-sm leading-5">{{ $action['description'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
