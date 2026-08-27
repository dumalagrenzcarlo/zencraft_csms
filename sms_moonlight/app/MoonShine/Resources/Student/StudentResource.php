<?php

namespace App\MoonShine\Resources\Student;

use App\Models\Setting;
use App\Models\Student;
use App\MoonShine\Resources\Student\Pages\StudentDetailPage;
use App\MoonShine\Resources\Student\Pages\StudentFormPage;
use App\MoonShine\Resources\Student\Pages\StudentIndexPage;
use App\Support\PaymentAccess;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class StudentResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Student::class;

    protected string $column = 'lrn';

    public function getTitle(): string
    {
        return 'Students';
    }

    protected function pages(): array
    {
        return [
            StudentIndexPage::class,
            StudentFormPage::class,
            StudentDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'lrn',
            ...(Setting::enabled('rfid_enabled', true) ? ['rfid_card_uid'] : []),
            'firstname',
            'lastname',
            'middlename',
            'gender',

        ];
    }

    protected function filters(): iterable
    {
        return array_values(array_filter([
            Text::make('Student Number', 'lrn'),
            Setting::enabled('rfid_enabled', true)
                ? Text::make('RFID Card UID', 'rfid_card_uid')
                : null,
            Text::make('Firstname', 'firstname'),
            Text::make('Lastname', 'lastname'),
            Text::make('Middlename', 'middlename'),
            Text::make('Address', 'address'),
            Select::make(__('Gender'), 'gender')
                ->options([
                    '' => 'All',
                    'male' => 'Male',
                    'female' => 'Female',
                ]),
            Text::make(__('Parent or Guardian'), 'parent_guardian'),
            Checkbox::make(__('Is 4Ps Member'), 'is_4ps_member'),

        ]));
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->active();
    }

    protected function modifyItemQueryBuilder(Builder $builder): Builder
    {
        return $this->modifyQueryBuilder($builder);
    }

    public function indexFields(): array
    {
        return array_values(array_filter([
            ID::make(__('Id'), 'id'),
            // BelongsTo::make(__('User Id'), 'user', resource: \App\MoonShine\Resources\MoonShineUser\MoonShineUserResource::class),
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('students')
                ->disk('public'),
            Text::make(__('Lrn'), 'lrn'),
            Setting::enabled('rfid_enabled', true)
                ? Text::make(__('RFID Card UID'), 'rfid_card_uid')
                : null,
            Text::make(__('Firstname'), 'firstname'),
            Text::make(__('Lastname'), 'lastname'),
            Text::make(__('Middlename'), 'middlename'),
            Select::make(__('Gender'), 'gender')
                ->options([
                    '' => 'All',
                    'male' => 'Male',
                    'female' => 'Female',
                ]),
            Text::make(__('Address'), 'address'),
            Text::make(__('Birthdate'), 'dob'),
            Text::make(__('Birthplace'), 'birthplace'),
            // Text::make(__('Parent or Guardian'), 'parent_guardian'),
            // Text::make(__('Parent or Guardian Address'), 'parent_guardian_address'),
            // Text::make(__('Parent or Guardian Relationship'), 'parent_guardian_relationship'),
            // Text::make(__('Weight'), 'weight'),
            // Text::make(__('Height'), 'height'),
            Checkbox::make(__('Is 4Ps Member'), 'is_4ps_member'),
            // Text::make(__('Elementary School Name'), 'elementary_school_name'),
            // Number::make(__('Elementary School Id'), 'elementary_school_id'),
            // Text::make(__('Elementary School Address'), 'elementary_school_address'),
            // Text::make(__('Elementary School Grade'), 'elementary_school_grade'),
            // Text::make(__('Elementary School Citation'), 'elementary_school_citation'),
            // Checkbox::make(__('Deworming Grade 7'), 'deworming_grade_7'),
            // Checkbox::make(__('Deworming Grade 8'), 'deworming_grade_8'),
            // Checkbox::make(__('Deworming Grade 9'), 'deworming_grade_9'),
            // Checkbox::make(__('Deworming Grade 10'), 'deworming_grade_10'),
        ]));
    }

    public function formFields(): array
    {
        $schoolPrefix = $this->useJhsFields() ? 'JHS School' : 'Elementary School';

        $fields = [
            ID::make(__('Id'), 'id'),
            Hidden::make(__('User Id'), 'user_id'),
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('students')
                ->disk('public')
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'gif']),
            Text::make(__('Lrn'), 'lrn'),
            Text::make(__('Firstname'), 'firstname'),
            Text::make(__('Lastname'), 'lastname'),
            Text::make(__('Middlename'), 'middlename'),
            Select::make(__('Gender'), 'gender')
                ->options([
                    'male' => 'Male',
                    'female' => 'Female',
                ]),
            Date::make(__('Dob'), 'dob'),
            Text::make(__('Address'), 'address'),
            Text::make(__('Birthplace'), 'birthplace'),

            Text::make(__('Parent Guardian'), 'parent_guardian'),
            Text::make(__('Parent Guardian Address'), 'parent_guardian_address'),
            Text::make(__('Parent Guardian Relationship'), 'parent_guardian_relationship'),
            Checkbox::make(__('Is 4Ps Member'), 'is_4ps_member'),
            Text::make(__('Weight'), 'weight'),
            Text::make(__('Height'), 'height'),
            ...($this->showPreviousSchoolFields()
                ? $this->previousSchoolFields($schoolPrefix)
                : []),
        ];

        if (! $this->useJhsFields() && $this->elementaryFieldsEnabled()) {
            $fields = [
                ...$fields,
                Checkbox::make(__('Deworming Grade 7'), 'deworming_grade_7'),
                Checkbox::make(__('Deworming Grade 8'), 'deworming_grade_8'),
                Checkbox::make(__('Deworming Grade 9'), 'deworming_grade_9'),
                Checkbox::make(__('Deworming Grade 10'), 'deworming_grade_10'),
            ];
        }

        return $fields;
    }

    public function extraInformationFields(): array
    {
        $fields = array_values(array_filter([
            Setting::enabled('tshirt_size_enabled', true)
                ? Select::make(__('T-Shirt Size'), 'tshirt_size')
                    ->options($this->tshirtSizeOptions())
                    ->nullable()
                : null,
            HasMany::make(__('Class Enrollments'), 'classStudents', resource: \App\MoonShine\Resources\ClassStudent\ClassStudentResource::class)
                ->tabMode(),
            HasMany::make(__('Class History'), 'studentClasses', resource: \App\MoonShine\Resources\StudentClass\StudentClassResource::class)
                ->tabMode(),
            HasMany::make(__('Subject Grades'), 'classStudentGrades', resource: \App\MoonShine\Resources\ClassStudentGrade\ClassStudentGradeResource::class)
                ->tabMode(),
            HasMany::make(__('Documents'), 'documents', resource: \App\MoonShine\Resources\StudentDocument\StudentDocumentResource::class)
                ->creatable()
                ->tabMode(),
        ]));

        if (
            config('school_portal.features.payments_module')
            && PaymentAccess::canAccess(request(), MoonShineAuth::getGuard()->user())
        ) {
            $fields[] = HasMany::make(__('Payment History'), 'paymentHistories', resource: \App\MoonShine\Resources\StudentPaymentHistory\StudentPaymentHistoryResource::class)
                ->creatable()
                ->tabMode();
        }

        if (config('school_portal.features.quiz_module')) {
            $fields[] = HasMany::make(__('Quiz Answers'), 'studentQuizAnswers', resource: \App\MoonShine\Resources\StudentQuizAnswer\StudentQuizAnswerResource::class)
                ->tabMode();
        }

        return $fields;
    }

    public function detailsFields(): array
    {
        $schoolPrefix = $this->useJhsFields() ? 'JHS School' : 'Elementary School';

        $fields = [
            ID::make(__('Id'), 'id'),
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('students')
                ->disk('public'),
            BelongsTo::make(__('User'), 'user', resource: \App\MoonShine\Resources\MoonShineUser\MoonShineUserResource::class),
            Text::make(__('Lrn'), 'lrn'),
            ...(
                Setting::enabled('rfid_enabled', true)
                    ? [
                        Text::make(__('RFID Card UID'), 'rfid_card_uid')
                            ->customWrapperAttributes(['data-rfid-detail-field' => true]),
                    ]
                    : []
            ),
            Text::make(__('Firstname'), 'firstname'),
            Text::make(__('Lastname'), 'lastname'),
            Text::make(__('Middlename'), 'middlename'),
            Text::make(__('Gender'), 'gender'),
            Text::make(__('Dob'), 'dob'),
            Text::make(__('Address'), 'address'),
            Text::make(__('Birthplace'), 'birthplace'),
            Text::make(__('Parent Guardian'), 'parent_guardian'),
            Text::make(__('Parent Guardian Address'), 'parent_guardian_address'),
            Text::make(__('Parent Guardian Relationship'), 'parent_guardian_relationship'),
            Checkbox::make(__('Is 4Ps Member'), 'is_4ps_member'),
            Text::make(__('Weight'), 'weight'),
            Text::make(__('Height'), 'height'),
            ...($this->showPreviousSchoolFields()
                ? $this->previousSchoolFields($schoolPrefix)
                : []),
        ];

        if (! $this->useJhsFields() && $this->elementaryFieldsEnabled()) {
            $fields = [
                ...$fields,
                Checkbox::make(__('Deworming Grade 7'), 'deworming_grade_7'),
                Checkbox::make(__('Deworming Grade 8'), 'deworming_grade_8'),
                Checkbox::make(__('Deworming Grade 9'), 'deworming_grade_9'),
                Checkbox::make(__('Deworming Grade 10'), 'deworming_grade_10'),
            ];
        }

        return [
            ...$fields,
            ...$this->extraInformationFields(),
        ];
    }

    private function useJhsFields(): bool
    {
        return (string) config('school.use_jhs_fields', '0') === '1';
    }

    private function showPreviousSchoolFields(): bool
    {
        return $this->useJhsFields()
            || $this->elementaryFieldsEnabled();
    }

    private function elementaryFieldsEnabled(): bool
    {
        return Setting::enabled('elementary_fields_enabled', true);
    }

    /**
     * @return array<int, Text|Number>
     */
    private function previousSchoolFields(string $schoolPrefix): array
    {
        return [
            Text::make(__($schoolPrefix.' Name'), 'elementary_school_name'),
            Number::make(__($schoolPrefix.' Id'), 'elementary_school_id'),
            Text::make(__($schoolPrefix.' Address'), 'elementary_school_address'),
            Text::make(__($schoolPrefix.' Grade'), 'elementary_school_grade'),
            Text::make(__($schoolPrefix.' Citation'), 'elementary_school_citation'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tshirtSizeOptions(): array
    {
        return [
            '' => 'Select Size',
            'XS' => 'XS',
            'S' => 'S',
            'M' => 'M',
            'L' => 'L',
            'XL' => 'XL',
            '2XL' => '2XL',
            '3XL' => '3XL',
            '4XL' => '4XL',
        ];
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        /** @var Student $student */
        $student = $item->getOriginal()->refresh();

        session()->flash('admin_created_student_credentials', [
            'title' => 'Student Credentials',
            'name' => trim(($student->firstname ?? '').' '.($student->lastname ?? '')),
            'username' => (string) $student->lrn,
            'password' => (string) config('school.default_config_student_password', 'student123'),
        ]);

        return $item;
    }
}
