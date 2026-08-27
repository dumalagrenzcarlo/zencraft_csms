<style>
    .adviser-information-page {
        --adviser-accent: var(--color-primary);
        --adviser-border: color-mix(in srgb, var(--adviser-accent) 25%, transparent);
        --adviser-muted: color-mix(in srgb, currentColor 62%, transparent);
        display: grid;
        gap: 1.25rem;
    }

    .adviser-information-page * {
        box-sizing: border-box;
    }

    .adviser-page-heading,
    .adviser-profile-summary,
    .adviser-information-grid,
    .adviser-detail-card__heading,
    .adviser-profile-summary__identity,
    .adviser-profile-summary__meta,
    .adviser-photo-action__overlay,
    .adviser-page-actions {
        display: flex;
    }

    .adviser-page-heading {
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
    }

    .adviser-page-heading__copy p {
        margin: 0;
        color: var(--adviser-muted);
        font-size: .925rem;
    }

    .adviser-page-actions {
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .75rem;
    }

    .adviser-page-button {
        min-height: 2.75rem;
        padding: .625rem 1.1rem;
        border: 1px solid var(--adviser-accent);
        border-radius: .75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        color: var(--adviser-accent);
        font-weight: 650;
        text-decoration: none;
        transition: transform 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .adviser-page-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 .4rem 1rem color-mix(in srgb, var(--adviser-accent) 14%, transparent);
    }

    .adviser-page-button:focus-visible,
    .adviser-photo-action:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--adviser-accent) 28%, transparent);
        outline-offset: 3px;
    }

    .adviser-page-button--primary {
        background: var(--adviser-accent);
        color: white;
    }

    .adviser-page-button svg,
    .adviser-detail-card__heading svg,
    .adviser-profile-summary__meta svg {
        width: 1.25rem;
        height: 1.25rem;
        flex: none;
    }

    .adviser-profile-summary,
    .adviser-detail-card {
        border: 1px solid var(--adviser-border);
        border-radius: 1rem;
        background: var(--color-base);
        box-shadow: 0 .5rem 1.5rem rgba(59, 35, 30, .06);
    }

    .adviser-profile-summary {
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        padding: 2rem;
    }

    .adviser-profile-summary__identity {
        align-items: center;
        min-width: 0;
        gap: 1.5rem;
    }

    .adviser-photo-action {
        position: relative;
        width: 8rem;
        height: 8rem;
        flex: 0 0 8rem;
        overflow: hidden;
        border: 3px solid color-mix(in srgb, var(--adviser-accent) 12%, white);
        border-radius: 50%;
        background: color-mix(in srgb, var(--adviser-accent) 8%, white);
        box-shadow: 0 .5rem 1.25rem rgba(59, 35, 30, .12);
    }

    .adviser-photo-action img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .adviser-photo-action__overlay {
        position: absolute;
        inset: 0;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: .35rem;
        background: rgba(35, 22, 19, .7);
        color: white;
        opacity: 0;
        transition: opacity 160ms ease;
    }

    .adviser-photo-action__overlay svg {
        width: 1.75rem;
        height: 1.75rem;
    }

    .adviser-photo-action__overlay span {
        font-size: .75rem;
        font-weight: 700;
    }

    .adviser-photo-action:hover .adviser-photo-action__overlay,
    .adviser-photo-action:focus-visible .adviser-photo-action__overlay {
        opacity: 1;
    }

    .adviser-profile-summary__copy {
        min-width: 0;
    }

    .adviser-profile-summary__copy h2 {
        margin: 0 0 .75rem;
        overflow-wrap: anywhere;
        font-size: clamp(1.5rem, 2.2vw, 2rem);
        line-height: 1.15;
    }

    .adviser-profile-summary__meta {
        flex-wrap: wrap;
        align-items: center;
        gap: .8rem 1.25rem;
        color: var(--adviser-muted);
    }

    .adviser-profile-summary__meta > span {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }

    .adviser-status {
        padding: .4rem .75rem;
        border: 1px solid #22c55e;
        border-radius: 999px;
        color: #15803d;
        background: color-mix(in srgb, #22c55e 8%, transparent);
        font-size: .825rem;
        font-weight: 700;
    }

    .adviser-status::before {
        width: .5rem;
        height: .5rem;
        border-radius: 50%;
        background: #22c55e;
        content: '';
    }

    .adviser-information-grid {
        align-items: stretch;
        gap: 1.25rem;
    }

    .adviser-detail-card {
        min-width: 0;
        flex: 1 1 0;
        padding: 1.5rem;
    }

    .adviser-detail-card__heading {
        align-items: center;
        gap: .65rem;
        margin: 0 0 .75rem;
        padding-bottom: .9rem;
        border-bottom: 1px solid var(--adviser-border);
        color: var(--adviser-accent);
    }

    .adviser-detail-card__heading h3 {
        margin: 0;
        color: inherit;
        font-size: 1.05rem;
    }

    .adviser-detail-list {
        margin: 0;
    }

    .adviser-detail-row {
        display: grid;
        grid-template-columns: minmax(8rem, .8fr) minmax(0, 1.2fr);
        gap: 1rem;
        padding: .9rem .25rem;
        border-bottom: 1px solid color-mix(in srgb, currentColor 10%, transparent);
    }

    .adviser-detail-row:last-child {
        border-bottom: 0;
    }

    .adviser-detail-row dt {
        color: var(--adviser-muted);
        font-size: .875rem;
        font-weight: 650;
    }

    .adviser-detail-row dd {
        margin: 0;
        overflow-wrap: anywhere;
        font-weight: 600;
    }

    .adviser-detail-row dd[data-empty='true'] {
        color: var(--adviser-muted);
        font-weight: 500;
        font-style: italic;
    }

    .adviser-detail-danger-actions {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--adviser-border);
    }

    .adviser-detail-danger-actions .action-group {
        justify-content: flex-start;
    }

    .adviser-detail-delete-button {
        width: auto !important;
        min-height: 2.75rem;
        padding-inline: 1rem !important;
        border-radius: .75rem !important;
    }

    @media (max-width: 900px) {
        .adviser-information-grid,
        .adviser-profile-summary,
        .adviser-page-heading {
            align-items: stretch;
            flex-direction: column;
        }

        .adviser-page-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 560px) {
        .adviser-profile-summary {
            padding: 1.25rem;
        }

        .adviser-profile-summary__identity {
            align-items: flex-start;
            flex-direction: column;
        }

        .adviser-photo-action {
            width: 6.5rem;
            height: 6.5rem;
            flex-basis: 6.5rem;
        }

        .adviser-detail-row {
            grid-template-columns: 1fr;
            gap: .35rem;
        }

        .adviser-page-button {
            flex: 1 1 auto;
        }
    }

    @media (hover: none) {
        .adviser-photo-action__overlay {
            inset: auto 0 0;
            padding: .4rem;
            flex-direction: row;
            opacity: 1;
        }

        .adviser-photo-action__overlay svg {
            width: 1rem;
            height: 1rem;
        }
    }
