<?php

namespace Tests\Unit;

use App\MoonShine\Components\AttendanceSummary;
use App\MoonShine\Components\chartjs;
use App\MoonShine\Components\DashboardHealth;
use App\MoonShine\Pages\Dashboard;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class DashboardMetricTest extends TestCase
{
    public function test_average_attendance_is_reported_as_a_percentage_of_enrollment(): void
    {
        $dailyAttendance = collect([7, 7, 6, 6, 6, 2, 1])
            ->map(fn (int $total): array => ['total' => $total]);

        $method = new ReflectionMethod(Dashboard::class, 'averageAttendanceRate');
        $rate = $method->invoke(app(Dashboard::class), $dailyAttendance, 8);

        $this->assertSame(62.5, $rate);
    }

    public function test_empty_attendance_does_not_create_a_fake_attendance_day(): void
    {
        $dashboard = app(Dashboard::class);
        $method = new ReflectionMethod(Dashboard::class, 'dailyAttendance');

        /** @var Collection $dailyAttendance */
        $dailyAttendance = $method->invoke($dashboard, collect());

        $this->assertTrue($dailyAttendance->isEmpty());

        $rateMethod = new ReflectionMethod(Dashboard::class, 'averageAttendanceRate');
        $this->assertSame(0.0, $rateMethod->invoke($dashboard, $dailyAttendance, 0));
    }

    public function test_health_component_exposes_the_selected_school_year_and_actions(): void
    {
        $items = [[
            'tone' => 'danger',
            'label' => 'Enrollment health',
            'value' => '8 of 960 enrolled',
            'description' => '956 active student records are not assigned.',
            'action_label' => 'Review students',
            'action_url' => '/admin/resource/student-resource/index-page',
        ]];
        $component = DashboardHealth::make('2025-2026', $items);
        $method = new ReflectionMethod(DashboardHealth::class, 'viewData');
        $data = $method->invoke($component);

        $this->assertSame('2025-2026', $data['schoolYear']);
        $this->assertSame($items, $data['items']);
    }

    public function test_attendance_timeline_marks_missing_collection_days_without_counting_absences(): void
    {
        $dailyAttendance = collect([
            ['date' => '2026-01-05', 'label' => 'Jan 05', 'total' => 7],
            ['date' => '2026-01-07', 'label' => 'Jan 07', 'total' => 6],
        ]);
        $method = new ReflectionMethod(Dashboard::class, 'attendanceTimeline');

        /** @var Collection $timeline */
        $timeline = $method->invoke(
            app(Dashboard::class),
            $dailyAttendance,
            8,
            '2026-01-05',
            '2026-01-07',
        );

        $this->assertSame(['recorded', 'no_data', 'recorded'], $timeline->pluck('status')->all());
        $this->assertSame([1, null, 2], $timeline->pluck('absent')->all());
    }

    public function test_monthly_attendance_uses_a_percentage_denominator(): void
    {
        $dailyAttendance = collect([
            ['date' => '2026-01-05', 'label' => 'Jan 05', 'total' => 7],
            ['date' => '2026-01-06', 'label' => 'Jan 06', 'total' => 5],
        ]);
        $method = new ReflectionMethod(Dashboard::class, 'averageAttendancePerMonth');

        /** @var Collection $monthly */
        $monthly = $method->invoke(app(Dashboard::class), $dailyAttendance, 8);

        $this->assertSame(75.0, $monthly->first()['rate']);
    }

    public function test_dashboard_cache_is_reused_and_can_be_explicitly_refreshed(): void
    {
        config()->set('school_portal.dashboard.cache_seconds', 60);
        request()->query->remove('refresh');
        $calls = 0;
        $method = new ReflectionMethod(Dashboard::class, 'rememberDashboard');
        $dashboard = app(Dashboard::class);
        $context = ['test' => uniqid('dashboard-cache-', true)];
        $callback = function () use (&$calls): int {
            return ++$calls;
        };

        $this->assertSame(1, $method->invoke($dashboard, 'unit-test', $context, $callback));
        $this->assertSame(1, $method->invoke($dashboard, 'unit-test', $context, $callback));
        $this->assertSame(1, $calls);

        request()->query->set('refresh', '1');
        $this->assertSame(2, $method->invoke($dashboard, 'unit-test', $context, $callback));
        $this->assertSame(2, $calls);
        request()->query->remove('refresh');
    }

    public function test_chartjs_uses_the_bundled_local_asset(): void
    {
        $method = new ReflectionMethod(chartjs::class, 'assets');
        $assets = $method->invoke(chartjs::make());
        $link = $assets[0]->getLink();

        $this->assertStringContainsString('/vendor/chartjs/chart.umd.js', $link);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $link);
        $this->assertFileExists(public_path('vendor/chartjs/chart.umd.js'));
    }

    public function test_attendance_summary_uses_one_lightweight_component(): void
    {
        $metrics = [
            ['label' => 'Enrolled Students', 'value' => '8'],
            ['label' => 'Avg Attendance Rate', 'value' => '62.5%'],
        ];
        $component = AttendanceSummary::make($metrics);
        $method = new ReflectionMethod(AttendanceSummary::class, 'viewData');

        $this->assertSame($metrics, $method->invoke($component)['metrics']);

        $dashboard = file_get_contents(app_path('MoonShine/Pages/Dashboard.php'));
        $this->assertStringContainsString('AttendanceSummary::make', $dashboard);
        $this->assertStringNotContainsString('ValueMetric::make', $dashboard);
    }

    public function test_dashboard_views_include_explicit_responsive_and_theme_hooks(): void
    {
        $attendance = file_get_contents(resource_path('views/admin/components/attendance-attention.blade.php'));
        $filters = file_get_contents(resource_path('views/admin/components/dashboard-filters.blade.php'));
        $layout = file_get_contents(app_path('MoonShine/Layouts/CustomLayout.php'));

        $this->assertStringContainsString('dashboard-table-scroll', $attendance);
        $this->assertStringContainsString('dashboard-table-wide', $attendance);
        $this->assertStringContainsString('dashboard-control', $filters);
        $this->assertStringContainsString('.dashboard-panel', $layout);
        $this->assertStringContainsString('html.dark .dashboard-panel', $layout);
        $this->assertStringContainsString('--ms-layout-vertical-menu-width: 248px', $layout);
    }
}
