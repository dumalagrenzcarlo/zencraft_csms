<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\AttendanceRecord;
use App\Models\ClassesModel;
use App\Models\ClassStudent;
use App\Models\SchoolYear;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPaymentHistory;
use App\MoonShine\Components\AcademicInsights;
use App\MoonShine\Components\AttendanceAttention;
use App\MoonShine\Components\AttendanceSummary;
use App\MoonShine\Components\chartjs;
use App\MoonShine\Components\DashboardFilters;
use App\MoonShine\Components\DashboardHealth;
use App\MoonShine\Components\DashboardQuickActions;
use App\MoonShine\Components\RecordCompleteness;
use App\MoonShine\Resources\Announcement\AnnouncementResource;
use App\MoonShine\Resources\AttendanceRecord\AttendanceRecordResource;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use App\MoonShine\Resources\Student\StudentResource;
use App\Support\AttendanceTardySummary;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\UI\Components\Layout\Grid;

#[SkipMenu]
class Dashboard extends Page
{
    public function getTitle(): string
    {
        return $this->title ?: 'Dashboard';
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): array
    {
        $schoolYearModels = SchoolYear::query()
            ->orderByDesc('active')
            ->orderByDesc('id')
            ->get();
        $schoolYears = $schoolYearModels->pluck('school_year', 'id')->all();

        $activeSchoolYearId = $schoolYearModels->firstWhere('active', true)?->id;
        $selectedSchoolYearId = $this->selectedSchoolYearId($schoolYears, $activeSchoolYearId);
        $selectedSchoolYearModel = $selectedSchoolYearId !== null
            ? $schoolYearModels->firstWhere('id', $selectedSchoolYearId)
            : null;
        $selectedSchoolYear = $selectedSchoolYearId !== null
            ? ($schoolYears[$selectedSchoolYearId] ?? 'Selected school year')
            : 'No school year selected';
        $filterOptions = $this->rememberDashboard(
            'filter-options',
            ['school_year_id' => $selectedSchoolYearId],
            fn () => $this->filterOptions($selectedSchoolYearId),
        );
        $selectedGradeId = $this->selectedOptionId('grade_id', $filterOptions['grades']);
        $selectedClassId = $this->selectedClassId($filterOptions['classes'], $selectedGradeId);
        $dateRange = $this->selectedDateRange($selectedSchoolYearModel);
        $scope = [
            'school_year_id' => $selectedSchoolYearId,
            'grade_id' => $selectedGradeId,
            'class_id' => $selectedClassId,
            'date_from' => $dateRange['from'],
            'date_to' => $dateRange['to'],
        ];
        $classIds = $this->rememberDashboard(
            'class-ids',
            $scope,
            fn () => $this->classIdsForScope($selectedSchoolYearId, $selectedGradeId, $selectedClassId),
        );
        $schoolYearStudentIds = $this->rememberDashboard(
            'school-year-student-ids',
            ['school_year_id' => $selectedSchoolYearId],
            fn () => $this->studentIdsForSchoolYear($selectedSchoolYearId),
        );
        $studentIds = $selectedGradeId === null && $selectedClassId === null
            ? $schoolYearStudentIds
            : $this->rememberDashboard(
                'scoped-student-ids',
                $scope,
                fn () => $this->studentIdsForSchoolYear($selectedSchoolYearId, $classIds),
            );
        $enrolledStudentCount = $studentIds->count();
        $gradeDistribution = $this->rememberDashboard(
            'grade-distribution',
            $scope,
            fn () => $this->studentsPerGrade($selectedSchoolYearId, $classIds),
        );
        $dailyAttendance = $this->rememberDashboard(
            'daily-attendance',
            $scope,
            fn () => $this->dailyAttendance($studentIds, $dateRange['from'], $dateRange['to']),
        );
        $attendanceTimeline = $this->attendanceTimeline(
            $dailyAttendance,
            $enrolledStudentCount,
            $dateRange['from'],
            $dateRange['to'],
        );
        $monthlyAttendance = $this->averageAttendancePerMonth($dailyAttendance, $enrolledStudentCount);
        $averageAttendanceRate = $this->averageAttendanceRate($dailyAttendance, $enrolledStudentCount);
        $health = $this->rememberDashboard(
            'health',
            $scope,
            fn () => $this->dashboardHealth(
                $selectedSchoolYearId,
                $schoolYearStudentIds,
                $dailyAttendance,
                $selectedSchoolYearModel,
            ),
        );
        $lateByClass = $this->rememberDashboard(
            'late-by-class',
            $scope,
            fn () => AttendanceTardySummary::perClass(
                $selectedSchoolYearId,
                $classIds,
                $dateRange['from'],
                $dateRange['to'],
            ),
        );
        $totalLateArrivals = $lateByClass->sum('total');
        $presentStudentDays = (int) $dailyAttendance->sum('total');
        $absentStudentDays = max(0, ($dailyAttendance->count() * $enrolledStudentCount) - $presentStudentDays);
        $unrecordedWeekdays = $attendanceTimeline->where('status', 'no_data')->count();
        $studentsNeedingAttention = $this->rememberDashboard(
            'attendance-attention',
            $scope + ['recorded_days' => $dailyAttendance->count()],
            fn () => $this->studentsNeedingAttention(
                $selectedSchoolYearId,
                $classIds,
                $dailyAttendance->count(),
                $dateRange['from'],
                $dateRange['to'],
            ),
        );
        $academic = $this->rememberDashboard(
            'academic',
            $scope + ['quiz_enabled' => (bool) config('school_portal.features.quiz_module')],
            fn () => $this->academicInsights($selectedSchoolYearId, $classIds, $studentIds),
        );
        $rfidEnabled = Setting::enabled('rfid_enabled', true);
        $recordCompleteness = $this->rememberDashboard(
            'record-completeness',
            $scope + ['rfid_enabled' => $rfidEnabled],
            fn () => $this->recordCompleteness($selectedSchoolYearId, $classIds, $studentIds, $rfidEnabled),
        );
        $quickActions = $this->quickActions($health, $recordCompleteness);
        $theme = config('school_portal.theme');
        $chartColors = [
            $theme['primary'],
            $theme['secondary'],
            $theme['accent'],
            $theme['alert'],
            '#6E120D',
            '#A9771C',
            '#5B1B16',
            '#C69A35',
        ];

        $attendanceSummary = [
            ['label' => 'Enrolled Students', 'value' => number_format($enrolledStudentCount)],
            ['label' => 'Recorded Days', 'value' => number_format($dailyAttendance->count())],
            ['label' => 'Present Student-Days', 'value' => number_format($presentStudentDays)],
            ['label' => 'Avg Attendance Rate', 'value' => number_format($averageAttendanceRate, 1).'%'],
            ['label' => 'Absent Student-Days', 'value' => number_format($absentStudentDays)],
            ['label' => 'Weekdays Without Records', 'value' => number_format($unrecordedWeekdays)],
            [
                'label' => 'Tardy/Late Arrivals',
                'value' => $health['tardy_available'] ? number_format($totalLateArrivals) : 'Not configured',
            ],
        ];

        return [
            Grid::make([
                DashboardFilters::make(
                    $schoolYears,
                    $filterOptions['grades'],
                    $filterOptions['classes'],
                    [
                        'school_year_id' => $selectedSchoolYearId,
                        'grade_id' => $selectedGradeId,
                        'class_id' => $selectedClassId,
                        'date_from' => $dateRange['from'],
                        'date_to' => $dateRange['to'],
                    ],
                    $dateRange['boundary_message'],
                ),
            ]),

            Grid::make([
                DashboardHealth::make($selectedSchoolYear, $health['items']),
            ]),

            Grid::make([
                DashboardQuickActions::make($quickActions),
            ]),

            Grid::make([
                AttendanceSummary::make($attendanceSummary),
            ]),

            Grid::make([
                chartjs::make()
                    ->type('bar')
                    ->chartData([
                        'labels' => $gradeDistribution->pluck('grade')->values()->all(),
                        'datasets' => [
                            [
                                'label' => 'Students',
                                'data' => $gradeDistribution->pluck('total')->values()->all(),
                                'backgroundColor' => $chartColors,
                            ],
                        ],
                    ])
                    ->chartOptions([
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Number of Students per Grade',
                            ],
                            'legend' => ['display' => false],
                        ],
                        'indexAxis' => 'y',
                        'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
                    ])
                    ->columnSpan(6),

                chartjs::make()
                    ->type('line')
                    ->chartData([
                        'labels' => $attendanceTimeline->isNotEmpty()
                            ? $attendanceTimeline->pluck('label')->values()->all()
                            : ['No attendance data'],
                        'datasets' => [
                            [
                                'label' => 'Present students',
                                'data' => $attendanceTimeline->isNotEmpty()
                                    ? $attendanceTimeline->pluck('present')->values()->all()
                                    : [0],
                                'borderColor' => $theme['primary'],
                                'backgroundColor' => $this->hexToRgba($theme['primary'], 0.14),
                                'tension' => 0.35,
                            ],
                            [
                                'label' => 'Absent students',
                                'data' => $attendanceTimeline->isNotEmpty()
                                    ? $attendanceTimeline->pluck('absent')->values()->all()
                                    : [0],
                                'borderColor' => $theme['alert'],
                                'backgroundColor' => $this->hexToRgba($theme['alert'], 0.10),
                                'tension' => 0.35,
                            ],
                        ],
                    ])
                    ->chartOptions([
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Present vs Absent — gaps indicate no attendance records',
                            ],
                            'legend' => ['position' => 'bottom'],
                        ],
                        'scales' => [
                            'y' => ['beginAtZero' => true],
                        ],
                    ])
                    ->columnSpan(6),

