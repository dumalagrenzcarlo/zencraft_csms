<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Staff;

use App\Models\Adviser;
use App\Models\Staff;
use App\MoonShine\Fields\TimePicker;
use App\MoonShine\Resources\Adviser\AdviserResource;
use App\Support\TeacherStaffAttendance;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Text;

class StaffResource extends AdviserResource
{
    public string $model = Staff::class;

    public function uriKey(): string
    {
        return 'staff';
    }

    public function getTitle(): string
    {
        return 'Staff';
    }

    public function personnelType(): string
    {
        return Adviser::TYPE_STAFF;
    }

    public function personnelLabel(): string
    {
        return 'Staff';
    }

    public function indexFields(): array
    {
        return array_values(array_filter([
            ID::make(__('Id'), 'id'),
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('advisers')
                ->disk('public'),
            TeacherStaffAttendance::rfidEnabled()
                ? Text::make(__('RFID Card UID'), 'rfid_card_uid')
                : null,
            Text::make(__('Name'), 'name'),
            Text::make(__('Position / Rank'), 'rank'),
            Text::make(__('Department / Office'), 'major'),
            Text::make(__('Shift Start'), 'shift_start_time'),
            Text::make(__('Shift End'), 'shift_end_time'),
        ]));
    }

    public function formFields(): array
    {
        return [
            ID::make(__('Id'), 'id'),
            Text::make(__('Name'), 'name'),
            Text::make(__('Position / Rank'), 'rank'),
            Text::make(__('Department / Office'), 'major'),
            TimePicker::make(__('Shift Start Time'), 'shift_start_time'),
            TimePicker::make(__('Shift End Time'), 'shift_end_time'),
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('advisers')
                ->disk('public')
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'gif']),
        ];
    }

    public function detailsFields(): array
    {
        return array_values(array_filter([
            Image::make(__('Profile Photo'), 'profile_photo')
                ->dir('advisers')
                ->disk('public'),
            Text::make(__('Name'), 'name'),
            TeacherStaffAttendance::rfidEnabled()
                ? Text::make(__('RFID Card UID'), 'rfid_card_uid')
                    ->customWrapperAttributes(['data-rfid-detail-field' => true])
                : null,
            Text::make(__('Position / Rank'), 'rank'),
            Text::make(__('Department / Office'), 'major'),
            Text::make(__('Shift Start'), 'shift_start_time'),
            Text::make(__('Shift End'), 'shift_end_time'),
        ]));
    }
}
