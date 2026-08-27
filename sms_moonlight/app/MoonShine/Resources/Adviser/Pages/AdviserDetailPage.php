<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Adviser\Pages;

use App\Models\Adviser;
use App\Support\TeacherStaffAttendance;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\DTOs\AsyncCallback;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\FlexibleRender;
use Throwable;

class AdviserDetailPage extends DetailPage
{
    public function getTitle(): string
    {
        return __('Adviser Information');
    }

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->detailsFields(); // reuse dynamic fields
    }

    protected function buttons(): ListOf
    {
        $deleteButton = $this->modifyDeleteButton(
            $this->getResource()->getDeleteButton(
                redirectAfterDelete: $this->getResource()->getRedirectAfterDelete(),
                isAsync: false,
            )
        )
            ->setLabel(__('Delete Adviser'))
            ->customAttributes([
                'class' => 'adviser-detail-delete-button',
                'data-adviser-delete-control' => true,
            ]);

        $buttons = new ListOf(ActionButtonContract::class, [$deleteButton]);

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
                        name: 'teacher-detail-rfid-remove-'.$teacher->getKey(),
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
            ->where('staff_type', $this->getResource()->personnelType())
            ->findOrFail($request->getItemID());
        $teacher->update(['rfid_card_uid' => null]);

        toast(__('RFID card removed from :name.', [
            'name' => $teacher->name,
        ]), ToastType::SUCCESS);

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
        /** @var Adviser $teacher */
        $teacher = $this->getResource()->getItem();

        return [
            FlexibleRender::make(view('admin.advisers.detail', [
                'teacher' => $teacher,
                'backUrl' => $this->getResource()->getIndexPageUrl(),
                'editUrl' => $this->getResource()->getFormPageUrl($teacher->getKey()),
                'shiftStart' => $this->formatTime($teacher->shift_start_time),
                'shiftEnd' => $this->formatTime($teacher->shift_end_time),
                'showRfid' => TeacherStaffAttendance::rfidEnabled(),
            ])),
            ActionGroup::make($this->buttons()->toArray())
                ->fill($this->getResource()->getCastedData())
                ->class('adviser-detail-danger-actions'),
        ];
    }

    private function formatTime(?string $time): string
    {
        if (blank($time)) {
            return __('Not set');
        }

        $timestamp = strtotime($time);

        return $timestamp === false ? $time : date('g:i A', $timestamp);
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