                chartjs::make()
                    ->type('bar')
                    ->chartData([
                        'labels' => $monthlyAttendance->isNotEmpty()
                            ? $monthlyAttendance->pluck('label')->values()->all()
                            : ['No attendance data'],
                        'datasets' => [
                            [
                                'label' => 'Attendance rate',
                                'data' => $monthlyAttendance->isNotEmpty()
                                    ? $monthlyAttendance->pluck('rate')->values()->all()
                                    : [0],
                                'backgroundColor' => $theme['accent'],
                                'borderRadius' => 6,
                            ],
                        ],
                    ])
                    ->chartOptions([
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Monthly Attendance Rate',
                            ],
                            'legend' => ['display' => false],
                        ],
                        'scales' => [
                            'y' => ['beginAtZero' => true, 'max' => 100],
                        ],
                    ])
                    ->columnSpan(12),

                ...($health['tardy_available'] ? [chartjs::make()
                    ->type('bar')
                    ->chartData([
                        'labels' => $lateByClass->pluck('label')->values()->all(),
                        'datasets' => [
                            [
                                'label' => 'Tardy/late arrivals',
                                'data' => $lateByClass->pluck('total')->values()->all(),
                                'backgroundColor' => $theme['alert'],
                                'borderRadius' => 6,
                            ],
                        ],
                    ])
                    ->chartOptions([
                        'plugins' => [
                            'title' => [
                                'display' => true,
                                'text' => 'Tardy/Late Arrivals per Class',
                            ],
                            'legend' => ['display' => false],
                        ],
                        'scales' => [
                            'y' => [
                                'beginAtZero' => true,
                                'ticks' => ['precision' => 0],
                            ],
                        ],
                    ])
                    ->columnSpan(12)] : []),
            ], gap: 4),

            Grid::make([
                AttendanceAttention::make($studentsNeedingAttention, $dailyAttendance->count(), 80.0),
            ]),

            Grid::make([
                AcademicInsights::make($academic['summary'], $academic['students']),
            ]),

            Grid::make([
                chartjs::make()
                    ->type('bar')
                    ->chartData([
                        'labels' => $academic['subject_performance']->isNotEmpty()
                            ? $academic['subject_performance']->pluck('subject')->all()
                            : ['No grade data'],
                        'datasets' => [[
                            'label' => 'Average grade',
                            'data' => $academic['subject_performance']->isNotEmpty()
                                ? $academic['subject_performance']->pluck('average')->all()
                                : [0],
                            'backgroundColor' => $theme['secondary'],
                            'borderRadius' => 6,
                        ]],
                    ])
                    ->chartOptions([
                        'plugins' => [
                            'title' => ['display' => true, 'text' => 'Average Grade by Subject'],
                            'legend' => ['display' => false],
                        ],
                        'scales' => [
                            'y' => ['beginAtZero' => true, 'max' => 100],
                        ],
                    ])
                    ->columnSpan(12),
            ]),

            Grid::make([
                RecordCompleteness::make($recordCompleteness['summary'], $recordCompleteness['students']),
            ]),

            // Grid::make([
            //     SuggestedGraphs::make($this->suggestedGraphs()),
            // ]),
        ];
    }

    /**
     * @param  array<int, string>  $schoolYears
     */
    private function selectedSchoolYearId(array $schoolYears, int|string|null $activeSchoolYearId = null): ?int
    {
        $requested = request()->integer('school_year_id');

        if ($requested > 0 && array_key_exists($requested, $schoolYears)) {
            return $requested;
        }

        if ($activeSchoolYearId !== null && array_key_exists((int) $activeSchoolYearId, $schoolYears)) {
            return (int) $activeSchoolYearId;
        }

        $firstSchoolYearId = array_key_first($schoolYears);

        return $firstSchoolYearId === null ? null : (int) $firstSchoolYearId;
    }

    private function rememberDashboard(string $segment, array $context, Closure $callback): mixed
    {
        $seconds = max(0, (int) config('school_portal.dashboard.cache_seconds', 30));

        if ($seconds === 0) {
            return $callback();
        }

        $context['host'] = request()->getHost();
        $key = 'admin-dashboard:v5:'.$segment.':'.hash(
            'sha256',
            json_encode($context, JSON_THROW_ON_ERROR),
        );

        if (request()->boolean('refresh')) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addSeconds($seconds), $callback);
    }

    /**
     * @param  array{tardy_available: bool, items: array<int, array<string, string>>}  $health
     * @param  array{summary: array{students: int, missing_rfid: int, missing_photo: int, missing_dob: int, missing_guardian: int}, students: array<int, array<string, mixed>>}  $recordCompleteness
     * @return list<array{label: string, description: string, url: string, icon: string, tone: string}>
     */
    private function quickActions(array $health, array $recordCompleteness): array
    {
        $dateScopeNeedsAttention = collect($health['items'])->contains(
            fn (array $item) => $item['label'] === 'School-year date scope' && $item['tone'] === 'warning',
        );
        $classSetupNeedsAttention = collect($health['items'])->contains(
            fn (array $item) => in_array($item['label'], ['Class readiness', 'Tardiness readiness'], true)
                && $item['tone'] === 'warning',
        );

        $actions = [
            [
                'label' => 'Add student',
                'description' => 'Create a new student record and portal account.',
                'url' => app(StudentResource::class)->getFormPageUrl(),
                'icon' => 'user-plus',
                'tone' => 'neutral',
            ],
            [
                'label' => 'Manage classes',
                'description' => 'Assign students, complete schedules, and review empty classes.',
                'url' => app(ClassesModelResource::class)->getIndexPageUrl(),
                'icon' => 'academic-cap',
                'tone' => $classSetupNeedsAttention ? 'warning' : 'neutral',
            ],
            [
                'label' => 'Review attendance',
                'description' => 'Open the detailed student attendance dashboard and reports.',
                'url' => (string) toPage(StudentAttendanceDashboard::class),
                'icon' => 'clipboard-document-check',
                'tone' => 'neutral',
            ],
            [
                'label' => 'Configure school year',
                'description' => 'Set the academic date boundaries used by attendance filters.',
                'url' => app(SchoolYearResource::class)->getIndexPageUrl(),
                'icon' => 'calendar-days',
                'tone' => $dateScopeNeedsAttention ? 'warning' : 'neutral',
            ],
            [
                'label' => 'Manage announcements',
                'description' => 'Publish and maintain student and teacher announcements.',
                'url' => app(AnnouncementResource::class)->getIndexPageUrl(),
                'icon' => 'megaphone',
                'tone' => 'neutral',
            ],
        ];

        if ($recordCompleteness['summary']['rfid_enabled']) {
            $actions[] = [
                'label' => 'Check RFID card',
                'description' => 'Identify an assigned card or investigate missing RFID setup.',
                'url' => (string) toPage(RfidChecker::class),
                'icon' => 'magnifying-glass',
                'tone' => $recordCompleteness['summary']['missing_rfid'] > 0 ? 'warning' : 'neutral',
            ];
        }

        return $actions;
    }

    /**
     * @return array{grades: array<int, string>, classes: array<int, string>}
     */
    private function filterOptions(?int $schoolYearId): array
    {
        if ($schoolYearId === null) {
            return ['grades' => [], 'classes' => []];
        }

        $classes = ClassesModel::query()
            ->with('grade:id,grade')
            ->where('school_year_id', $schoolYearId)
            ->where('active', true)
            ->orderBy('grade_id')
            ->orderBy('section')
            ->get(['id', 'grade_id', 'section']);

        return [
            'grades' => $classes
                ->filter(fn (ClassesModel $class) => $class->grade !== null)
                ->mapWithKeys(fn (ClassesModel $class) => [(int) $class->grade_id => (string) $class->grade->grade])
                ->all(),
            'classes' => $classes
                ->mapWithKeys(fn (ClassesModel $class) => [
                    (int) $class->id => trim(($class->grade?->grade ?? 'Grade').' - '.$class->section),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<int, string>  $options
     */
    private function selectedOptionId(string $key, array $options): ?int
    {
        $requested = request()->integer($key);

        return $requested > 0 && array_key_exists($requested, $options)
            ? $requested
            : null;
    }

    /**
     * @param  array<int, string>  $classes
     */
    private function selectedClassId(array $classes, ?int $gradeId): ?int
    {
        $classId = $this->selectedOptionId('class_id', $classes);

        if ($classId === null || $gradeId === null) {
            return $classId;
        }

        return ClassesModel::query()
            ->whereKey($classId)
            ->where('grade_id', $gradeId)
            ->exists()
                ? $classId
                : null;
    }

    /**
     * @return array{from: ?string, to: ?string, boundary_message: ?string}
     */
    private function selectedDateRange(?SchoolYear $schoolYear): array
    {
        $schoolYearStart = $schoolYear?->start_date?->format('Y-m-d');
        $schoolYearEnd = $schoolYear?->end_date?->format('Y-m-d');
        $from = $this->validDate(request()->input('date_from')) ?? $schoolYearStart;
        $to = $this->validDate(request()->input('date_to')) ?? $schoolYearEnd;

        if ($schoolYearStart !== null && $from !== null && $from < $schoolYearStart) {
            $from = $schoolYearStart;
        }

        if ($schoolYearEnd !== null && $to !== null && $to > $schoolYearEnd) {
            $to = $schoolYearEnd;
        }

        if ($from !== null && $to !== null && $from > $to) {
            $to = $from;
        }

        return [
            'from' => $from,
            'to' => $to,
            'boundary_message' => $schoolYearStart === null || $schoolYearEnd === null
                ? 'School-year dates are not configured yet. Choose a date range or update the School Year record.'
                : null,
        ];
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

            return $date->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function classIdsForScope(?int $schoolYearId, ?int $gradeId, ?int $classId): Collection
    {
        if ($schoolYearId === null) {
            return collect();
        }

        return ClassesModel::query()
            ->where('school_year_id', $schoolYearId)
            ->where('active', true)
            ->when($gradeId !== null, fn ($classes) => $classes->where('grade_id', $gradeId))
            ->when($classId !== null, fn ($classes) => $classes->whereKey($classId))
            ->pluck('id');
    }

    private function studentIdsForSchoolYear(?int $schoolYearId, ?Collection $classIds = null): Collection
    {
        if ($schoolYearId === null) {
            return collect();
        }

        return ClassStudent::query()
            ->where('school_year_id', $schoolYearId)
            ->when($classIds !== null, fn ($enrollments) => $enrollments->whereIn('class_id', $classIds->all()))
            ->distinct()
            ->pluck('student_id');
    }

    private function activeClassesCount(?int $schoolYearId): int
    {
        if ($schoolYearId === null) {
            return 0;
        }

        return ClassesModel::query()
            ->where('school_year_id', $schoolYearId)
            ->where('active', true)
            ->count();
    }

    private function studentsPerGrade(?int $schoolYearId, Collection $classIds): Collection
    {
        if ($schoolYearId === null) {
            return collect([
                ['grade' => 'No school year data', 'total' => 0],
            ]);
        }

        $rows = ClassStudent::query()
            ->join('classes', 'class_students.class_id', '=', 'classes.id')
            ->join('grade', 'classes.grade_id', '=', 'grade.id')
            ->where('class_students.school_year_id', $schoolYearId)
            ->whereIn('class_students.class_id', $classIds->all())
            ->select('grade.grade', DB::raw('COUNT(DISTINCT class_students.student_id) as total'))
            ->groupBy('grade.id', 'grade.grade')
            ->orderBy('grade.grade')
            ->get()
            ->map(fn ($row) => [
                'grade' => (string) $row->grade,
                'total' => (int) $row->total,
            ]);

        return $rows->isNotEmpty()
            ? $rows
            : collect([['grade' => 'No enrollment data', 'total' => 0]]);
    }

    private function dailyAttendance(Collection $studentIds, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        $rows = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds->all())
            ->whereNotNull('currentdate')
            ->when($dateFrom !== null, fn ($attendance) => $attendance->whereDate('currentdate', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($attendance) => $attendance->whereDate('currentdate', '<=', $dateTo))
            ->selectRaw('currentdate as attendance_date')
            ->selectRaw('COUNT(DISTINCT student_id) as total')
            ->groupBy('currentdate')
            ->orderBy('currentdate')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows->map(function ($row): array {
            $date = (string) $row->attendance_date;

            return [
                'date' => $date,
                'label' => date('M d', strtotime($date)),
                'total' => (int) $row->total,
            ];
        })->values();
    }

    private function averageAttendancePerMonth(Collection $dailyAttendance, int $enrolledStudentCount): Collection
    {
        $rows = $dailyAttendance->filter(fn (array $day) => $day['date'] !== null);

        if ($rows->isEmpty() || $enrolledStudentCount === 0) {
            return collect();
        }

        return $rows
            ->groupBy(fn (array $day) => substr((string) $day['date'], 0, 7))
            ->map(fn (Collection $days, string $month) => [
                'month' => $month,
                'label' => date('M Y', strtotime($month.'-01')),
                'rate' => round(((float) $days->avg('total') / $enrolledStudentCount) * 100, 1),
            ])
            ->sortBy('month')
            ->values();
    }

    /**
     * @return Collection<int, array{date: string, label: string, present: ?int, absent: ?int, status: string}>
     */
    private function attendanceTimeline(
        Collection $dailyAttendance,
        int $enrolledStudentCount,
        ?string $dateFrom,
        ?string $dateTo,
    ): Collection {
        $start = $dateFrom ?? $dailyAttendance->min('date');
        $end = $dateTo ?? $dailyAttendance->max('date');

        if ($start === null || $end === null) {
            return collect();
        }

        $startDate = CarbonImmutable::parse((string) $start)->startOfDay();
        $endDate = CarbonImmutable::parse((string) $end)->startOfDay();
        $today = CarbonImmutable::today();

        if ($endDate->isAfter($today)) {
            $endDate = $today;
        }

        if ($startDate->isAfter($endDate)) {
            return collect();
        }

        if ($startDate->diffInDays($endDate) > 370) {
            $startDate = $endDate->subDays(370);
        }

        $recorded = $dailyAttendance->keyBy(fn (array $day) => substr((string) $day['date'], 0, 10));

        return collect(CarbonPeriod::create($startDate, $endDate))
            ->reject(fn ($date) => $date->isWeekend())
            ->map(function ($date) use ($recorded, $enrolledStudentCount): array {
                $key = $date->format('Y-m-d');
                $day = $recorded->get($key);
                $present = $day === null ? null : (int) $day['total'];

                return [
                    'date' => $key,
                    'label' => $date->format('M d'),
                    'present' => $present,
                    'absent' => $present === null ? null : max(0, $enrolledStudentCount - $present),
                    'status' => $present === null ? 'no_data' : 'recorded',
                ];
            })
            ->values();
    }

    /**
     * @return list<array{name: string, lrn: string, class: string, present: int, absent: int, rate: float, last_attendance: string, url: string}>
     */
    private function studentsNeedingAttention(
        ?int $schoolYearId,
        Collection $classIds,
        int $recordedDays,
        ?string $dateFrom,
        ?string $dateTo,
        float $threshold = 80.0,
    ): array {
        if ($schoolYearId === null || $classIds->isEmpty() || $recordedDays === 0) {
            return [];
        }

        $attendance = DB::table('attendance_record')
            ->whereNotNull('currentdate')
            ->when($dateFrom !== null, fn ($records) => $records->whereDate('currentdate', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($records) => $records->whereDate('currentdate', '<=', $dateTo))
            ->select('student_id')
            ->selectRaw('COUNT(DISTINCT currentdate) as present_days')
            ->selectRaw('MAX(currentdate) as last_attendance')
            ->groupBy('student_id');

        return ClassStudent::query()
            ->join('students', 'students.id', '=', 'class_students.student_id')
            ->join('classes', 'classes.id', '=', 'class_students.class_id')
            ->join('grade', 'grade.id', '=', 'classes.grade_id')
            ->leftJoinSub($attendance, 'attendance_summary', function ($join): void {
                $join->on('attendance_summary.student_id', '=', 'class_students.student_id');
            })
            ->where('class_students.school_year_id', $schoolYearId)
            ->whereIn('class_students.class_id', $classIds->all())
            ->select([
                'students.id as student_id',
                'students.lrn',
                'students.firstname',
                'students.lastname',
                'grade.grade',
                'classes.section',
                DB::raw('COALESCE(attendance_summary.present_days, 0) as present_days'),
                'attendance_summary.last_attendance',
            ])
            ->orderByRaw('COALESCE(attendance_summary.present_days, 0)')
            ->orderBy('students.lastname')
            ->get()
            ->map(function ($row) use ($recordedDays): array {
                $present = (int) $row->present_days;

                return [
                    'name' => trim($row->lastname.', '.$row->firstname),
                    'lrn' => (string) $row->lrn,
                    'class' => trim($row->grade.' - '.$row->section),
                    'present' => $present,
                    'absent' => max(0, $recordedDays - $present),
                    'rate' => round(($present / $recordedDays) * 100, 1),
                    'last_attendance' => $row->last_attendance
                        ? CarbonImmutable::parse((string) $row->last_attendance)->format('M d, Y')
                        : 'Never',
                    'url' => app(StudentResource::class)->getDetailPageUrl((int) $row->student_id),
                ];
            })
            ->filter(fn (array $student) => $student['rate'] < $threshold)
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   summary: array{expected_grade_records: int, grade_records: int, grade_coverage: float, at_risk_students: int, quiz_enabled: bool, quiz_participants: int, enrolled_students: int, quiz_participation: float, quiz_answers: int, quiz_accuracy: ?float},
     *   subject_performance: Collection<int, array{subject: string, average: float}>,
     *   students: list<array{name: string, class: string, average: float, status: string, tone: string, url: string}>
     * }
     */
    private function academicInsights(?int $schoolYearId, Collection $classIds, Collection $studentIds): array
    {
        $quizEnabled = filter_var(
            config('school_portal.features.quiz_module', false),
            FILTER_VALIDATE_BOOLEAN,
        );
        $empty = [
            'summary' => [
                'expected_grade_records' => 0,
                'grade_records' => 0,
                'grade_coverage' => 0.0,
                'at_risk_students' => 0,
                'quiz_enabled' => $quizEnabled,
                'quiz_participants' => 0,
                'enrolled_students' => $studentIds->count(),
                'quiz_participation' => 0.0,
                'quiz_answers' => 0,
                'quiz_accuracy' => null,
            ],
            'subject_performance' => collect(),
            'students' => [],
        ];

        if ($schoolYearId === null || $classIds->isEmpty() || $studentIds->isEmpty()) {
            return $empty;
        }

        $expectedGradeRecords = DB::table('class_students')
            ->join('class_subjects', 'class_subjects.class_id', '=', 'class_students.class_id')
            ->where('class_students.school_year_id', $schoolYearId)
            ->whereIn('class_students.class_id', $classIds->all())
            ->select(['class_students.student_id', 'class_subjects.subject_id'])
            ->distinct()
            ->get()
            ->count();
        $gradeRecords = DB::table('class_student_grades')
            ->whereIn('class_id', $classIds->all())
            ->whereIn('student_id', $studentIds->all())
            ->select(['student_id', 'subject_id'])
            ->distinct()
            ->get()
            ->count();
        $termAverage = <<<'SQL'
            CASE COALESCE(NULLIF(classes.grading_period_count, 0), grade.term_count, 4)
                WHEN 1 THEN class_student_grades.q1
                WHEN 2 THEN (class_student_grades.q1 + class_student_grades.q2) / 2.0
                WHEN 3 THEN (class_student_grades.q1 + class_student_grades.q2 + class_student_grades.q3) / 3.0
                ELSE (class_student_grades.q1 + class_student_grades.q2 + class_student_grades.q3 + class_student_grades.q4) / 4.0
            END
            SQL;

        $gradeQuery = DB::table('class_student_grades')
            ->join('classes', 'classes.id', '=', 'class_student_grades.class_id')
            ->join('grade', 'grade.id', '=', 'classes.grade_id')
            ->whereIn('class_student_grades.class_id', $classIds->all())
            ->whereIn('class_student_grades.student_id', $studentIds->all());
        $subjectPerformance = (clone $gradeQuery)
            ->join('subjects', 'subjects.id', '=', 'class_student_grades.subject_id')
            ->select('subjects.subject')
            ->selectRaw("ROUND(AVG({$termAverage}), 2) as average")
            ->groupBy('subjects.id', 'subjects.subject', 'subjects.record_order')
            ->orderByRaw('subjects.record_order IS NULL')
            ->orderBy('subjects.record_order')
            ->orderBy('subjects.subject')
            ->get()
            ->map(fn ($row): array => [
                'subject' => (string) $row->subject,
                'average' => (float) $row->average,
            ]);
        $studentPerformance = (clone $gradeQuery)
            ->join('students', 'students.id', '=', 'class_student_grades.student_id')
            ->select([
                'students.id as student_id',
                'students.firstname',
                'students.lastname',
                'grade.grade',
                'classes.section',
            ])
            ->selectRaw("ROUND(AVG({$termAverage}), 2) as average")
            ->groupBy(
                'students.id',
                'students.firstname',
                'students.lastname',
                'grade.grade',
                'classes.section',
            )
            ->orderBy('average')
            ->get();

        $quizAnswers = $quizEnabled
            ? DB::table('student_quiz_answers')
                ->join('quiz_group_days', 'quiz_group_days.id', '=', 'student_quiz_answers.quiz_group_days_id')
                ->join('quiz_group', 'quiz_group.id', '=', 'quiz_group_days.quiz_group_id')
                ->leftJoin('quiz_quiz_answers', function ($join): void {
                    $join->on('quiz_quiz_answers.quiz_id', '=', 'student_quiz_answers.quiz_id')
                        ->on('quiz_quiz_answers.answer_id', '=', 'student_quiz_answers.answer_id');
                })
                ->where('quiz_group.school_year_id', $schoolYearId)
                ->whereIn('student_quiz_answers.student_id', $studentIds->all())
                ->select([
                    'student_quiz_answers.student_id',
                    'quiz_quiz_answers.is_correct_answer',
                ])
                ->get()
            : collect();
        $quizParticipantCount = $quizAnswers->pluck('student_id')->unique()->count();
        $quizAnswerCount = $quizAnswers->count();
        $correctQuizAnswers = $quizAnswers->where('is_correct_answer', 1)->count();

        $students = $studentPerformance
            ->take(8)
            ->map(function ($row): array {
                $average = (float) $row->average;
                [$status, $tone] = $average < 75
                    ? ['At risk', 'danger']
                    : ($average < 85 ? ['Watch', 'warning'] : ['On track', 'success']);

                return [
                    'name' => trim($row->lastname.', '.$row->firstname),
                    'class' => trim($row->grade.' - '.$row->section),
                    'average' => $average,
                    'status' => $status,
                    'tone' => $tone,
                    'url' => app(StudentResource::class)->getDetailPageUrl((int) $row->student_id),
                ];
            })
            ->all();

        return [
            'summary' => [
                'expected_grade_records' => $expectedGradeRecords,
                'grade_records' => $gradeRecords,
                'grade_coverage' => $expectedGradeRecords > 0
                    ? round(($gradeRecords / $expectedGradeRecords) * 100, 1)
                    : 0.0,
                'at_risk_students' => $studentPerformance->where('average', '<', 75)->count(),
                'quiz_enabled' => $quizEnabled,
                'quiz_participants' => $quizParticipantCount,
                'enrolled_students' => $studentIds->count(),
                'quiz_participation' => $studentIds->isNotEmpty()
                    ? round(($quizParticipantCount / $studentIds->count()) * 100, 1)
                    : 0.0,
                'quiz_answers' => $quizAnswerCount,
                'quiz_accuracy' => $quizAnswerCount > 0
                    ? round(($correctQuizAnswers / $quizAnswerCount) * 100, 1)
                    : null,
            ],
            'subject_performance' => $subjectPerformance,
            'students' => $students,
        ];
    }

    /**
     * @return array{summary: array{students: int, rfid_enabled: bool, missing_rfid: int, missing_photo: int, missing_dob: int, missing_guardian: int}, students: list<array{name: string, class: string, issues: string, issue_count: int, url: string}>}
     */
    private function recordCompleteness(
        ?int $schoolYearId,
        Collection $classIds,
        Collection $studentIds,
        bool $rfidEnabled = true,
    ): array {
        $summary = [
            'students' => $studentIds->count(),
            'rfid_enabled' => $rfidEnabled,
            'missing_rfid' => 0,
            'missing_photo' => 0,
            'missing_dob' => 0,
            'missing_guardian' => 0,
        ];

        if ($schoolYearId === null || $classIds->isEmpty() || $studentIds->isEmpty()) {
            return ['summary' => $summary, 'students' => []];
        }

        $students = Student::query()
            ->join('class_students', function ($join) use ($schoolYearId): void {
                $join->on('class_students.student_id', '=', 'students.id')
                    ->where('class_students.school_year_id', $schoolYearId);
            })
            ->join('classes', 'classes.id', '=', 'class_students.class_id')
            ->join('grade', 'grade.id', '=', 'classes.grade_id')
            ->whereIn('students.id', $studentIds->all())
            ->whereIn('class_students.class_id', $classIds->all())
            ->get([
                'students.id',
                'students.firstname',
                'students.lastname',
                'students.rfid_card_uid',
                'students.profile_photo',
                'students.dob',
                'students.parent_guardian',
                'grade.grade',
                'classes.section',
            ]);

        $summary['missing_rfid'] = $rfidEnabled
            ? $students->filter(fn ($student) => blank($student->rfid_card_uid))->count()
            : 0;
        $summary['missing_photo'] = $students->filter(fn ($student) => blank($student->profile_photo))->count();
        $summary['missing_dob'] = $students->filter(fn ($student) => blank($student->dob))->count();
        $summary['missing_guardian'] = $students->filter(fn ($student) => blank($student->parent_guardian))->count();

        $attention = $students
            ->map(function ($student) use ($rfidEnabled): array {
                $issues = collect([
                    $rfidEnabled && blank($student->rfid_card_uid) ? 'RFID' : null,
                    blank($student->profile_photo) ? 'profile photo' : null,
                    blank($student->dob) ? 'birthdate' : null,
                    blank($student->parent_guardian) ? 'guardian' : null,
                ])->filter()->values();

                return [
                    'name' => trim($student->lastname.', '.$student->firstname),
                    'class' => trim($student->grade.' - '.$student->section),
                    'issues' => $issues->implode(', '),
                    'issue_count' => $issues->count(),
                    'url' => app(StudentResource::class)->getDetailPageUrl((int) $student->id),
                ];
            })
            ->filter(fn (array $student) => $student['issue_count'] > 0)
            ->sortByDesc('issue_count')
            ->take(10)
            ->values()
            ->all();

        return ['summary' => $summary, 'students' => $attention];
    }

    private function averageAttendanceRate(Collection $dailyAttendance, int $enrolledStudentCount): float
    {
        if ($enrolledStudentCount === 0 || $dailyAttendance->isEmpty()) {
            return 0.0;
        }

        return round(
            ((float) $dailyAttendance->avg('total') / $enrolledStudentCount) * 100,
            1,
        );
    }

    /**
     * @return array{tardy_available: bool, items: list<array{tone: string, label: string, value: string, description: string, action_label: string, action_url: string}>}
     */
    private function dashboardHealth(
        ?int $schoolYearId,
        Collection $studentIds,
        Collection $dailyAttendance,
        ?SchoolYear $schoolYear = null,
    ): array {
        $activeStudentCount = Student::query()->active()->count();
        $enrolledStudentCount = $studentIds->count();
        $unassignedStudentCount = Student::query()
            ->active()
            ->when(
                $schoolYearId !== null,
                fn ($students) => $students->whereDoesntHave(
                    'classStudents',
                    fn ($enrollments) => $enrollments->where('school_year_id', $schoolYearId),
                ),
            )
            ->count();

        $classes = $schoolYearId === null
            ? collect()
            : ClassesModel::query()
                ->where('school_year_id', $schoolYearId)
                ->where('active', true)
                ->withCount([
                    'classStudents as enrolled_students_count' => fn ($enrollments) => $enrollments
                        ->where('school_year_id', $schoolYearId),
                ])
                ->get(['id', 'start_time', 'end_time']);

        $populatedClasses = $classes->filter(
            fn (ClassesModel $class) => (int) $class->enrolled_students_count > 0,
        );
        $emptyClassCount = $classes->count() - $populatedClasses->count();
        $missingScheduleCount = $populatedClasses->filter(
            fn (ClassesModel $class) => blank($class->start_time) || blank($class->end_time),
        )->count();
        $tardyAvailable = $populatedClasses->isNotEmpty() && $missingScheduleCount === 0;

        $latestAttendanceDate = $dailyAttendance->max('date');
        $attendanceAgeDays = $latestAttendanceDate
            ? (int) max(0, CarbonImmutable::parse((string) $latestAttendanceDate)->startOfDay()->diffInDays(today(), false))
            : null;
        $latestAttendanceLabel = $latestAttendanceDate
            ? CarbonImmutable::parse((string) $latestAttendanceDate)->format('M d, Y')
            : 'No attendance received';

        $enrollmentCoverage = $activeStudentCount > 0
            ? round(($enrolledStudentCount / $activeStudentCount) * 100, 1)
            : 0.0;

        return [
            'tardy_available' => $tardyAvailable,
            'items' => [
                [
                    'tone' => $unassignedStudentCount > 0 ? 'danger' : 'success',
                    'label' => 'Enrollment health',
                    'value' => number_format($enrolledStudentCount).' of '.number_format($activeStudentCount).' enrolled',
                    'description' => $unassignedStudentCount > 0
                        ? number_format($unassignedStudentCount).' active student records are not assigned to this school year. Coverage is '.number_format($enrollmentCoverage, 1).'%.'
                        : 'Every active student record is assigned to this school year.',
                    'action_label' => 'Review students',
                    'action_url' => app(StudentResource::class)->getIndexPageUrl(),
                ],
                [
                    'tone' => $emptyClassCount > 0 ? 'warning' : 'success',
                    'label' => 'Class readiness',
                    'value' => number_format($populatedClasses->count()).' of '.number_format($classes->count()).' populated',
                    'description' => $emptyClassCount > 0
                        ? number_format($emptyClassCount).' active classes currently have no enrolled students.'
                        : 'Every active class currently has enrolled students.',
                    'action_label' => 'Review classes',
                    'action_url' => app(ClassesModelResource::class)->getIndexPageUrl(),
                ],
                [
                    'tone' => $missingScheduleCount > 0 ? 'warning' : ($populatedClasses->isEmpty() ? 'neutral' : 'success'),
                    'label' => 'Tardiness readiness',
                    'value' => $tardyAvailable ? 'Ready' : 'Needs configuration',
                    'description' => $missingScheduleCount > 0
                        ? number_format($missingScheduleCount).' populated classes need both start and end times before late arrivals can be measured reliably.'
                        : ($populatedClasses->isEmpty()
                            ? 'Add students to active classes before measuring late arrivals.'
                            : 'All populated classes have the schedule information needed for tardiness reporting.'),
                    'action_label' => 'Configure schedules',
                    'action_url' => app(ClassesModelResource::class)->getIndexPageUrl(),
                ],
                [
                    'tone' => $attendanceAgeDays === null || $attendanceAgeDays > 3 ? 'warning' : 'success',
                    'label' => 'Attendance freshness',
                    'value' => $latestAttendanceLabel,
                    'description' => $attendanceAgeDays === null
                        ? 'No attendance has been recorded for the enrolled students in this school year.'
                        : ($attendanceAgeDays === 0
                            ? 'Attendance data was received today across '.number_format($dailyAttendance->count()).' recorded days.'
                            : 'The latest attendance is '.number_format($attendanceAgeDays).' day'.($attendanceAgeDays === 1 ? '' : 's').' old. '.number_format($dailyAttendance->count()).' attendance days are recorded.'),
                    'action_label' => 'Review attendance',
                    'action_url' => app(AttendanceRecordResource::class)->getIndexPageUrl(),
                ],
                [
                    'tone' => $schoolYear?->start_date && $schoolYear?->end_date ? 'success' : 'warning',
                    'label' => 'School-year date scope',
                    'value' => $schoolYear?->start_date && $schoolYear?->end_date
                        ? $schoolYear->start_date->format('M d, Y').' – '.$schoolYear->end_date->format('M d, Y')
                        : 'Needs configuration',
                    'description' => $schoolYear?->start_date && $schoolYear?->end_date
                        ? 'Attendance is automatically constrained to these school-year boundaries.'
                        : 'Set start and end dates on the School Year record to prevent attendance from other periods appearing here.',
                    'action_label' => 'Configure school year',
                    'action_url' => app(\App\MoonShine\Resources\SchoolYear\SchoolYearResource::class)->getIndexPageUrl(),
                ],
            ],
        ];
    }

    /**
     * @return array{total: float, transactions: int, students: int, payment_types: string}
     */
    private function paymentSummary(Collection $studentIds): array
    {
        if ($studentIds->isEmpty()) {
            return [
                'total' => 0.0,
                'transactions' => 0,
                'students' => 0,
                'payment_types' => 'No payment records',
            ];
        }

        $summary = StudentPaymentHistory::query()
            ->whereIn('student_id', $studentIds->all())
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('COUNT(DISTINCT student_id) as students')
            ->first();

        $paymentTypes = StudentPaymentHistory::query()
            ->leftJoin('payment_types', 'payment_types.id', '=', 'student_payment_histories.payment_type_id')
            ->whereIn('student_payment_histories.student_id', $studentIds->all())
            ->selectRaw("COALESCE(payment_types.name, 'Unspecified') as payment_type")
            ->selectRaw('COUNT(DISTINCT student_payment_histories.student_id) as student_count')
            ->groupBy('student_payment_histories.payment_type_id', 'payment_types.name')
            ->orderBy('payment_types.name')
            ->get()
            ->map(fn ($row) => $row->payment_type.' — '.number_format((int) $row->student_count))
            ->implode(', ');

        return [
            'total' => (float) $summary->total,
            'transactions' => (int) $summary->transactions,
            'students' => (int) $summary->students,
            'payment_types' => $paymentTypes !== '' ? $paymentTypes : 'No payment records',
        ];
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6) {
            return "rgba(143, 22, 15, {$alpha})";
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        return "rgba({$red}, {$green}, {$blue}, {$alpha})";
    }

    /**
     * @return list<array{title: string, description: string}>
     */
    private function suggestedGraphs(): array
    {
        return [
            [
                'title' => 'Average grade by subject',
                'description' => 'Use class_student_grades with subjects to compare academic performance across the selected school year.',
            ],
            [
                'title' => 'Quiz score distribution by grade',
                'description' => 'Use quiz_group, quizzes, and student_quiz_answers to see which grade levels need review support.',
            ],
            [
                'title' => 'Enrollment by section',
                'description' => 'Use classes and class_students to compare section sizes and catch overloaded classes.',
            ],
            [
                'title' => 'Students with low attendance',
                'description' => 'Use attendance_record against enrolled students to flag students below an attendance threshold.',
            ],
            [
                'title' => '4Ps membership by grade',
                'description' => 'Use students.is_4ps_member with class_students to understand support-program distribution.',
            ],
            [
                'title' => 'Quiz participation trend',
                'description' => 'Use quiz_group_days and student_quiz_answers to track daily or weekly quiz completion.',
            ],
        ];
    }
}
