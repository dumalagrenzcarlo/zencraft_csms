<div class="col-span-{{ $columnSpan }}">
    <div class="chartjs-wrapper dashboard-chart" style="position: relative; width: 100%; height: 320px;" data-chart-wrapper>
        <canvas id="{{ $id }}"></canvas>
        <p class="hidden rounded-lg bg-amber-50 p-4 text-sm font-semibold text-amber-800" data-chart-error>
            This chart could not be loaded. The dashboard summaries remain available above.
        </p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById(@json($id));

        if (!canvas || typeof Chart === 'undefined') {
            const wrapper = canvas?.closest('[data-chart-wrapper]');
            canvas?.classList.add('hidden');
            wrapper?.querySelector('[data-chart-error]')?.classList.remove('hidden');
            return;
        }

        const ctx = canvas.getContext('2d');

        const chartConfig = {
            type: @json($type),
            data: @json($chartData),
            options: {
                responsive: true,
                maintainAspectRatio: false, // Important for responsive height
                ...@json($chartOptions),
            },
        };

        new Chart(ctx, chartConfig);
    });
</script>
@endpush
