<?php

namespace App\MoonShine\Resources\Adviser;

use App\Models\Adviser;
use App\MoonShine\Fields\TimePicker;
use App\MoonShine\Resources\Adviser\Pages\AdviserDetailPage;
use App\MoonShine\Resources\Adviser\Pages\AdviserFormPage;
use App\MoonShine\Resources\Adviser\Pages\AdviserIndexPage;
use App\Support\TeacherStaffAttendance;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Text;

/**
 * Auto-generated MoonShine resource
 */
class AdviserResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = Adviser::class;

    protected string $column = 'name';

    protected array $with = ['user'];

    public function uriKey(): string
    {
        return 'advisers';
    }

    public function getTitle(): string
    {
        return 'Teachers';
    }

    public function personnelType(): string
    {
        return Adviser::TYPE_TEACHER;
    }

    public function personnelLabel(): string
    {
        return 'Teacher';
    }

    protected function pages(): array
    {
        return [
            AdviserIndexPage::class,
            AdviserFormPage::class,
            AdviserDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            ...(TeacherStaffAttendance::rfidEnabled() ? ['rfid_card_uid'] : []),
            'rank',
            'major',
        ];
    }

    protected function filters(): iterable
    {
        return array_values(array_filter([
            Text::make('Name', 'name'),
            TeacherStaffAttendance::rfidEnabled()
                ? Text::make('RFID Card UID', 'rfid_card_uid')
                : null,
            Text::make('Rank', 'rank'),
            Text::make('Major', 'major'),
        ]));
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->visibleAsPersonnelType($this->personnelType());
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
                ->dir('advisers')
                ->disk('public'),
            Text::make(__('Username'), 'user.username'),
            TeacherStaffAttendance::rfidEnabled()
                ? Text::make(__('RFID Card UID'), 'rfid_card_uid')
                : null,
            Text::make(__('Name'), 'name'),
            Text::make(__('Rank'), 'rank'),
            Text::make(__('Major'), 'major'),
            $this->personnelType() === Adviser::TYPE_TEACHER
                ? Checkbox::make(__('College Instructor'), 'is_college_instructor')
                : null,
            Text::make(__('Shift Start'), 'shift_start_time'),
            Text::make(__('Shift End'), 'shift_end_time'),
        ]));
    }

    public function formFields(): array
    {
        return array_values(array_filter([
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name'),
            Text::make(__('Rank'), 'rank'),
            Text::make(__('Major'), 'major'),
            $this->personnelType() === Adviser::TYPE_TEACHER
                ? Checkbox::make(__('Appear in College Instructors'), 'is_college_instructor')
                    ->hint(__('Use this teacher account for both High School adviser and College instructor assignments.'))
                : null,
            TimePicker::make(__('Shift Start Time'), 'shift_start_time'),
            TimePicker::make(__('Shift End Time'), 'shift_end_time'),
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('advisers')
                ->disk('public')
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'gif']),
        ]));
    }

    public function detailsFields(): array
    {
        return array_values(array_filter([
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('advisers')
                ->disk('public'),
            Text::make('Name', 'name'),
            TeacherStaffAttendance::rfidEnabled()
                ? Text::make(__('RFID Card UID'), 'rfid_card_uid')
                    ->customWrapperAttributes(['data-rfid-detail-field' => true])
                : null,
            Text::make('Rank', 'rank'),
            Text::make('Major', 'major'),
            $this->personnelType() === Adviser::TYPE_TEACHER
                ? Checkbox::make(__('College Instructor'), 'is_college_instructor')
                : null,
            Text::make(__('Shift Start'), 'shift_start_time'),
            Text::make(__('Shift End'), 'shift_end_time'),
        ]));
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        /** @var Adviser $adviser */
        $adviser = $item->getOriginal()->refresh();

        if ($adviser->staff_type === Adviser::TYPE_STAFF) {
            return $item;
        }

        $adviser->load('user');

        session()->flash('admin_created_adviser_credentials', [
            'title' => 'Teacher Credentials',
            'name' => (string) $adviser->name,
            'username' => (string) ($adviser->user?->username ?? ''),
            'password' => (string) config('school.default_config_teacher_password', 'teacher123'),
        ]);

        return $item;
    }
}
