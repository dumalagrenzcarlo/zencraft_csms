<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Student\Pages;

use App\Models\Setting;
use App\Models\Student;
use App\MoonShine\Resources\Student\StudentResource;
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
 * @extends FormPage<StudentResource>
 */
class StudentFormPage extends FormPage
{
    protected bool $isAsync = false;

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $formFields = $this->getResource()->formFields();
        $extraInformationFields = $this->getResource()->extraInformationFields();

        if ($this->getResource()->isItemExists()) {
            foreach ($formFields as $field) {
                if (method_exists($field, 'getColumn') && $field->getColumn() === 'lrn') {
                    $field->readonly();
                }
            }
        }

        $half = (int) ceil(count($formFields) / 2);

        return [
            Box::make([
                Grid::make([
                    Column::make(
                        array_slice($formFields, 0, $half)
                    )->columnSpan(6),

                    Column::make(
                        array_slice($formFields, $half)
                    )->columnSpan(6),
                ]),
            ]),
            Box::make(__('Extra Information'), [
                Grid::make([
                    Column::make($extraInformationFields)->columnSpan(12),
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

        /** @var Student|null $student */
        $student = $this->getResource()->getItem();

        if (
            Setting::enabled('rfid_enabled', true)
            && $student
            && filled($student->rfid_card_uid)
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
                        ['resourceItem' => $student->getKey()],
                        callback: AsyncCallback::with(afterResponse: 'rfidCardRemoved'),
                    )
                    ->withConfirm(
                        title: __('Remove RFID Card'),
                        content: __('Remove the RFID card assigned to this student?'),
                        button: __('Remove RFID Card'),
                        name: 'student-edit-rfid-remove-'.$student->getKey(),
                    )
            );
        }

        return $buttons;
    }

    #[AsyncMethod]
    public function removeRfidCard(CrudRequestContract $request): void
    {
        if (! Setting::enabled('rfid_enabled', true)) {
            throw ValidationException::withMessages([
                'rfid_card_uid' => __('RFID features are disabled in Settings.'),
            ]);
        }

        $student = Student::query()->findOrFail($request->getItemID());
        $student->update(['rfid_card_uid' => null]);

        toast(__('RFID card removed from :name.', [
            'name' => trim($student->firstname.' '.$student->lastname),
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
