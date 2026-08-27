<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Student\Pages;

use App\Models\Setting;
use App\Models\Student;
use App\MoonShine\Resources\Student\StudentResource;
use App\Services\StudentArchiver;
use App\Support\RfidCardUid;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Text;
use Throwable;

/**
 * @extends IndexPage<StudentResource>
 */
class StudentIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    protected function buttons(): ListOf
    {
        $buttons = parent::buttons()
            ->add(
                ActionButton::make('')
                    ->icon('archive-box')
                    ->customAttributes([
                        'title' => __('Archive student'),
                        'aria-label' => __('Archive student'),
                    ])
                    ->method('archiveStudent', events: [$this->getListEventName()])
                    ->withConfirm(
                        title: __('Archive Student'),
                        content: static fn (Student $student): string => __('Archive :name and disable their portal access?', [
                            'name' => trim($student->firstname.' '.$student->lastname),
                        ]),
                        button: __('Archive Student'),
                        name: static fn (Student $student): string => 'archive-student-'.$student->getKey(),
                    )
            );

        if (! Setting::enabled('rfid_enabled', true)) {
            return $buttons;
        }

        return $buttons
            ->add(
                ActionButton::make('')
                    ->icon('credit-card')
                    ->customAttributes([
                        'class' => 'rfid-action rfid-action-register',
                        'title' => __('Register RFID card'),
                        'aria-label' => __('Register RFID card'),
                        'data-rfid-register-trigger' => true,
                    ])
                    ->canSee(static fn (?Student $student): bool => blank($student?->rfid_card_uid))
                    ->method(
                        'registerRfidCard',
                        events: [$this->getListEventName()],
                    )
                    ->withConfirm(
                        title: static fn (Student $student): string => __('Register RFID Card — :name', [
                            'name' => trim($student->firstname.' '.$student->lastname),
                        ]),
                        content: __('Tap RFID card on device and save.'),
                        button: '',
                        fields: static fn (Student $student): array => [
                            Text::make(__('RFID Card UID'), 'rfid_card_uid')
                                ->required()
                                ->customAttributes([
                                    'autofocus' => true,
                                    'autocomplete' => 'off',
                                    'inputmode' => 'text',
                                    'data-rfid-registration-input' => true,
                                ]),
                        ],
                        formBuilder: static fn (FormBuilderContract $form): FormBuilderContract => $form
                            ->customAttributes(['data-rfid-registration-form' => true]),
                        name: static fn (Student $student): string => 'student-rfid-'.$student->getKey(),
                        modalBuilder: static fn (Modal $modal): Modal => $modal->closeOutside(false),
                    )
            )
            ->add(
                ActionButton::make('')
                    ->icon('credit-card')
                    ->customAttributes([
                        'class' => 'rfid-action rfid-action-remove',
                        'title' => __('Remove RFID card'),
                        'aria-label' => __('Remove RFID card'),
                    ])
                    ->canSee(static fn (?Student $student): bool => filled($student?->rfid_card_uid))
                    ->method(
                        'removeRfidCard',
                        events: [$this->getListEventName()],
                    )
                    ->withConfirm(
                        title: __('Remove RFID Card'),
                        content: static fn (Student $student): string => __('Remove the RFID card assigned to :name?', [
                            'name' => trim($student->firstname.' '.$student->lastname),
                        ]),
                        button: __('Remove RFID Card'),
                        name: static fn (Student $student): string => 'student-rfid-remove-'.$student->getKey(),
                    )
            );
    }

    #[AsyncMethod]
    public function archiveStudent(CrudRequestContract $request): void
    {
        $student = Student::query()->active()->findOrFail($request->getItemID());
        app(StudentArchiver::class)->archive($student);

        toast(__('Student archived and portal access disabled.'), ToastType::SUCCESS);
    }

    #[AsyncMethod]
    public function registerRfidCard(CrudRequestContract $request): void
    {
        $this->ensureRfidMigrationIsApplied();

        $validator = Validator::make(
            ['rfid_card_uid' => $request->input('rfid_card_uid')],
            ['rfid_card_uid' => ['required', 'string', 'max:100']],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $student = Student::query()->findOrFail($request->getItemID());
        $student->update([
            'rfid_card_uid' => RfidCardUid::normalize($validator->validated()['rfid_card_uid']),
        ]);

        toast(__('RFID card registered for :name.', [
            'name' => trim($student->firstname.' '.$student->lastname),
        ]), ToastType::SUCCESS);

    }

    #[AsyncMethod]
    public function removeRfidCard(CrudRequestContract $request): void
    {
        $this->ensureRfidMigrationIsApplied();

        $student = Student::query()->findOrFail($request->getItemID());
        $student->update(['rfid_card_uid' => null]);

        toast(__('RFID card removed from :name.', [
            'name' => trim($student->firstname.' '.$student->lastname),
        ]), ToastType::SUCCESS);

    }

    private function ensureRfidMigrationIsApplied(): void
    {
        if (! Setting::enabled('rfid_enabled', true)) {
            throw ValidationException::withMessages([
                'rfid_card_uid' => __('RFID features are disabled in Settings.'),
            ]);
        }

        if (! Schema::hasColumn('students', 'rfid_card_uid')) {
            throw ValidationException::withMessages([
                'rfid_card_uid' => __('RFID support is not installed yet. Run php artisan migrate and try again.'),
            ]);
        }
    }

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->indexFields(); // reuse dynamic fields
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function topLeftButtons(): ListOf
    {
        $buttons = parent::topLeftButtons()
            ->add(
                ActionButton::make('Import')
                    ->icon('arrow-up-tray')
                    ->primary()
                    ->inModal(
                        'Import Instructions',
                        view('admin.students.import-modal')->render()
                    )
            )
            ->add(
                ActionButton::make('Export')
                    ->icon('arrow-down-tray')
                    ->setUrl(route('admin.students.export'))
            );

        if (\App\Models\Setting::enabled('qr_code_enabled', true)) {
            $buttons->add(
                ActionButton::make('Export QR Codes')
                    ->icon('qr-code')
                    ->setUrl(route('admin.students.export-qr'))
            );
        }

        return $buttons;
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
