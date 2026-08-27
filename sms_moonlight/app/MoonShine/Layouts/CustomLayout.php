<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\Models\Setting;
use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Pages\RfidChecker;
use App\MoonShine\Pages\StaffAttendanceDashboard;
use App\MoonShine\Pages\StudentAttendanceDashboard;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\Announcement\AnnouncementResource;
use App\MoonShine\Resources\ArchivedStudent\ArchivedStudentResource;
use App\MoonShine\Resources\ClassesModel\ClassesModelResource;
use App\MoonShine\Resources\CollegeCourseOffering\CollegeCourseOfferingResource;
use App\MoonShine\Resources\CollegeEnrollment\CollegeEnrollmentResource;
use App\MoonShine\Resources\CollegeEnrollmentCourse\CollegeEnrollmentCourseResource;
use App\MoonShine\Resources\CollegeProgram\CollegeProgramResource;
use App\MoonShine\Resources\CollegeProgramCourse\CollegeProgramCourseResource;
use App\MoonShine\Resources\Grade\GradeResource;
use App\MoonShine\Resources\Instructor\InstructorResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use App\MoonShine\Resources\Quiz\QuizResource;
use App\MoonShine\Resources\QuizAnswer\QuizAnswerResource;
use App\MoonShine\Resources\QuizGroup\QuizGroupResource;
use App\MoonShine\Resources\SchoolYear\SchoolYearResource;
use App\MoonShine\Resources\Setting\SettingResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\MoonShine\Resources\Student\StudentResource;
use App\MoonShine\Resources\StudentDocument\StudentDocumentResource;
use App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource;
use App\MoonShine\Resources\Subject\SubjectResource;
use App\MoonShine\Themes\HananPalette;
use App\Support\PaymentAccess;
use App\Support\SchoolBranding;
use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\InlineCss;
use MoonShine\AssetManager\InlineJs;
use MoonShine\AssetManager\Js;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Crud\Components\Fragment;
use MoonShine\Crud\Components\Layout\Notifications;
use MoonShine\Laravel\Components\Layout\Profile;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\ProfilePage;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Body;
use MoonShine\UI\Components\Layout\Burger;
use MoonShine\UI\Components\Layout\Content;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Favicon;
use MoonShine\UI\Components\Layout\Flash;
use MoonShine\UI\Components\Layout\Html;
use MoonShine\UI\Components\Layout\Layout;
use MoonShine\UI\Components\Layout\Logo;
use MoonShine\UI\Components\Layout\Menu;
use MoonShine\UI\Components\Layout\Sidebar;
use MoonShine\UI\Components\Layout\ThemeSwitcher;
use MoonShine\UI\Components\Layout\Wrapper;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Components\When;

