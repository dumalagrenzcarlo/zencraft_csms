<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Adviser\Pages;

use App\Models\Adviser;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\Support\TeacherStaffAttendance;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\DTOs\AsyncCallback;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;

/**
 * @extends FormPage<AdviserResource>
 */
class AdviserFormPage extends FormPage
{
    protected bool $isAsync = false;

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $formFields = $this->getResource()->formFields();
        $half = (int) ceil(count($formFields) / 2);

        return [
            Box::make([
                Grid::make([
                    Column::make(array_slice($formFields, 0, $half)),
                    Column::make(array_slice($formFields, $half)),
                ]),
            ]),
        ];
    }

    protected function formButtons(): ListOf
    {
        $buttons = parent::formButtons();

        $buttons->add(
            ActionButton::make(__('Back'), $this->getResource()->getIndexPageUrl())
        );

        /** @var Adviser|null $teacher */
        $teacher = $this->getResource()->getItem();

        if (
            TeacherStaffAttendance::rfidEnabled()
            && $teacher
            && filled($teacher->rfid_card_uid)
        ) {
            $buttons->add(
                ActionButton::make(__('Remove RFID'))
                    ->icon('x-circle')
                    ->customAttributes([
                        'class' => 'rfid-action-remove',
                        'data-rfid-page-remove-control' => true,
                    ])
                    ->method(
                        'removeRfidCard',
                        ['resourceItem' => $teacher->getKey()],
                        callback: AsyncCallback::with(afterResponse: 'rfidCardRemoved'),
                    )
                    ->withConfirm(
                        title: __('Remove RFID Card'),
                        content: __('Remove the RFID card assigned to this teacher?'),
                        button: __('Remove RFID Card'),
                        name: 'teacher-edit-rfid-remove-'.$teacher->getKey(),
                    )
            );
        }

        return $buttons;
    }

    #[AsyncMethod]
    public function removeRfidCard(CrudRequestContract $request): void
    {
        if (! TeacherStaffAttendance::rfidEnabled()) {
            throw ValidationException::withMessages([
                'rfid_card_uid' => __('RFID features are disabled in Settings.'),
            ]);
        }

        $teacher = Adviser::query()
            ->visibleAsPersonnelType($this->getResource()->personnelType())
            ->findOrFail($request->getItemID());
        $teacher->update(['rfid_card_uid' => null]);

        toast(__('RFID card removed from :name.', [
            'name' => $teacher->name,
        ]), ToastType::SUCCESS);

    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
    {
        return $component;
    }
}
