<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Student\Pages;

use App\Models\ClassStudent;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\DTOs\AsyncCallback;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;

class StudentDetailPage extends DetailPage
{
    protected function buttons(): ListOf
    {
        $buttons = parent::buttons();

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
                        name: 'student-detail-rfid-remove-'.$student->getKey(),
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

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return $this->getResource()->detailsFields();
    }

    /**
     * @return list<ComponentContract>
     */
    protected function mainLayer(): array
    {
        return [
            Box::make([
                ...$this->getTopButtons(),
                LineBreak::make(),
                $this->getDetailComponent(),
            ]),
        ];
    }

    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component->class('student-detail-two-column');
    }

    /**
     * @return list<ComponentContract>
     */
    protected function bottomLayer(): array
    {
        $components = parent::bottomLayer();
        $student = $this->getResource()->getItem();
        $classStudents = $student?->classStudents()
            ->with(['class.grade', 'schoolYear'])
            ->orderByDesc('school_year_id')
            ->orderByDesc('id')
            ->get() ?? collect();

        if ($classStudents->isEmpty()) {
            return $components;
        }

        $tabs = $classStudents
            ->values()
            ->map(function (ClassStudent $classStudent, int $index) use ($student): Tab {
                $label = $this->classHistoryLabel($classStudent);

                return Tab::make($label, [
                    FlexibleRender::make(
                        '<p class="mb-4 text-sm text-gray-500">Download the grades recorded for '.e($label).'.</p>'
                    ),
                    ActionButton::make(
                        'Download this class',
                        route('admin.students.download-grades', [
                            'student' => $student,
                            'class_student_id' => $classStudent->id,
                        ])
                    )->icon('arrow-down-tray')->primary(),
                ])
                    ->setId('class-grade-download-'.$classStudent->id)
                    ->active($index === 0);
            })
            ->all();

        $gradeDownloads = Box::make('Grade Downloads', [
            FlexibleRender::make(
                '<p class="mb-4 text-sm text-gray-500">Download every class in one PDF or choose a class-history tab below.</p>'
            ),
            ActionButton::make(
                'Download all class grades',
                route('admin.students.download-grades', ['student' => $student])
            )->icon('arrow-down-tray')->primary(),
            LineBreak::make(),
            Tabs::make($tabs),
        ]);

        return [
            LineBreak::make(),
            $gradeDownloads,
            ...$components,
        ];
    }

    private function classHistoryLabel(ClassStudent $classStudent): string
    {
        $schoolYear = $classStudent->schoolYear?->school_year ?? 'No school year';
        $grade = $classStudent->class?->grade?->grade ?? 'Grade';
        $section = $classStudent->class?->section ?? 'No section';

        return $schoolYear.' - '.$grade.' '.$section;
    }
}