final class CustomLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = HananPalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
            Css::make(asset('css/file-upload-preview.css')),
            Js::make(asset('js/file-upload-preview.js'))->defer(),
            InlineCss::make($this->themeOverrides()),
            InlineJs::make($this->notificationDurationScript()),
            InlineJs::make($this->loadingOverlayScript()),
            InlineJs::make($this->leadingGridActionsScript()),
            InlineJs::make($this->rfidRegistrationScript()),
            InlineJs::make($this->returnToDetailAfterSaveScript()),
            InlineJs::make($this->collegeCourseFiltersScript()),
            InlineJs::make($this->classScheduleSubmitLabelScript()),
            ...($this->easterEggsEnabled()
                ? [InlineJs::make($this->sidebarEasterEggScript())]
                : []),
        ];
    }

    private function notificationDurationScript(): string
    {
        return <<<'JS'
            (() => {
                const setNotificationDuration = () => {
                    if (!window.MoonShine?.config) {
                        return false;
                    }

                    window.MoonShine.config().setToastDuration(5000);

                    return true;
                };

                if (!setNotificationDuration()) {
                    document.addEventListener('moonshine:init', setNotificationDuration, {once: true});
                    document.addEventListener('DOMContentLoaded', setNotificationDuration, {once: true});
                }
            })();
        JS;
    }

    private function themeOverrides(): string
    {
        $theme = array_merge([
            'primary' => '#8F160F',
            'secondary' => '#B32317',
            'background' => '#FFF7F1',
            'text' => '#2F1A17',
            'accent' => '#D7A83D',
            'alert' => '#D92D20',
            'surface' => '#FFFFFF',
            'dark_background' => '#2A0E0B',
            'dark_text' => '#FDEDE8',
        ], config('school_portal.theme', []));

        return <<<CSS
            :root {
                --school-primary: {$theme['primary']};
                --school-secondary: {$theme['secondary']};
                --school-background: {$theme['background']};
                --school-text: {$theme['text']};
                --school-accent: {$theme['accent']};
                --school-alert: {$theme['alert']};
                --school-surface: {$theme['surface']};
                --school-dark-background: {$theme['dark_background']};
                --school-dark-text: {$theme['dark_text']};
                --ms-layout-vertical-menu-width: 320px;
                --ms-menu-space-y: 0.5rem;
                --ms-menu-icon-size: 1.55rem;
                --ms-menu-arrow-size: 1rem;
                --ms-menu-item-gap-x: 0.9rem;
                --ms-menu-item-padding-y: 0.95rem;
                --ms-menu-item-padding-x: 1rem;
                --ms-menu-item-radius: 0.85rem;
                --ms-menu-item-font-size: 1rem;
                --ms-menu-item-font-weight: 600;
                --ms-menu-submenu-space-y: 0.45rem;
            }

            body {
                background: var(--school-background) !important;
                color: var(--school-text) !important;
            }

            .js-table-builder-container [data-admin-actions] {
                min-width: 9rem;
                text-align: left;
            }

            .js-table-builder-container [data-admin-actions] > .flex {
                justify-content: flex-start !important;
            }

            .js-table-builder-container [data-admin-actions].sticky-col {
                right: auto !important;
                left: 0 !important;
            }

            .js-table-builder-container [data-admin-actions-after-selection].sticky-col {
                left: 2.5rem !important;
            }

            .js-table-builder-container .flex:has(> .college-course-filters) {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                align-items: end;
                width: 100%;
            }

            .js-table-builder-container .flex:has(> .college-course-filters) > form,
            .js-table-builder-container .college-course-filters,
            .js-table-builder-container .college-course-filters > .moonshine-field {
                width: 100%;
                min-width: 0;
            }

            .js-table-builder-container .flex:has(> .college-course-filters) > form .form-input {
                width: 100%;
            }

            .js-table-builder-container .college-course-filters {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-column: span 2;
                gap: 1rem;
            }

            @media (max-width: 639px) {
                .js-table-builder-container .flex:has(> .college-course-filters) {
                    grid-template-columns: minmax(0, 1fr);
                }

                .js-table-builder-container .college-course-filters {
                    grid-column: auto;
                }
            }

            .rfid-action {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                width: 2.35rem !important;
                min-width: 2.35rem !important;
                height: 2.35rem !important;
                padding: 0 !important;
                border-width: 1px !important;
                border-style: solid !important;
                border-radius: 0.55rem !important;
            }

            .rfid-action svg {
                width: 1.15rem !important;
                min-width: 1.15rem !important;
                height: 1.15rem !important;
            }

            .rfid-action-register {
                border-color: #2563eb !important;
                background: #dbeafe !important;
                color: #1d4ed8 !important;
            }

            .rfid-action-register:hover {
                background: #bfdbfe !important;
                color: #1e40af !important;
            }

            .rfid-action-remove {
                position: relative;
                border-color: #64748b !important;
                background: #f1f5f9 !important;
                color: #475569 !important;
            }

            .rfid-action.rfid-action-remove::after {
                content: "×";
                position: absolute;
                top: -0.3rem;
                right: -0.3rem;
                display: grid;
                place-items: center;
                width: 1rem;
                height: 1rem;
                border: 2px solid #f1f5f9;
                border-radius: 9999px;
                background: #64748b;
                color: #ffffff;
                font-size: 0.8rem;
                font-weight: 800;
                line-height: 1;
            }

            .rfid-action-remove:hover {
                background: #e2e8f0 !important;
                color: #334155 !important;
            }

            form[data-rfid-registration-form] button[type="submit"],
            form[data-rfid-registration-form] input[type="submit"] {
                display: none !important;
            }

            .rfid-checker-card {
                max-width: 760px;
                margin-inline: auto;
                padding: 1.5rem;
                border: 1px solid color-mix(in srgb, var(--school-primary) 18%, transparent);
                border-radius: 1rem;
                background: var(--school-surface);
                box-shadow: 0 10px 30px color-mix(in srgb, var(--school-primary) 8%, transparent);
                text-align: center;
            }

            .rfid-checker-heading h2 {
                margin: 0;
                font-size: 1.35rem;
                font-weight: 800;
            }

            .rfid-checker-heading p,
            .rfid-checker-hint {
                margin: 0.35rem 0 0;
                opacity: 0.72;
            }

            .rfid-checker-form {
                margin-top: 1.35rem;
            }

            .rfid-checker-form label {
                display: block;
                margin-bottom: 0.4rem;
                font-weight: 700;
            }

            .rfid-checker-controls {
                display: flex;
                justify-content: center;
                gap: 0.75rem;
            }

            .rfid-checker-controls input {
                width: 100%;
                min-height: 2.75rem;
                padding: 0.65rem 0.8rem;
                border: 1px solid color-mix(in srgb, var(--school-text) 25%, transparent);
                border-radius: 0.65rem;
                background: var(--school-surface);
                color: var(--school-text);
            }

            .rfid-checker-result {
                display: grid;
                gap: 0.5rem;
                margin-top: 1.25rem;
                padding: 1rem;
                border-radius: 0.75rem;
            }

            .rfid-checker-result.is-assigned {
                border: 1px solid #22c55e;
                background: color-mix(in srgb, #22c55e 10%, var(--school-surface));
            }

            .rfid-checker-result.is-unassigned,
            .rfid-checker-result.is-error {
                border: 1px solid var(--school-alert);
                background: color-mix(in srgb, var(--school-alert) 8%, var(--school-surface));
            }

            .rfid-checker-result dl {
                display: grid;
                gap: 0.45rem;
                width: min(100%, 34rem);
                margin: 0.25rem auto 0;
                text-align: left;
            }

            .rfid-checker-result dl div {
                display: grid;
                grid-template-columns: minmax(9rem, 0.35fr) 1fr;
                gap: 0.75rem;
            }

            .rfid-checker-result dt {
                font-weight: 700;
            }

            .rfid-checker-result dd {
                margin: 0;
            }

            .rfid-checker-record-link {
                justify-self: center;
                margin-top: 0.5rem;
            }

            @media (max-width: 640px) {
                .rfid-checker-controls {
                    align-items: stretch;
                    flex-direction: column;
                }
            }

            html.dark body {
                background: var(--school-dark-background) !important;
                color: var(--school-dark-text) !important;
            }

            .layout,
            .layout-wrapper,
            .layout-main,
            .layout-menu,
            .layout-sidebar,
            aside {
                background-color: var(--school-background) !important;
            }

            html.dark .layout,
            html.dark .layout-wrapper,
            html.dark .layout-main,
            html.dark .layout-menu,
            html.dark .layout-sidebar,
            html.dark aside {
                background-color: var(--school-dark-background) !important;
            }

            .layout-menu {
                padding-inline: 0.9rem !important;
            }

            .layout-menu .menu,
            .layout-menu .menu-list,
            .layout-menu .menu-item,
            .layout-menu .menu-link,
            .layout-menu .menu-button {
                width: 100%;
            }

            .layout-menu .menu-list {
                gap: 0.55rem !important;
                padding-block: 0.35rem 0.75rem;
            }

            .layout-menu .menu-link,
            .layout-menu .menu-button {
                min-height: 3.55rem;
                align-items: center;
                justify-content: flex-start;
                border: 1px solid transparent;
                box-shadow: 0 1px 0 color-mix(in srgb, var(--school-primary) 8%, transparent);
            }

            .layout-menu .menu-link:hover,
            .layout-menu .menu-button:hover {
                border-color: color-mix(in srgb, var(--school-primary) 18%, #ffffff);
                transform: translateX(2px);
            }

            .layout-menu .menu-icon {
                width: var(--ms-menu-icon-size) !important;
                height: var(--ms-menu-icon-size) !important;
            }

            .layout-menu .menu-header {
                align-items: center;
                gap: 0.75rem;
                padding-bottom: 1rem;
            }

            .layout-menu .menu-logo {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-width: 0;
                flex: 1;
            }

            .layout-menu .sidebar-school-name {
                display: block;
                min-width: 0;
                max-width: 190px;
                color: var(--school-text);
                font-size: 0.95rem;
                font-weight: 800;
                line-height: 1.15;
            }

            .layout-menu .sidebar-school-name::before {
                content: attr(data-school-name);
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .layout-menu .menu-text {
                flex: 1;
                min-width: 0;
                line-height: 1.25;
            }

            .layout-menu .menu-divider {
                margin-block: 0.85rem 0.45rem !important;
            }

            .layout-menu .menu-divider span {
                font-size: 0.78rem !important;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .layout-menu .menu-submenu {
                width: 100%;
                padding-inline: 0.45rem 0 !important;
                padding-block: 0.45rem !important;
            }

            .layout-menu .menu-submenu .menu-link,
            .layout-menu .menu-submenu .menu-button {
                min-height: 3.15rem;
                padding-left: 0.9rem !important;
            }

            .layout-menu .sidebar-credit {
                width: 100%;
                padding: 0.35rem 0.85rem 0.25rem;
                color: color-mix(in srgb, var(--school-text) 62%, #ffffff);
                font-size: 0.78rem;
                font-weight: 700;
                line-height: 1.25;
                text-align: center;
            }

            .layout-menu .sidebar-credit::before {
                content: attr(data-credit);
            }

            .layout-menu .sidebar-datetime {
                width: 100%;
                margin-top: auto;
                padding: 0.9rem 0.85rem 0.2rem;
                color: var(--school-primary);
                font-size: 1.23rem;
                font-weight: 800;
                line-height: 1.25;
                text-align: center;
            }

            .layout-menu .sidebar-easter-egg {
                width: 60%;
                margin: auto auto 0;
                overflow: hidden;
                border: 3px solid color-mix(in srgb, var(--school-accent) 70%, #ffffff);
                border-radius: 0.85rem;
                box-shadow: 0 8px 22px color-mix(in srgb, var(--school-primary) 22%, transparent);
            }

            .layout-menu .sidebar-easter-egg[hidden] {
                display: none;
            }

            .layout-menu .sidebar-easter-egg img {
                display: block;
                width: 100%;
                height: auto;
            }

            .layout-menu .sidebar-easter-egg-message {
                margin: 0;
                padding: 0.65rem 0.75rem;
                background: color-mix(in srgb, var(--school-accent) 18%, #ffffff);
                color: var(--school-primary);
                font-size: 1.05rem;
                font-weight: 900;
                line-height: 1.2;
                text-align: center;
            }

            .layout-menu .sidebar-easter-egg:not([hidden]) + .sidebar-datetime {
                margin-top: 0;
            }

            .layout-menu .sidebar-credit {
                user-select: none;
            }

            html.dark .layout-menu .sidebar-school-name {
                color: var(--school-dark-text);
            }

            html.dark .layout-menu .sidebar-credit {
                color: color-mix(in srgb, var(--school-dark-text) 62%, var(--school-dark-background));
            }

            html.dark .layout-menu .sidebar-datetime {
                color: color-mix(in srgb, var(--school-accent) 82%, #ffffff);
            }

            @media (min-width: 1024px) {
                .layout-menu:not(._is-minimized) {
                    width: min(var(--ms-layout-vertical-menu-width), 100%) !important;
                }

                .layout-menu._is-minimized .sidebar-school-name,
                .layout-menu._is-minimized .sidebar-easter-egg,
                .layout-menu._is-minimized .sidebar-datetime,
                .layout-menu._is-minimized .sidebar-credit {
                    display: none;
                }

                .layout-menu._is-minimized .menu-text,
                .layout-menu._is-minimized .menu-badge,
                .layout-menu._is-minimized .menu-arrow,
                .layout-menu._is-minimized .menu-divider span {
                    display: none !important;
                }

                .layout-menu._is-minimized .menu-link,
                .layout-menu._is-minimized .menu-button {
                    min-height: 3.15rem;
                    justify-content: center !important;
                    padding-inline: 0 !important;
                    gap: 0 !important;
                }

                .layout-menu._is-minimized .menu-icon {
                    margin: 0 !important;
                }

                .layout-menu._is-minimized .menu-submenu {
                    padding-inline: 0 !important;
                }
            }

            .layout-page,
            .card,
            .box,
            .table-builder,
            .dropdown,
            .form,
            .toast {
                background-color: var(--school-surface) !important;
            }

            html.dark .layout-page,
            html.dark .card,
            html.dark .box,
            html.dark .table-builder,
            html.dark .dropdown,
            html.dark .form,
            html.dark .toast {
                background-color: color-mix(in srgb, var(--school-dark-background) 78%, #ffffff) !important;
                color: var(--school-dark-text) !important;
            }

            .modal {
                background-color: transparent !important;
            }

            .modal-backdrop {
                background-color: rgb(0 0 0 / 55%) !important;
                backdrop-filter: blur(3px);
            }

            .modal-content {
                background-color: var(--school-surface) !important;
                opacity: 1;
            }

            html.dark .modal-content {
                background-color: color-mix(in srgb, var(--school-dark-background) 78%, #ffffff) !important;
                color: var(--school-dark-text) !important;
            }

            /*
             * Keep the School Years fields together without narrowing the table
             * itself, so its background, border, and row separators remain intact.
             */
            .school-year-table.table-list {
                width: 100% !important;
                table-layout: fixed;
            }

            .school-year-table.table-list th:nth-child(1),
            .school-year-table.table-list td:nth-child(1) {
                width: 4rem;
            }

            .school-year-table.table-list th:nth-child(2),
            .school-year-table.table-list td:nth-child(2) {
                width: 13rem;
            }

            .school-year-table.table-list th:nth-child(3),
            .school-year-table.table-list td:nth-child(3) {
                width: 7rem;
            }

            .school-year-table.table-list th:nth-child(4),
            .school-year-table.table-list td:nth-child(4) {
                width: 13rem;
            }

            .school-year-table.table-list th:nth-child(5),
            .school-year-table.table-list td:nth-child(5) {
                text-align: left !important;
            }

            .compact-dashboard-metric {
                --ms-report-card-gap-y: 0.5rem;
                --ms-report-card-padding-y: 0.7rem;
                --ms-report-card-padding-x: 0.8rem;
                --ms-report-card-heading-icon-size: 1.15rem;
                --ms-report-card-value-font-size: 1.25rem;
                --ms-report-card-title-font-size: 0.75rem;
                min-height: 5.75rem;
            }

            .compact-dashboard-metric--text .report-card-value {
                overflow: visible;
                white-space: normal;
                text-overflow: clip;
                font-size: 0.85rem;
                line-height: 1.25;
            }

            .dashboard-section,
            .dashboard-panel,
            .dashboard-card,
            .dashboard-table-scroll {
                min-width: 0;
                max-width: 100%;
            }

            body:has(.dashboard-section),
            .layout-main:has(.dashboard-section),
            .layout-page:has(.dashboard-section) {
                overflow-x: clip;
            }

            .dashboard-panel {
                border-color: color-mix(in srgb, var(--school-primary) 20%, #ffffff) !important;
                background: var(--school-surface) !important;
                color: var(--school-text) !important;
            }

            .dashboard-card {
                border-color: color-mix(in srgb, var(--school-primary) 14%, #ffffff) !important;
                background: color-mix(in srgb, var(--school-primary) 3%, var(--school-surface)) !important;
                color: var(--school-text) !important;
            }

            .dashboard-card[data-dashboard-tone="danger"] {
                border-color: #fecaca !important;
                background: #fef2f2 !important;
            }

            .dashboard-card[data-dashboard-tone="warning"] {
                border-color: #fde68a !important;
                background: #fffbeb !important;
            }

            .dashboard-card[data-dashboard-tone="success"] {
                border-color: #a7f3d0 !important;
                background: #ecfdf5 !important;
            }

            .dashboard-heading,
            .dashboard-value {
                color: var(--school-text) !important;
            }

            .dashboard-muted {
                color: color-mix(in srgb, var(--school-text) 70%, #ffffff) !important;
            }

            .dashboard-warning-text {
                color: #92400e !important;
            }

            html.dark .dashboard-panel {
                border-color: color-mix(in srgb, var(--school-primary) 55%, var(--school-dark-text)) !important;
                background: color-mix(in srgb, var(--school-dark-background) 86%, #ffffff) !important;
                color: var(--school-dark-text) !important;
            }

            html.dark .dashboard-card {
                border-color: color-mix(in srgb, var(--school-primary) 45%, var(--school-dark-text)) !important;
                background: color-mix(in srgb, var(--school-dark-background) 92%, #ffffff) !important;
                color: var(--school-dark-text) !important;
            }

            html.dark .dashboard-card[data-dashboard-tone="danger"] {
                border-color: #7f1d1d !important;
                background: color-mix(in srgb, #7f1d1d 35%, var(--school-dark-background)) !important;
            }

            html.dark .dashboard-card[data-dashboard-tone="warning"] {
                border-color: #78350f !important;
                background: color-mix(in srgb, #78350f 35%, var(--school-dark-background)) !important;
            }

            html.dark .dashboard-card[data-dashboard-tone="success"] {
                border-color: #065f46 !important;
                background: color-mix(in srgb, #065f46 30%, var(--school-dark-background)) !important;
            }

            html.dark .dashboard-heading,
            html.dark .dashboard-value {
                color: var(--school-dark-text) !important;
            }

            html.dark .dashboard-muted {
                color: color-mix(in srgb, var(--school-dark-text) 76%, var(--school-dark-background)) !important;
            }

            html.dark .dashboard-warning-text {
                color: #fcd34d !important;
            }

            .dashboard-control,
            .dashboard-link,
            .dashboard-table-action {
                min-height: 44px !important;
            }

            .dashboard-control {
                padding-block: 0.55rem !important;
            }

            select.dashboard-control,
            input.dashboard-control {
                width: 100%;
            }

            .dashboard-link,
            .dashboard-table-action {
                display: inline-flex;
                align-items: center;
            }

            .dashboard-table-action {
                min-width: 44px;
                justify-content: center;
                padding-inline: 0.5rem;
            }

            .dashboard-table-scroll {
                width: 100%;
                overflow-x: auto;
                overscroll-behavior-inline: contain;
            }

            .dashboard-table-wide {
                min-width: 47.5rem;
            }

            .dashboard-table-medium {
                min-width: 40rem;
            }

            .dashboard-summary-card {
                min-height: 6.25rem;
            }

            .dashboard-chart {
                min-width: 0;
                max-width: 100%;
                padding: 0.75rem;
                border: 1px solid color-mix(in srgb, var(--school-primary) 14%, #ffffff);
                border-radius: 0.75rem;
                background: #ffffff;
            }

            @media (min-width: 1024px) and (max-width: 1199px) {
                :root {
                    --ms-layout-vertical-menu-width: 248px;
                }

                .layout-menu {
                    padding-inline: 0.6rem !important;
                }

                .layout-menu .menu-link,
                .layout-menu .menu-button {
                    min-height: 3.25rem;
                    padding-inline: 0.75rem !important;
                }
            }

            @media (max-width: 639px) {
                .btn-burger,
                .theme-switcher-btn,
                .layout-header button {
                    min-width: 44px !important;
                    min-height: 44px !important;
                }

                .dashboard-table-scroll {
                    border-top: 1px solid color-mix(in srgb, var(--school-primary) 14%, transparent);
                }
            }

            @media (min-width: 1280px) {
                .attendance-class-summary-grid {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                }

                .attendance-class-summary-grid > * {
                    grid-column: span 1 / span 1;
                }
            }

            .btn-primary,
            .btn-primary:hover,
            button.btn-primary,
            a.btn-primary,
            [class*="btn-primary"],
            [class*="bg-primary"],
            [class*="bg-purple"],
            [class*="bg-indigo"] {
                background-color: var(--school-primary) !important;
                border-color: var(--school-primary) !important;
                color: #ffffff !important;
            }

            .btn-secondary,
            [class*="bg-secondary"] {
                background-color: var(--school-secondary) !important;
                border-color: var(--school-secondary) !important;
                color: #ffffff !important;
            }

            .btn-warning,
            [class*="bg-warning"] {
                background-color: var(--school-accent) !important;
                border-color: var(--school-accent) !important;
                color: #ffffff !important;
            }

            .btn-outline-primary,
            .text-primary,
            [class*="text-primary"],
            [class*="text-purple"],
            [class*="text-indigo"] {
                color: var(--school-primary) !important;
            }

            .border-primary,
            [class*="border-primary"],
            input:focus,
            select:focus,
            textarea:focus {
                border-color: var(--school-primary) !important;
            }

            .menu-inner a:hover,
            .menu-inner .active,
            .menu-inner [aria-current="page"],
            .menu-button._is-active,
            .menu-link._is-active,
            .menu-item:hover,
            .menu-item_active,
            .menu-item--active {
                background-color: color-mix(in srgb, var(--school-primary) 10%, #ffffff) !important;
                color: var(--school-primary) !important;
            }

            html.dark .menu-inner a:hover,
            html.dark .menu-inner .active,
            html.dark .menu-inner [aria-current="page"],
            html.dark .menu-button._is-active,
            html.dark .menu-link._is-active,
            html.dark .menu-item:hover,
            html.dark .menu-item_active,
            html.dark .menu-item--active {
                background-color: color-mix(in srgb, var(--school-primary) 34%, var(--school-dark-background)) !important;
                color: #ffffff !important;
            }

            .menu-inner .active *,
            .menu-inner [aria-current="page"] *,
            .menu-button._is-active *,
            .menu-link._is-active *,
            .menu-item_active *,
            .menu-item--active * {
                color: var(--school-primary) !important;
            }

            html.dark .menu-inner .active *,
            html.dark .menu-inner [aria-current="page"] *,
            html.dark .menu-button._is-active *,
            html.dark .menu-link._is-active *,
            html.dark .menu-item_active *,
            html.dark .menu-item--active * {
                color: #ffffff !important;
            }

            .form-input,
            .form-select,
            .form-textarea,
            .search-form-field {
                background-color: var(--school-surface) !important;
                border-color: color-mix(in srgb, var(--school-primary) 32%, #ffffff) !important;
                color: var(--school-text) !important;
            }

            html.dark .form-input,
            html.dark .form-select,
            html.dark .form-textarea,
            html.dark .search-form-field {
                background-color: color-mix(in srgb, var(--school-dark-background) 86%, #ffffff) !important;
                border-color: color-mix(in srgb, var(--school-primary) 58%, #ffffff) !important;
                color: var(--school-dark-text) !important;
            }

            .form-input::placeholder,
            .form-select::placeholder,
            .form-textarea::placeholder,
            .search-form-field::placeholder {
                color: color-mix(in srgb, var(--school-text) 45%, #ffffff) !important;
            }

            .badge,
            .count,
            .form-switch input:checked,
            input[type="checkbox"]:checked,
            input[type="radio"]:checked {
                background-color: var(--school-primary) !important;
                border-color: var(--school-primary) !important;
            }

            .badge,
            .badge *,
            a.badge,
            a.badge:visited,
            a.badge:hover,
            a.badge:focus {
                color: #ffffff !important;
            }

            .alert,
            .alert-warning,
            [class*="alert-warning"] {
                background-color: color-mix(in srgb, var(--school-accent) 22%, #ffffff) !important;
                border-color: color-mix(in srgb, var(--school-accent) 40%, #ffffff) !important;
                color: var(--school-text) !important;
            }

            .alert-error,
            .alert.alert-error,
            [class~="alert-error"] {
                --ms-alert-border-color: #7f1d1d;
                --ms-alert-bg-color: #b91c1c;
                --ms-alert-color: #ffffff;
                border: 2px solid #7f1d1d !important;
                border-left-width: 6px !important;
                background-color: #b91c1c !important;
                color: #ffffff !important;
                box-shadow: 0 10px 24px rgb(127 29 29 / 28%) !important;
                font-weight: 700;
                opacity: 1 !important;
            }

            .alert-error .alert-icon,
            .alert-error .alert-content,
            .alert-error .alert-content *,
            .alert-error svg {
                color: #ffffff !important;
                opacity: 1 !important;
            }

            .modal-template .modal-body > form {
                width: 100%;
                min-width: 0;
                max-width: 100%;
            }

            .modal-template .modal-body > form .heading {
                overflow-wrap: anywhere;
            }

            .modal-template .modal-body > img {
                display: block;
                max-height: 75vh;
                margin: auto;
                object-fit: contain;
            }

            @media (min-width: 1024px) {
                .student-detail-two-column {
                    width: 100% !important;
                    table-layout: fixed;
                }

                .student-detail-two-column td.table-grid {
                    width: 100% !important;
                    max-width: none !important;
                }

                .student-detail-two-column .table-grid {
                    display: grid !important;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    column-gap: 2rem;
                    row-gap: 0;
                    width: 100% !important;
                }

                .student-detail-two-column .table-grid > .grid {
                    display: flex !important;
                    min-width: 0;
                    padding: 0.85rem 0;
                    border-bottom: 1px solid color-mix(in srgb, var(--school-primary) 32%, transparent);
                    flex-direction: column;
                    gap: 0.4rem;
                }

                .student-detail-two-column .table-grid > .grid > [class*="col-span-"] {
                    grid-column: auto !important;
                    width: 100%;
                    min-width: 0;
                }

                .student-detail-two-column .table-grid > .grid .form-label {
                    font-size: 0.75rem;
                    font-weight: 700;
                    line-height: 1.3;
                    opacity: 0.72;
                }

                .student-detail-two-column .table-grid > .grid img {
                    max-width: 8rem;
                }

                .student-detail-two-column .table-grid > .flex {
                    grid-column: 1 / -1;
                }
            }

            #admin-page-loader {
                position: fixed;
                z-index: 99999;
                inset: 0;
                display: none;
                place-items: center;
                background: color-mix(in srgb, var(--school-background) 78%, transparent);
                backdrop-filter: blur(2px);
            }

            #admin-page-loader.is-visible {
                display: grid;
            }

            .admin-page-loader-card {
                display: flex;
                align-items: center;
                gap: 0.85rem;
                padding: 0.9rem 1.15rem;
                border: 1px solid color-mix(in srgb, var(--school-primary) 25%, #ffffff);
                border-radius: 0.9rem;
                background: var(--school-surface);
                color: var(--school-text);
                box-shadow: 0 16px 45px color-mix(in srgb, var(--school-text) 16%, transparent);
                font-size: 0.9rem;
                font-weight: 700;
            }

            .admin-page-loader-spinner {
                width: 1.6rem;
                height: 1.6rem;
                border: 3px solid color-mix(in srgb, var(--school-primary) 20%, #ffffff);
                border-top-color: var(--school-primary);
                border-radius: 9999px;
                animation: admin-page-loader-spin 0.7s linear infinite;
            }

            @keyframes admin-page-loader-spin {
                to { transform: rotate(360deg); }
            }

            @media (prefers-reduced-motion: reduce) {
                .admin-page-loader-spinner { animation-duration: 1.4s; }
            }
        CSS;
    }

    private function loadingOverlayScript(): string
    {
        return <<<'JS'
            (() => {
                let showTimer;
                let safetyTimer;

                const loader = () => document.getElementById('admin-page-loader');
                const hide = () => {
                    clearTimeout(showTimer);
                    clearTimeout(safetyTimer);
                    loader()?.classList.remove('is-visible');
                };
                const show = () => {
                    clearTimeout(showTimer);
                    showTimer = setTimeout(() => {
                        loader()?.classList.add('is-visible');
                        safetyTimer = setTimeout(hide, 15000);
                    }, 120);
                };

                document.addEventListener('DOMContentLoaded', () => {
                    if (!loader()) {
                        document.body.insertAdjacentHTML('beforeend', `
                            <div id="admin-page-loader" role="status" aria-live="polite" aria-label="Loading page">
                                <div class="admin-page-loader-card">
                                    <span class="admin-page-loader-spinner" aria-hidden="true"></span>
                                    <span>Loading page…</span>
                                </div>
                            </div>
                        `);
                    }

                    document.addEventListener('click', (event) => {
                        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                            return;
                        }

                        const link = event.target.closest('a[href]');
                        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
                            return;
                        }

                        const url = new URL(link.href, window.location.href);
                        if (url.origin !== window.location.origin || url.hash && url.pathname === window.location.pathname || url.protocol === 'javascript:') {
                            return;
                        }

                        if (url.pathname.includes('/export') || url.pathname.includes('/download')) {
                            return;
                        }

                        show();
                    });

                    document.addEventListener('submit', (event) => {
                        if (!event.defaultPrevented) {
                            show();
                        }
                    });
                });

                window.addEventListener('pageshow', hide);
                window.addEventListener('beforeunload', show);
            })();
        JS;
    }

    private function leadingGridActionsScript(): string
    {
        return <<<'JS'
            (() => {
                let scheduled = false;

                const isActionCell = (cell) => {
                    if (!cell || cell.hasAttribute('data-column-selection')) {
                        return false;
                    }

                    return cell.querySelector(
                        'a.btn, button.btn, [data-button-type="modal-button"], .dropdown-menu'
                    ) !== null;
                };

                const placeActionCell = (row, actionCell) => {
                    const firstCell = row.cells[0];
                    const hasSelectionCheckbox = firstCell?.querySelector(
                        '.js-table-action-row, .js-actions-all-checked'
                    ) !== null;

                    actionCell.setAttribute('data-admin-actions', '');

                    if (hasSelectionCheckbox) {
                        actionCell.setAttribute('data-admin-actions-after-selection', '');
                        firstCell.after(actionCell);

                        return;
                    }

                    actionCell.removeAttribute('data-admin-actions-after-selection');
                    row.prepend(actionCell);
                };

                const moveActionsFirst = (table) => {
                    const bodyRows = Array.from(table.tBodies)
                        .flatMap((body) => Array.from(body.rows));

                    const hasActions = bodyRows.some((row) => {
                        if (row.cells.length < 2) {
                            return false;
                        }

                        if (row.querySelector('[data-admin-actions]')) {
                            return true;
                        }

                        return isActionCell(row.cells[row.cells.length - 1]);
                    });

                    if (!hasActions) {
                        return;
                    }

                    bodyRows.forEach((row) => {
                        if (row.cells.length < 2 || row.querySelector('[data-admin-actions]')) {
                            return;
                        }

                        const actionCell = row.cells[row.cells.length - 1];

                        if (isActionCell(actionCell)) {
                            placeActionCell(row, actionCell);
                        }
                    });

                    [table.tHead, table.tFoot]
                        .filter(Boolean)
                        .forEach((section) => {
                            Array.from(section.rows).forEach((row) => {
                                if (row.cells.length < 2 || row.querySelector('[data-admin-actions]')) {
                                    return;
                                }

                                const actionHeader = row.cells[row.cells.length - 1];

                                if (!actionHeader.hasAttribute('data-column-selection')) {
                                    actionHeader.setAttribute('aria-label', 'Actions');
                                    placeActionCell(row, actionHeader);
                                }
                            });
                        });
                };

                const arrangeTables = () => {
                    scheduled = false;
                    document
                        .querySelectorAll('.js-table-builder-container table')
                        .forEach(moveActionsFirst);
                };

                const scheduleArrange = () => {
                    if (scheduled) {
                        return;
                    }

                    scheduled = true;
                    window.requestAnimationFrame(arrangeTables);
                };

                document.addEventListener('DOMContentLoaded', () => {
                    scheduleArrange();

                    new MutationObserver(scheduleArrange).observe(document.body, {
                        childList: true,
                        subtree: true,
                    });
                });
            })();
        JS;
    }

    private function sidebarEasterEggScript(): string
    {
        return <<<'JS'
            (() => {
                let clickCount = 0;
                let hideTimer;

                const resetSequence = () => {
                    clickCount = 0;
                };

                const showEasterEgg = () => {
                    const easterEgg = document.querySelector('[data-sidebar-easter-egg]');

                    if (!easterEgg) {
                        return;
                    }

                    if (!easterEgg.querySelector('img')) {
                        const image = document.createElement('img');
                        image.src = easterEgg.dataset.src;
                        image.alt = 'Three dancing kittens';

                        const message = document.createElement('p');
                        message.className = 'sidebar-easter-egg-message';
                        
                        message.textContent = 'Happy Friday';

                        easterEgg.append(image, message);
                    }

                    window.clearTimeout(hideTimer);
                    easterEgg.hidden = false;
                    hideTimer = window.setTimeout(() => {
                        easterEgg.hidden = true;
                    }, 7000);
                };

                const fridayDateKey = (parts) => [
                    parts.get('year'),
                    parts.get('month'),
                    parts.get('day'),
                ].join('-');

                const checkFridayCelebration = () => {
                    const easterEgg = document.querySelector('[data-sidebar-easter-egg]');

                    if (!easterEgg) {
                        return;
                    }

                    const parts = new Map(
                        new Intl.DateTimeFormat('en-US', {
                            timeZone: easterEgg.dataset.timezone,
                            weekday: 'short',
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            hourCycle: 'h23',
                        }).formatToParts(new Date()).map(({type, value}) => [type, value])
                    );

                    if (parts.get('weekday') !== 'Fri'
                        || parts.get('hour') !== '17'
                        || parts.get('minute') !== '00') {
                        return;
                    }

                    const celebrationKey = fridayDateKey(parts);

                    if (window.localStorage.getItem('adminFridayEasterEgg') === celebrationKey) {
                        return;
                    }

                    window.localStorage.setItem('adminFridayEasterEgg', celebrationKey);
                    showEasterEgg();
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-sidebar-easter-egg-trigger]');

                    if (!trigger) {
                        resetSequence();

                        return;
                    }

                    clickCount += 1;

                    if (clickCount < 7) {
                        return;
                    }

                    resetSequence();
                    showEasterEgg();
                });

                document.addEventListener('DOMContentLoaded', () => {
                    checkFridayCelebration();
                    window.setInterval(checkFridayCelebration, 1000);
                });
            })();
        JS;
    }

    private function returnToDetailAfterSaveScript(): string
    {
        return <<<'JS'
            (() => {
                const detailMatch = window.location.pathname.match(
                    /^(.*\/resource\/[^/]+)\/detail-page\/([^/]+)\/?$/
                );

                const prepareDetailEditLinks = () => {
                    if (!detailMatch) {
                        return;
                    }

                    const expectedFormPath = `${detailMatch[1]}/form-page/${detailMatch[2]}`;
                    const returnUrl = window.location.href;

                    document.querySelectorAll('a.js-edit-button[href]').forEach((link) => {
                        const editUrl = new URL(link.href, window.location.origin);

                        if (editUrl.origin !== window.location.origin
                            || editUrl.pathname.replace(/\/$/, '') !== expectedFormPath) {
                            return;
                        }

                        editUrl.searchParams.set('_redirect', returnUrl);
                        link.href = editUrl.toString();
                    });
                };

                const prepareEditFormRedirect = () => {
                    const requestedRedirect = new URLSearchParams(window.location.search).get('_redirect');

                    if (!requestedRedirect) {
                        return;
                    }

                    let returnUrl;

                    try {
                        returnUrl = new URL(requestedRedirect, window.location.origin);
                    } catch {
                        return;
                    }

                    if (returnUrl.origin !== window.location.origin
                        || !/\/resource\/[^/]+\/detail-page\/[^/]+\/?$/.test(returnUrl.pathname)) {
                        return;
                    }

                    document.querySelectorAll('form[action]').forEach((form) => {
                        const method = form.querySelector('input[name="_method"]')?.value.toUpperCase();

                        if (!['PUT', 'PATCH'].includes(method) || form.querySelector('input[name="_redirect"]')) {
                            return;
                        }

                        const redirect = document.createElement('input');
                        redirect.type = 'hidden';
                        redirect.name = '_redirect';
                        redirect.value = returnUrl.toString();
                        form.append(redirect);
                    });
                };

                const prepareRedirects = () => {
                    prepareDetailEditLinks();
                    prepareEditFormRedirect();
                };

                document.addEventListener('DOMContentLoaded', () => {
                    prepareRedirects();
                    new MutationObserver(prepareRedirects).observe(document.body, {
                        childList: true,
                        subtree: true,
                    });
                });
            })();
        JS;
    }

    private function collegeCourseFiltersScript(): string
    {
        return <<<'JS'
            (() => {
                const applyCourseFilters = (container) => {
                    const year = container.querySelector(
                        '[data-college-course-filter="year"]'
                    )?.value ?? '';
                    const semester = container.querySelector(
                        '[data-college-course-filter="semester"]'
                    )?.value ?? '';

                    container.querySelectorAll('[data-college-course-row]').forEach((row) => {
                        row.hidden = (year !== '' && row.dataset.courseYear !== year)
                            || (semester !== '' && row.dataset.courseSemester !== semester);
                    });
                };

                document.addEventListener('change', (event) => {
                    if (!event.target.matches('[data-college-course-filter]')) {
                        return;
                    }

                    const container = event.target.closest('.js-table-builder-container');

                    if (container) {
                        applyCourseFilters(container);
                    }
                });

                document.addEventListener('DOMContentLoaded', () => {
                    document
                        .querySelectorAll('.js-table-builder-container:has([data-college-course-filter])')
                        .forEach(applyCourseFilters);
                });
            })();
        JS;
    }

    private function classScheduleSubmitLabelScript(): string
    {
        return <<<'JS'
            (() => {
                const label = 'Save and Create Schedule';

                const updateCreateForm = (form) => {
                    const isClassForm = form.querySelector('[name="course_code"]')
                        && form.querySelector('[name="year_level"]')
                        && form.querySelector('[name="semester"]');
                    const isEditForm = form.querySelector('[name="_method"]');

                    if (!isClassForm || isEditForm) {
                        return;
                    }

                    const button = form.querySelector('button[type="submit"]');

                    if (!button || button.dataset.classScheduleSubmitLabel === 'true') {
                        return;
                    }

                    const labelNode = Array.from(button.childNodes).find(
                        (node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() === 'Save'
                    );

                    if (!labelNode) {
                        return;
                    }

                    labelNode.nodeValue = ` ${label} `;
                    button.dataset.classScheduleSubmitLabel = 'true';
                };

                const updateCreateForms = () => {
                    document.querySelectorAll('form').forEach(updateCreateForm);
                };

                document.addEventListener('DOMContentLoaded', () => {
                    updateCreateForms();

                    new MutationObserver(updateCreateForms).observe(document.body, {
                        childList: true,
                        subtree: true,
                    });
                });
            })();
        JS;
    }

    private function easterEggsEnabled(): bool
    {
        return filter_var(
            config('school_portal.features.easter_eggs', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function rfidRegistrationScript(): string
    {
        return <<<'JS'
            (() => {
                const submitTimers = new WeakMap();

                const resetRegistrationInput = (input) => {
                    window.clearTimeout(submitTimers.get(input));
                    input.dataset.rfidScannerTouched = '';
                    input.value = '';

                    const form = input.closest('form');

                    if (form) {
                        delete form.dataset.rfidAutoSubmitting;
                    }

                    input.focus({preventScroll: true});
                };

                const registerMoonShineCallbacks = (attempt = 0) => {
                    if (!window.MoonShine?.onCallback) {
                        if (attempt < 40) {
                            window.setTimeout(() => registerMoonShineCallbacks(attempt + 1), 25);
                        }

                        return;
                    }

                    window.MoonShine.onCallback('rfidCardRemoved', (_data, type) => {
                        if (type === 'error') {
                            return;
                        }

                        document
                            .querySelectorAll('[data-rfid-page-remove-control]')
                            .forEach((control) => control.remove());

                        document
                            .querySelectorAll('[data-rfid-detail-field]')
                            .forEach((field) => field.remove());
                    });
                };

                registerMoonShineCallbacks();

                const visibleRfidInput = () => Array.from(
                    document.querySelectorAll('[data-rfid-registration-input]')
                ).find((input) => {
                    const modal = input.closest('.modal');

                    return modal && modal.getClientRects().length > 0;
                });

                const focusScannerField = (attempt = 0) => {
                    const input = visibleRfidInput();

                    if (!input) {
                        if (attempt < 20) {
                            window.setTimeout(() => focusScannerField(attempt + 1), 25);
                        }

                        return;
                    }

                    input.dataset.rfidScannerTouched = '';
                    input.value = '';
                    input.focus({preventScroll: true});
                };

                window.addEventListener('toast', (event) => {
                    if (event.detail?.type !== 'error') {
                        return;
                    }

                    const input = visibleRfidInput();

                    if (input) {
                        resetRegistrationInput(input);
                    }
                });

                const submitRegistration = (input) => {
                    if (input.dataset.rfidScannerTouched !== '1' || input.value.trim() === '') {
                        return;
                    }

                    const form = input.closest('form');

                    if (!form || form.dataset.rfidAutoSubmitting === '1') {
                        return;
                    }

                    form.dataset.rfidAutoSubmitting = '1';
                    form.requestSubmit();

                    window.setTimeout(() => {
                        delete form.dataset.rfidAutoSubmitting;
                    }, 1500);
                };

                document.addEventListener('click', (event) => {
                    if (event.target.closest('[data-rfid-register-trigger]')) {
                        focusScannerField();
                    }
                });

                document.addEventListener('input', (event) => {
                    const input = event.target.closest('[data-rfid-registration-input]');

                    if (!input) {
                        return;
                    }

                    input.dataset.rfidScannerTouched = '1';
                    window.clearTimeout(submitTimers.get(input));
                    submitTimers.set(
                        input,
                        window.setTimeout(() => submitRegistration(input), 650)
                    );
                });

                document.addEventListener('keydown', (event) => {
                    const input = event.target.closest('[data-rfid-registration-input]');

                    if (!input || event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    input.dataset.rfidScannerTouched = '1';
                    window.clearTimeout(submitTimers.get(input));
                    submitRegistration(input);
                });

                document.addEventListener('DOMContentLoaded', () => {
                    const checkerInput = document.querySelector('[data-rfid-checker-input]');

                    if (!checkerInput) {
                        return;
                    }

                    checkerInput.value = '';
                    checkerInput.focus({preventScroll: true});
                });

                document.addEventListener('input', (event) => {
                    const input = event.target.closest('[data-rfid-checker-input]');

                    if (!input) {
                        return;
                    }

                    window.clearTimeout(submitTimers.get(input));
                    submitTimers.set(input, window.setTimeout(() => {
                        if (input.value.trim() !== '') {
                            input.closest('form')?.requestSubmit();
                        }
                    }, 650));
                });

                document.addEventListener('keydown', (event) => {
                    const input = event.target.closest('[data-rfid-checker-input]');

                    if (!input || event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    window.clearTimeout(submitTimers.get(input));

                    if (input.value.trim() !== '') {
                        input.closest('form')?.requestSubmit();
                    }
                });
            })();
        JS;
    }

    public function build(): Layout
    {
        return Layout::make([
            Html::make([
                $this->getHeadComponent(),
                Body::make([
                    Wrapper::make([
                        $this->getSidebarComponent(),

                        Div::make([
                            Fragment::make([
                                Flash::make(),

                                ...$this->createdCredentialsModal(),

                                $this->getHeaderComponent(),

                                Content::make($this->getContentComponents()),
                            ])->class(['layout-page', 'layout-page-simple' => $this->contentSimpled])->name(self::CONTENT_FRAGMENT_NAME),
                        ])->class(['layout-main', 'layout-main-centered' => $this->contentCentered])->customAttributes(['id' => self::CONTENT_ID]),
                    ]),
                ]),
            ])
                ->customAttributes([
                    'lang' => $this->getHeadLang(),
                ])
                ->withAlpineJs()
                ->when(
                    $this->hasThemes() || $this->isAlwaysDark(),
                    fn (Html $html): Html => $html->withThemes($this->isAlwaysDark())
                ),
        ]);
    }

    protected function getSidebarComponent(): Sidebar
    {
        return Sidebar::make([
            Fragment::make([
                Div::make([
                    $this->getLogoComponent()->minimized(),
                    Div::make()
                        ->class('sidebar-school-name')
                        ->customAttributes(['data-school-name' => $this->getSchoolName()]),
                ])->class('menu-logo'),
                Div::make([
                    When::make(
                        fn (): bool => $this->isUseNotifications(),
                        static fn (): array => [Notifications::make()],
                    ),
                    When::make(
                        fn (): bool => $this->hasThemes() && ! $this->isAlwaysDark(),
                        static fn (): array => [ThemeSwitcher::make()]
                    ),
                    ...$this->sidebarTopSlot(),
                ])->class('menu-actions'),
                Div::make([
                    Burger::make()->sidebar(),
                ])->class('menu-burger'),
            ])->class('menu-header')->name('sidebar-top'),

            Fragment::make([
                ...$this->sidebarSlot(),
                Menu::make(),
            ])->customAttributes([
                'class' => 'menu menu--vertical',
            ])->name('sidebar-content'),

            ...($this->easterEggsEnabled()
                ? [
                    Div::make()
                        ->class('sidebar-easter-egg')
                        ->customAttributes([
                            'data-sidebar-easter-egg' => '',
                            'data-src' => asset('images/admin-cats-easter-egg.webp'),
                            'data-timezone' => config('school_portal.timezone', 'Asia/Manila'),
                            'hidden' => true,
                        ]),
                ]
                : []),

            Div::make()
                ->class('sidebar-datetime')
                ->customAttributes([
                    'x-data' => "{ now: new Date(), tick() { this.now = new Date() }, format() { return this.now.toLocaleString([], { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) } }",
                    'x-init' => 'tick(); setInterval(() => tick(), 30000)',
                    'x-text' => 'format()',
                ]),

            Div::make()
                ->class('sidebar-credit')
                ->customAttributes(array_filter([
                    'data-credit' => 'Developed by ZenCraft Systems',
                    'data-sidebar-easter-egg-trigger' => $this->easterEggsEnabled() ? '' : null,
                ], static fn (mixed $value): bool => $value !== null)),
        ])->collapsed();
    }

    private function getSchoolName(): string
    {
        return SchoolBranding::name();
    }

    private function createdCredentialsModal(): array
    {
        $credentials = session('admin_created_student_credentials')
            ?? session('admin_created_adviser_credentials');

        if (! $credentials) {
            return [];
        }

        return [
            Modal::make(
                title: $credentials['title'] ?? 'Account Credentials',
                content: view('admin.components.credentials-modal', [
                    'credentials' => $credentials,
                ]),
                name: 'created-credentials-modal'
            )
                ->open()
                ->closeOutside(false),
        ];
    }

    protected function menu(): array
    {
        $staffEnabled = filter_var(
            config('school_portal.features.staff_module', true),
            FILTER_VALIDATE_BOOLEAN,
        );
        $staffAttendanceEnabled = filter_var(
            config('school_portal.features.teacher_staff_attendance', true),
            FILTER_VALIDATE_BOOLEAN,
        );
        $toolsMenu = Setting::enabled('rfid_enabled', true)
            ? [
                MenuGroup::make('Tools', [
                    MenuItem::make(RfidChecker::class, label: 'RFID Checker', icon: 'magnifying-glass'),
                ]),
            ]
            : [];
        $quizMenu = config('school_portal.features.quiz_module')
            ? [
                MenuGroup::make('Quizzes & Exams', [
                    MenuItem::make(QuizResource::class, label: 'Quizzes', icon: 'pencil-square'),
                    MenuItem::make(QuizGroupResource::class, label: 'Quiz Groups', icon: 'folder'),
                    MenuItem::make(QuizAnswerResource::class, label: 'Answers', icon: 'chat-bubble-left-right'),
                ]),
            ]
            : [];
        $paymentMenu = config('school_portal.features.payments_module')
            && PaymentAccess::isAuthorizedAdmin(MoonShineAuth::getGuard()->user())
            ? [
                MenuGroup::make('Payments', [
                    MenuItem::make(StudentPaymentHistoryResource::class, label: 'Student Payments', icon: 'banknotes'),
                    MenuItem::make(PaymentTypeResource::class, label: 'Payment Types', icon: 'tag'),
                ]),
            ]
            : [];
        $collegeMenu = config('school_portal.features.college_module')
            ? [
                MenuGroup::make('College Management', [
                    MenuItem::make(CollegeProgramResource::class, label: 'Course', icon: 'building-library'),
                    MenuItem::make(CollegeProgramCourseResource::class, label: 'Class', icon: 'academic-cap'),
                    MenuItem::make(CollegeCourseOfferingResource::class, label: 'Schedules', icon: 'rectangle-stack'),
                    MenuItem::make(CollegeEnrollmentCourseResource::class, label: 'Grades', icon: 'chart-bar'),
                    MenuItem::make(InstructorResource::class, label: 'Instructors / Professors', icon: 'user-group'),
                    MenuItem::make(CollegeEnrollmentResource::class, label: 'Student Enrollments', icon: 'user-plus'),
                ]),
            ]
            : [];

        return [
            MenuItem::make(Dashboard::class, 'Dashboard', 'home'),
            MenuItem::make(AnnouncementResource::class, label: 'Announcements', icon: 'megaphone'),
            MenuGroup::make('Attendance', [
                MenuItem::make(StudentAttendanceDashboard::class, label: 'Student Attendance', icon: 'users'),
                ...($staffAttendanceEnabled
                    ? [MenuItem::make(StaffAttendanceDashboard::class, label: 'Staff Attendance', icon: 'clock')]
                    : []),
            ]),

            MenuItem::make(SchoolYearResource::class, label: 'Academic/School Year', icon: 'calendar'),
            MenuGroup::make($staffEnabled ? 'Students & Staff' : 'Students & Teachers', [
                MenuItem::make(StudentResource::class, label: 'Students', icon: 'users'),
                MenuItem::make(ArchivedStudentResource::class, label: 'Archived Students', icon: 'archive-box'),
                MenuItem::make(StudentDocumentResource::class, label: 'Documents', icon: 'document-text'),
                MenuItem::make(AdviserResource::class, label: 'Teachers', icon: 'user'),
                ...($staffEnabled
                    ? [MenuItem::make(StaffResource::class, label: 'Staff', icon: 'identification')]
                    : []),
            ]),

            MenuGroup::make('Senior High Curriculum', [
                MenuItem::make(SubjectResource::class, label: 'Subjects', icon: 'book-open'),
                MenuItem::make(ClassesModelResource::class, label: 'Classes', icon: 'academic-cap'),
                MenuItem::make(GradeResource::class, label: 'Grades', icon: 'chart-bar'),
            ]),

            ...$collegeMenu,

            ...$paymentMenu,

            ...$quizMenu,

            ...$toolsMenu,

            MenuGroup::make('System', [
                MenuItem::make(SettingResource::class, label: 'Settings', icon: 'cog-6-tooth'),
                MenuItem::make(MoonShineUserResource::class),
                // MenuItem::make(MoonShineUserRoleResource::class),
            ]),

        ];

    }

    /**
     * @param  ColorManager  $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        $theme = config('school_portal.theme');

        $colorManager->bulkAssign([
            'primary' => $theme['primary'],
            'primary-text' => '#FFFFFF',
            'secondary' => $theme['secondary'],
            'secondary-text' => '#FFFFFF',
            'body' => $theme['background'],
            'base.text' => $theme['text'],
            'base.stroke' => $theme['primary'],
            'base.default' => $theme['surface'],
            'info' => $theme['accent'],
            'info-text' => '#FFFFFF',
            'warning' => $theme['accent'],
            'warning-text' => $theme['text'],
            'error' => $theme['alert'],
            'error-text' => '#FFFFFF',
        ]);
    }

    protected function getLogoComponent(): Logo
    {
        $logoUrl = SchoolBranding::logoUrl();

        return Logo::make(
            $this->getHomeUrl(),
            $logoUrl,
            $logoUrl
        )->darkMode(
            $logoUrl,
            $logoUrl
        );
    }

    protected function getProfileComponent(): Profile
    {
        return Profile::make()
            ->avatarPlaceholder(asset('images/generic-user.svg'))
            ->menu([
                ActionButton::make(
                    label: $this->getCore()->getTranslator()->get('moonshine::ui.profile'),
                    url: $this->getCore()->getRouter()->getEndpoints()->toPage(
                        $this->getCore()->getConfig()->getPage('profile', ProfilePage::class),
                    )
                )->icon('user'),
            ]);
    }

    protected function getFaviconComponent(): Favicon
    {
        return Favicon::make([
            'apple-touch' => '/favicons/apple-touch-icon.png',
            '32' => '/favicons/favicon-32x32.png',
            '16' => '/favicons/favicon-16x16.png',
        ]);
    }

    protected function getFooterMenu(): array
    {
        // Return an empty array to remove any footer links
        return [];
    }

    protected function getFooterCopyright(): string
    {
        return '';
    }
}
