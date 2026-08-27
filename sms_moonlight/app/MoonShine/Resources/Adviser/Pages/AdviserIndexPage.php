<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Adviser\Pages;

use App\Models\Adviser;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\Support\RfidCardUid;
use App\Support\TeacherStaffAttendance;
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
 * @extends IndexPage<AdviserResource>
 */
class AdviserIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    protected function buttons(): ListOf
    {
        $buttons = parent::buttons();

        if (! TeacherStaffAttendance::rfidEnabled()) {
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
                    ->canSee(static fn (?Adviser $teacher): bool => blank($teacher?->rfid_card_uid))
                    ->method(
                        'registerRfidCard',
                        events: [$this->getListEventName()],
                    )
                    ->withConfirm(
                        title: static fn (Adviser $teacher): string => __('Register RFID Card — :name', [
                            'name' => $teacher->name,
                        ]),
                        content: __('Tap RFID card on device and save.'),
                        button: '',
                        fields: static fn (Adviser $teacher): array => [
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
                        name: static fn (Adviser $teacher): string => 'teacher-rfid-'.$teacher->getKey(),
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
                    ->canSee(static fn (?Adviser $teacher): bool => filled($teacher?->rfid_card_uid))
                    ->method(
                        'removeRfidCard',
                        events: [$this->getListEventName()],
                    )
                    ->withConfirm(
                        title: __('Remove RFID Card'),
                        content: static fn (Adviser $teacher): string => __('Remove the RFID card assigned to :name?', [
                            'name' => $teacher->name,
                        ]),
                        button: __('Remove RFID Card'),
                        name: static fn (Adviser $teacher): string => 'teacher-rfid-remove-'.$teacher->getKey(),
                    )
            );
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

        $teacher = Adviser::query()
            ->visibleAsPersonnelType($this->getResource()->personnelType())
            ->findOrFail($request->getItemID());
        $teacher->update([
            'rfid_card_uid' => RfidCardUid::normalize($validator->validated()['rfid_card_uid']),
        ]);

        toast(__('RFID card registered for :name.', [
            'name' => $teacher->name,
        ]), ToastType::SUCCESS);

    }

    #[AsyncMethod]
    public function removeRfidCard(CrudRequestContract $request): void
    {
        $this->ensureRfidMigrationIsApplied();

        $teacher = Adviser::query()
            ->visibleAsPersonnelType($this->getResource()->personnelType())
            ->findOrFail($request->getItemID());
        $teacher->update(['rfid_card_uid' => null]);

        toast(__('RFID card removed from :name.', [
            'name' => $teacher->name,
        ]), ToastType::SUCCESS);

    }

    private function ensureRfidMigrationIsApplied(): void
    {
        if (! TeacherStaffAttendance::rfidEnabled()) {
            throw ValidationException::withMessages([
                'rfid_card_uid' => __('Teacher and staff attendance or RFID is disabled.'),
            ]);
        }

        if (! Schema::hasColumn('advisers', 'rfid_card_uid')) {
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

    protected function topLeftButtons(): ListOf
    {
        $buttons = parent::topLeftButtons();

        if ($this->getResource()->personnelType() === Adviser::TYPE_TEACHER) {
            $buttons->add(
                ActionButton::make(__('Export Advisers'))
                    ->icon('arrow-down-tray')
                    ->setUrl(route('admin.advisers.export'))
            );
        } elseif ($this->getResource()->personnelType() === Adviser::TYPE_STAFF) {
            $buttons->add(
                ActionButton::make(__('Export Staff'))
                    ->icon('arrow-down-tray')
                    ->setUrl(route('admin.staff.export'))
            );
        }

        return $buttons;
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
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
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
