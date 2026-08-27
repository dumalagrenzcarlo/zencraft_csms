<section class="dashboard-section col-span-12 dashboard-panel rounded-xl border p-5 shadow-sm" aria-labelledby="attendance-summary-title">
    <h2 id="attendance-summary-title" class="dashboard-heading text-lg font-bold">Attendance/Class Summary</h2>

    <div class="dashboard-summary-grid mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($metrics as $metric)
            <article class="dashboard-card dashboard-summary-card rounded-xl border p-4">
                <p class="dashboard-value text-2xl font-bold">{{ $metric['value'] }}</p>
                <h3 class="dashboard-muted mt-1 text-sm font-semibold">{{ $metric['label'] }}</h3>
            </article>
        @endforeach
    </div>
</section>
