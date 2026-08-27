<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Admin\StudentImportExportController;
use App\Models\Adviser;
use App\MoonShine\Layouts\CustomLayout;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\Adviser\Pages\AdviserDetailPage;
use App\MoonShine\Resources\Adviser\Pages\AdviserFormPage;
use App\MoonShine\Resources\Adviser\Pages\AdviserIndexPage;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUser\Pages\MoonShineUserFormPage;
use App\MoonShine\Resources\Student\Pages\StudentDetailPage;
use App\MoonShine\Resources\Student\Pages\StudentFormPage;
use App\MoonShine\Resources\Student\Pages\StudentIndexPage;
use App\MoonShine\Resources\Student\StudentResource;
use App\Support\RoleDefaultPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;
use MoonShine\Support\Enums\PageType;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class AdminResourceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_model_resources_redirect_to_their_index_after_save(): void
    {
        $resourceFiles = glob(app_path('MoonShine/Resources/*/*Resource.php')) ?: [];

        $this->assertNotEmpty($resourceFiles);

        foreach ($resourceFiles as $resourceFile) {
            $contents = file_get_contents($resourceFile);

            preg_match('/namespace\s+([^;]+);/', $contents, $namespace);
            preg_match('/class\s+(\w+Resource)\s+extends/', $contents, $class);

            $resourceClass = $namespace[1].'\\'.$class[1];

            $redirectAfterSave = new ReflectionProperty($resourceClass, 'redirectAfterSave');

            $this->assertSame(
                PageType::INDEX,
                $redirectAfterSave->getDefaultValue(),
                $resourceClass.' must redirect to its index after save.'
            );
        }
    }

    public function test_adviser_index_includes_username(): void
    {
        $columns = collect(app(AdviserResource::class)->indexFields())
            ->map(fn ($field) => $field->getColumn());

        $this->assertContains('user.username', $columns);
    }

    public function test_student_and_teacher_indexes_show_rfid_registration_action(): void
    {
        foreach ([StudentResource::class, AdviserResource::class] as $resourceClass) {
            $resource = app($resourceClass);
            $buttonIcons = $resource->getIndexPage()
                ?->getButtons()
                ->map(static fn ($button): string => $button->getIconValue())
                ->all();
            $indexRfidFields = collect($resource->indexFields())
                ->filter(fn ($field): bool => $field->getColumn() === 'rfid_card_uid');
            $formRfidFields = collect($resource->formFields())
                ->filter(fn ($field): bool => $field->getColumn() === 'rfid_card_uid');

            $this->assertGreaterThanOrEqual(
                2,
                count(array_filter($buttonIcons, static fn (string $icon): bool => $icon === 'credit-card')),
                $resourceClass,
            );
            $this->assertCount(1, $indexRfidFields, $resourceClass);
            $this->assertCount(0, $formRfidFields, $resourceClass);
        }
    }

    public function test_disabled_rfid_setting_hides_rfid_resource_controls(): void
    {
        config()->set('school.rfid_enabled', '0');

        foreach ([StudentResource::class, AdviserResource::class] as $resourceClass) {
            $resource = app($resourceClass);
            $buttonIcons = $resource->getIndexPage()
                ?->getButtons()
                ->map(static fn ($button): string => $button->getIconValue())
                ->all();
            $indexColumns = collect($resource->indexFields())
                ->map(static fn ($field): string => $field->getColumn())
                ->all();

            $this->assertNotContains('credit-card', $buttonIcons, $resourceClass);
            $this->assertNotContains('x-circle', $buttonIcons, $resourceClass);
            $this->assertNotContains('rfid_card_uid', $indexColumns, $resourceClass);
        }
    }

    public function test_existing_dotted_username_does_not_block_admin_account_updates(): void
    {
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'brandao.caballero',
            'email' => 'brandao.caballero@example.test',
            'password' => bcrypt('old-password'),
            'name' => 'Brandao Amoranto Caballero',
        ]);
        $user->save();

        $page = (new ReflectionClass(MoonShineUserFormPage::class))
            ->newInstanceWithoutConstructor();
        $rulesMethod = new ReflectionMethod($page, 'rules');
        $rules = $rulesMethod->invoke($page, new ModelDataWrapper($user));

        $validator = Validator::make(
            ['username' => $user->username],
            ['username' => $rules['username']],
        );

        $this->assertTrue($validator->passes(), $validator->errors()->first('username'));
    }

    public function test_creating_an_admin_account_derives_the_required_email_from_username(): void
    {
        config()->set('app.domain', 'example.test');

        request()->merge([
            'moonshine_user_role_id' => 1,
            'name' => 'Admin Two',
            'username' => 'admin2',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $resource = app(MoonShineUserResource::class);
        $resource->save($resource->getCaster()->cast(new MoonshineUser));

        $this->assertDatabaseHas('moonshine_users', [
            'name' => 'Admin Two',
            'username' => 'admin2',
            'email' => 'admin2@example.test',
        ]);
    }

    public function test_admin_badge_links_keep_white_text_in_every_link_state(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();
        $themeOverrides = new ReflectionMethod($layout, 'themeOverrides');
        $css = $themeOverrides->invoke($layout);

        $this->assertStringContainsString('a.badge:visited', $css);
        $this->assertStringContainsString('a.badge:hover', $css);
        $this->assertStringContainsString('color: #ffffff !important;', $css);
    }

    public function test_admin_confirmation_forms_fit_inside_modals(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();
        $themeOverrides = new ReflectionMethod($layout, 'themeOverrides');
        $css = $themeOverrides->invoke($layout);

        $this->assertStringContainsString('.modal-template .modal-body > form', $css);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $css);
        $this->assertStringNotContainsString(
            ".modal-template .modal-body {\n                display: flex;",
            $css,
        );
    }

    public function test_admin_grid_actions_are_moved_to_the_first_column(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();

        $scriptMethod = new ReflectionMethod($layout, 'leadingGridActionsScript');
        $script = $scriptMethod->invoke($layout);

        $themeOverrides = new ReflectionMethod($layout, 'themeOverrides');
        $css = $themeOverrides->invoke($layout);

        $this->assertStringContainsString('.js-table-builder-container table', $script);
        $this->assertStringContainsString('row.prepend(actionCell)', $script);
        $this->assertStringContainsString('firstCell.after(actionCell)', $script);
        $this->assertStringContainsString('.js-actions-all-checked', $script);
        $this->assertStringContainsString('new MutationObserver(scheduleArrange)', $script);
        $this->assertStringContainsString('[data-admin-actions]', $css);
        $this->assertStringContainsString('[data-admin-actions-after-selection].sticky-col', $css);
        $this->assertStringContainsString('justify-content: flex-start !important;', $css);
    }

    public function test_edits_opened_from_a_detail_page_return_to_that_detail_page_after_save(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();

        $scriptMethod = new ReflectionMethod($layout, 'returnToDetailAfterSaveScript');
        $script = $scriptMethod->invoke($layout);

        $this->assertStringContainsString('detail-page', $script);
        $this->assertStringContainsString("editUrl.searchParams.set('_redirect', returnUrl)", $script);
        $this->assertStringContainsString("redirect.name = '_redirect'", $script);
        $this->assertStringContainsString("['PUT', 'PATCH'].includes(method)", $script);
        $this->assertStringContainsString('returnUrl.origin !== window.location.origin', $script);
    }

    public function test_admin_sidebar_reveals_the_cat_easter_egg_after_seven_consecutive_clicks(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();

        $scriptMethod = new ReflectionMethod($layout, 'sidebarEasterEggScript');
        $script = $scriptMethod->invoke($layout);

        $themeOverrides = new ReflectionMethod($layout, 'themeOverrides');
        $css = $themeOverrides->invoke($layout);

        $source = file_get_contents((new ReflectionClass(CustomLayout::class))->getFileName());

        $this->assertStringContainsString('[data-sidebar-easter-egg-trigger]', $script);
        $this->assertStringContainsString('if (clickCount < 7)', $script);
        $this->assertStringContainsString('easterEgg.hidden = false', $script);
        $this->assertStringContainsString('easterEgg.hidden = true', $script);
        $this->assertStringContainsString('}, 7000)', $script);
        $this->assertStringContainsString("image.alt = 'Three dancing kittens'", $script);
        $this->assertStringContainsString("message.textContent = 'Happy Friday'", $script);
        $this->assertStringContainsString("parts.get('weekday') !== 'Fri'", $script);
        $this->assertStringContainsString("parts.get('hour') !== '17'", $script);
        $this->assertStringContainsString("parts.get('minute') !== '00'", $script);
        $this->assertStringContainsString('window.setInterval(checkFridayCelebration, 1000)', $script);
        $this->assertStringContainsString('width: 60%;', $css);
        $this->assertStringContainsString('margin: auto auto 0;', $css);
        $this->assertStringContainsString('.sidebar-easter-egg:not([hidden]) + .sidebar-datetime', $css);
        $this->assertStringContainsString("'data-src' => asset('images/admin-cats-easter-egg.webp')", $source);
        $this->assertStringContainsString("'data-timezone' => config('school_portal.timezone', 'Asia/Manila')", $source);
        $this->assertFileExists(public_path('images/admin-cats-easter-egg.webp'));
        $this->assertLessThan(500 * 1024, filesize(public_path('images/admin-cats-easter-egg.webp')));
    }

    public function test_admin_sidebar_easter_eggs_can_be_disabled_by_configuration(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();
        $enabledMethod = new ReflectionMethod($layout, 'easterEggsEnabled');
        $originalValue = config('school_portal.features.easter_eggs');

        try {
            config()->set('school_portal.features.easter_eggs', false);
            $this->assertFalse($enabledMethod->invoke($layout));

            config()->set('school_portal.features.easter_eggs', 'true');
            $this->assertTrue($enabledMethod->invoke($layout));
        } finally {
            config()->set('school_portal.features.easter_eggs', $originalValue);
        }

        $this->assertSame(
            'true',
            strtolower((string) env('EASTER_EGGS_ENABLED', 'true'))
        );
    }

    public function test_rfid_registration_modal_autofocuses_and_submits_after_scan(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();

        $scriptMethod = new ReflectionMethod($layout, 'rfidRegistrationScript');
        $script = $scriptMethod->invoke($layout);

        $this->assertStringContainsString('[data-rfid-register-trigger]', $script);
        $this->assertStringContainsString('[data-rfid-registration-input]', $script);
        $this->assertStringContainsString('input.focus({preventScroll: true})', $script);
        $this->assertStringContainsString("input.value = ''", $script);
        $this->assertStringNotContainsString('input.select()', $script);
        $this->assertStringContainsString('form.requestSubmit()', $script);
        $this->assertStringContainsString('window.setTimeout(() => submitRegistration(input), 650)', $script);
        $this->assertStringContainsString('[data-rfid-checker-input]', $script);
        $this->assertStringContainsString("window.addEventListener('toast'", $script);
        $this->assertStringContainsString("event.detail?.type !== 'error'", $script);
        $this->assertStringContainsString('resetRegistrationInput(input)', $script);
        $this->assertStringContainsString("input.dataset.rfidScannerTouched = ''", $script);
        $this->assertStringContainsString("input.value = ''", $script);
        $this->assertStringContainsString('input.focus({preventScroll: true})', $script);

        foreach ([StudentIndexPage::class, AdviserIndexPage::class] as $indexPageClass) {
            $source = file_get_contents((new ReflectionClass($indexPageClass))->getFileName());

            $this->assertStringContainsString("content: __('Tap RFID card on device and save.')", $source);
            $this->assertStringContainsString("button: ''", $source);
            $this->assertStringContainsString("'data-rfid-registration-form' => true", $source);
            $this->assertStringNotContainsString('Save RFID Card', $source);
        }
    }

    public function test_rfid_actions_use_consistent_theme_independent_styles(): void
    {
        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();
        $themeOverrides = new ReflectionMethod($layout, 'themeOverrides');
        $css = $themeOverrides->invoke($layout);

        $this->assertStringContainsString('.rfid-action-register', $css);
        $this->assertStringContainsString('.rfid-action-remove', $css);
        $this->assertStringContainsString('.rfid-action.rfid-action-remove::after', $css);
        $this->assertStringContainsString('content: "×";', $css);
        $this->assertStringContainsString('form[data-rfid-registration-form] button[type="submit"]', $css);
        $this->assertStringContainsString('width: 2.35rem !important;', $css);
        $this->assertStringContainsString('background: #dbeafe !important;', $css);
        $this->assertStringContainsString('background: #f1f5f9 !important;', $css);
        $this->assertStringContainsString('margin-inline: auto;', $css);
    }

    public function test_rfid_actions_update_grids_and_pages_without_full_reload(): void
    {
        foreach ([StudentFormPage::class, AdviserFormPage::class] as $formPageClass) {
            $source = file_get_contents((new ReflectionClass($formPageClass))->getFileName());

            $this->assertStringContainsString("ActionButton::make(__('Remove RFID'))", $source);
            $this->assertStringContainsString('removeRfidCard', $source);
            $this->assertStringContainsString("'data-rfid-page-remove-control' => true", $source);
            $this->assertStringContainsString("afterResponse: 'rfidCardRemoved'", $source);
            $this->assertStringNotContainsString('return redirect()->to(', $source);
        }

        foreach ([StudentDetailPage::class, AdviserDetailPage::class] as $detailPageClass) {
            $source = file_get_contents((new ReflectionClass($detailPageClass))->getFileName());

            $this->assertStringContainsString("ActionButton::make(__('Remove RFID'))", $source);
            $this->assertStringContainsString('removeRfidCard', $source);
            $this->assertStringContainsString("'data-rfid-page-remove-control' => true", $source);
            $this->assertStringContainsString("afterResponse: 'rfidCardRemoved'", $source);
            $this->assertStringNotContainsString('return redirect()->to(', $source);
        }

        foreach ([StudentIndexPage::class, AdviserIndexPage::class] as $indexPageClass) {
            $source = file_get_contents((new ReflectionClass($indexPageClass))->getFileName());

            $this->assertStringContainsString('registerRfidCard', $source);
            $this->assertStringContainsString('blank($', $source);
            $this->assertStringContainsString('?->rfid_card_uid)', $source);
            $this->assertStringContainsString("->icon('credit-card')", $source);
            $this->assertStringContainsString('events: [$this->getListEventName()]', $source);
            $this->assertStringNotContainsString('return redirect()->to(', $source);
        }

        $layout = (new ReflectionClass(CustomLayout::class))
            ->newInstanceWithoutConstructor();
        $scriptMethod = new ReflectionMethod($layout, 'rfidRegistrationScript');
        $script = $scriptMethod->invoke($layout);

        $this->assertStringContainsString("onCallback('rfidCardRemoved'", $script);
        $this->assertStringContainsString('[data-rfid-page-remove-control]', $script);
        $this->assertStringContainsString('[data-rfid-detail-field]', $script);
    }

    public function test_rfid_checker_is_grouped_under_tools(): void
    {
        $source = file_get_contents((new ReflectionClass(CustomLayout::class))->getFileName());
        $toolsPosition = strpos($source, "MenuGroup::make('Tools'");
        $checkerPosition = strpos($source, 'MenuItem::make(RfidChecker::class');

        $this->assertNotFalse($toolsPosition);
        $this->assertNotFalse($checkerPosition);
        $this->assertGreaterThan($toolsPosition, $checkerPosition);
    }

    public function test_user_password_reset_uses_the_default_for_each_role(): void
    {
        config()->set([
            'school.default_config_admin_password' => 'admin-default',
            'school.default_config_teacher_password' => 'teacher-default',
            'school.default_config_student_password' => 'student-default',
        ]);

        $defaultPassword = app(RoleDefaultPassword::class);
        $roles = [
            1 => ['name' => 'Admin', 'password' => 'admin-default', 'must_change' => false],
            2 => ['name' => 'Teacher', 'password' => 'teacher-default', 'must_change' => true],
            3 => ['name' => 'Student', 'password' => 'student-default', 'must_change' => true],
        ];

        foreach ($roles as $roleId => $expected) {
            MoonshineUserRole::query()->updateOrCreate(
                ['id' => $roleId],
                ['name' => $expected['name']],
            );

            $user = (new MoonshineUser)->forceFill([
                'moonshine_user_role_id' => $roleId,
                'username' => "role-{$roleId}",
                'email' => "role-{$roleId}@example.test",
                'password' => Hash::make('old-password'),
                'must_change_password' => false,
                'name' => "Role {$roleId}",
            ]);
            $user->save();

            $defaultPassword->reset($user);
            $user->refresh();

            $this->assertTrue(Hash::check($expected['password'], $user->password));
            $this->assertSame($expected['must_change'], (bool) $user->must_change_password);
        }
    }

    public function test_users_page_shows_the_reset_password_action(): void
    {
        $resource = app(MoonShineUserResource::class);
        $buttonLabels = $resource->getIndexPage()
            ?->getButtons()
            ->map(static fn ($button): string => $button->getLabel())
            ->all();

        $this->assertContains('Reset password', $buttonLabels);
    }

    public function test_adviser_export_contains_account_and_profile_details(): void
    {
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => 1,
            'username' => 'maria.santos',
            'email' => 'maria.santos@example.test',
            'password' => bcrypt('password'),
            'name' => 'Maria Santos',
        ]);
        $user->save();

        Adviser::withoutEvents(fn () => Adviser::query()->create([
            'user_id' => $user->id,
            'name' => 'Maria Santos',
            'rank' => 'Teacher III',
            'major' => 'Mathematics',
        ]));

        $response = app(StudentImportExportController::class)->exportAdvisers();

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('advisers-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Username,Name,Rank,Major', $csv);
        $this->assertStringContainsString('maria.santos,"Maria Santos","Teacher III",Mathematics', $csv);
    }

    public function test_staff_export_contains_staff_profile_and_shift_details(): void
    {
        Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Ana Reyes',
            'rank' => 'Registrar',
            'major' => 'Records Office',
            'staff_type' => Adviser::TYPE_STAFF,
            'rfid_card_uid' => 'STAFF-001',
            'shift_start_time' => '08:00:00',
            'shift_end_time' => '17:00:00',
        ]));
        Adviser::withoutEvents(fn () => Adviser::query()->create([
            'name' => 'Teacher Excluded',
            'rank' => 'Teacher I',
            'major' => 'English',
            'staff_type' => Adviser::TYPE_TEACHER,
        ]));

        $response = app(StudentImportExportController::class)->exportStaff();

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('staff-', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('"RFID Card UID",Name,"Position / Rank","Department / Office","Shift Start","Shift End"', $csv);
        $this->assertStringContainsString('STAFF-001,"Ana Reyes",Registrar,"Records Office",08:00:00,17:00:00', $csv);
        $this->assertStringNotContainsString('Teacher Excluded', $csv);

        $staffIndexPage = app(\App\MoonShine\Resources\Staff\StaffResource::class)->getIndexPage();
        $staffButtons = collect((new \ReflectionMethod($staffIndexPage, 'topLeftButtons'))
            ->invoke($staffIndexPage)
            ->toArray())
            ->map(static fn ($button): string => $button->getLabel())
            ->all();

        $this->assertContains('Export Staff', $staffButtons);
    }
}
