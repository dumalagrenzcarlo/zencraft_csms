@php
    $hasActiveSchoolYear = filled($activeSchoolYear);
    $hasUnscheduledClasses = $unscheduledCount > 0;
    $noticeState = ! $hasActiveSchoolYear ? 'inactive' : ($hasUnscheduledClasses ? 'warning' : 'complete');
@endphp

<section class="schedule-coverage schedule-coverage--{{ $noticeState }}" role="status" aria-live="polite">
    <span class="schedule-coverage__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M5.25 4.5h13.5a1.5 1.5 0 0 1 1.5 1.5v12.75a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 13h3M8 16.5h5" />
        </svg>
    </span>

    <div class="schedule-coverage__content">
        <span class="schedule-coverage__eyebrow">Schedule coverage{{ $hasActiveSchoolYear ? ' · '.$activeSchoolYear : '' }}</span>
        <strong class="schedule-coverage__title">
            @if (! $hasActiveSchoolYear)
                No active school year
            @elseif ($hasUnscheduledClasses)
                {{ number_format($unscheduledCount) }} {{ \Illuminate\Support\Str::plural('schedule assignment', $unscheduledCount) }} needed
            @else
                Schedules are up to date
            @endif
        </strong>
        <span class="schedule-coverage__description">
            @if (! $hasActiveSchoolYear)
                Set an active school year before assigning class schedules.
            @elseif ($hasUnscheduledClasses)
                Includes Classes with no schedule entry and active schedule entries that are still blank.
            @else
                Every Class in an active Course has a schedule entry for this school year.
            @endif
        </span>
    </div>

    <a class="schedule-coverage__action" href="{{ $classesUrl }}">Review Classes</a>
</section>

<style>
    .schedule-coverage {
        --schedule-accent: #64748b;
        --schedule-accent-soft: #f1f5f9;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 14px;
        margin-bottom: 18px;
        padding: 14px 16px;
        border: 1px solid rgba(148, 163, 184, .32);
        border-left: 4px solid var(--schedule-accent);
        border-radius: 12px;
        background: rgba(255, 255, 255, .78);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        color: #334155;
    }

    .schedule-coverage--warning {
        --schedule-accent: #d97706;
        --schedule-accent-soft: #fff7ed;
    }

    .schedule-coverage--complete {
        --schedule-accent: #059669;
        --schedule-accent-soft: #ecfdf5;
    }

    .schedule-coverage__icon {
        display: inline-flex;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: var(--schedule-accent-soft);
        color: var(--schedule-accent);
    }

    .schedule-coverage__icon svg {
        width: 22px;
        height: 22px;
    }

    .schedule-coverage__content {
        min-width: 0;
    }

    .schedule-coverage__eyebrow,
    .schedule-coverage__title,
    .schedule-coverage__description {
        display: block;
    }

    .schedule-coverage__eyebrow {
        margin-bottom: 2px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .06em;
        line-height: 1.35;
        text-transform: uppercase;
    }

    .schedule-coverage__title {
        color: #0f172a;
        font-size: 15px;
        line-height: 1.35;
    }

    .schedule-coverage__description {
        margin-top: 2px;
        color: #64748b;
        font-size: 13px;
        line-height: 1.45;
    }

    .schedule-coverage__action {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        padding: 7px 12px;
        border: 1px solid rgba(148, 163, 184, .45);
        border-radius: 9px;
        background: #fff;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: border-color .15s ease, color .15s ease, background .15s ease;
    }

    .schedule-coverage__action:hover {
        border-color: var(--schedule-accent);
        background: var(--schedule-accent-soft);
        color: var(--schedule-accent);
    }

    @media (max-width: 720px) {
        .schedule-coverage {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .schedule-coverage__action {
            grid-column: 1 / -1;
            width: 100%;
        }
    }
</style>