</style>

<section class="adviser-information-page" aria-labelledby="adviser-profile-name">
    <header class="adviser-page-heading">
        <div class="adviser-page-heading__copy">
            <p>{{ __('View profile, employment, and schedule details') }}</p>
        </div>

        <nav class="adviser-page-actions" aria-label="{{ __('Adviser actions') }}">
            <a class="adviser-page-button" href="{{ $backUrl }}">
                <x-moonshine::icon icon="arrow-left" />
                {{ __('Back') }}
            </a>
            <a class="adviser-page-button adviser-page-button--primary" href="{{ $editUrl }}">
                <x-moonshine::icon icon="pencil-square" />
                {{ __('Edit Adviser') }}
            </a>
        </nav>
    </header>

    <article class="adviser-profile-summary">
        <div class="adviser-profile-summary__identity">
            <a
                class="adviser-photo-action"
                href="{{ $editUrl }}#profile_photo"
                aria-label="{{ __('Change profile photo for :name', ['name' => $teacher->name]) }}"
                title="{{ __('Change profile photo') }}"
            >
                <img src="{{ $teacher->profile_photo_url }}" alt="{{ __('Profile photo of :name', ['name' => $teacher->name]) }}">
                <span class="adviser-photo-action__overlay" aria-hidden="true">
                    <x-moonshine::icon icon="arrow-up-tray" />
                    <span>{{ __('Change photo') }}</span>
                </span>
            </a>

            <div class="adviser-profile-summary__copy">
                <h2 id="adviser-profile-name">{{ $teacher->name }}</h2>
                <div class="adviser-profile-summary__meta">
                    <span>
                        <x-moonshine::icon icon="briefcase" />
                        {{ filled($teacher->rank) ? $teacher->rank : __('Rank not set') }}
                    </span>
                    <span>
                        <x-moonshine::icon icon="book-open" />
                        {{ filled($teacher->major) ? $teacher->major : __('Major not set') }}
                    </span>
                    @if($teacher->isCollegeInstructor())
                        <span class="adviser-status">{{ __('College Instructor') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </article>

    <div class="adviser-information-grid">
        <article class="adviser-detail-card">
            <header class="adviser-detail-card__heading">
                <x-moonshine::icon icon="user" />
                <h3>{{ __('Personal & Employment Details') }}</h3>
            </header>
            <dl class="adviser-detail-list">
                <div class="adviser-detail-row">
                    <dt>{{ __('Full Name') }}</dt>
                    <dd>{{ $teacher->name }}</dd>
                </div>
                @if($showRfid)
                    <div class="adviser-detail-row" data-rfid-detail-field>
                        <dt>{{ __('RFID Card UID') }}</dt>
                        <dd data-empty="{{ filled($teacher->rfid_card_uid) ? 'false' : 'true' }}">
                            {{ filled($teacher->rfid_card_uid) ? $teacher->rfid_card_uid : __('Not assigned') }}
                        </dd>
                    </div>
                @endif
                <div class="adviser-detail-row">
                    <dt>{{ __('Rank') }}</dt>
                    <dd data-empty="{{ filled($teacher->rank) ? 'false' : 'true' }}">
                        {{ filled($teacher->rank) ? $teacher->rank : __('Not set') }}
                    </dd>
                </div>
                <div class="adviser-detail-row">
                    <dt>{{ __('Major') }}</dt>
                    <dd data-empty="{{ filled($teacher->major) ? 'false' : 'true' }}">
                        {{ filled($teacher->major) ? $teacher->major : __('Not set') }}
                    </dd>
                </div>
            </dl>
        </article>

        <article class="adviser-detail-card">
            <header class="adviser-detail-card__heading">
                <x-moonshine::icon icon="calendar-days" />
                <h3>{{ __('Work Schedule') }}</h3>
            </header>
            <dl class="adviser-detail-list">
                <div class="adviser-detail-row">
                    <dt>{{ __('Shift Start') }}</dt>
                    <dd data-empty="{{ filled($teacher->shift_start_time) ? 'false' : 'true' }}">{{ $shiftStart }}</dd>
                </div>
                <div class="adviser-detail-row">
                    <dt>{{ __('Shift End') }}</dt>
                    <dd data-empty="{{ filled($teacher->shift_end_time) ? 'false' : 'true' }}">{{ $shiftEnd }}</dd>
                </div>
            </dl>
        </article>
    </div>
</section>
