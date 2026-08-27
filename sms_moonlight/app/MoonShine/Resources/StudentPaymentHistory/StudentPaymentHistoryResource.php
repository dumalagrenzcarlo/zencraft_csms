<?php

namespace App\MoonShine\Resources\StudentPaymentHistory;

use App\Models\StudentPaymentHistory;
use App\MoonShine\Fields\StudentBelongsTo;
use App\MoonShine\Resources\PaymentType\PaymentTypeResource;
use App\MoonShine\Resources\StudentPaymentHistory\Pages\StudentPaymentHistoryDetailPage;
use App\MoonShine\Resources\StudentPaymentHistory\Pages\StudentPaymentHistoryFormPage;
use App\MoonShine\Resources\StudentPaymentHistory\Pages\StudentPaymentHistoryIndexPage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class StudentPaymentHistoryResource extends ModelResource
{
    protected ?\MoonShine\Support\Enums\PageType $redirectAfterSave = \MoonShine\Support\Enums\PageType::INDEX;

    public string $model = StudentPaymentHistory::class;

    public function getTitle(): string
    {
        return 'Student Payments';
    }

    protected function pages(): array
    {
        return [
            StudentPaymentHistoryIndexPage::class,
            StudentPaymentHistoryFormPage::class,
            StudentPaymentHistoryDetailPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'reference',
            'notes',
            'student' => ['firstname', 'lastname', 'lrn'],
        ];
    }

    protected function filters(): iterable
    {
        $filters = [
            StudentBelongsTo::make(__('Student')),
            DateRange::make(__('Payment Date'), 'payment_date'),
            Number::make(__('Amount'), 'amount'),
            Text::make(__('OR # / Reference #'), 'reference'),
        ];

        array_splice($filters, 1, 0, [
            BelongsTo::make(__('Payment Type'), 'paymentType', resource: PaymentTypeResource::class),
        ]);

        return $filters;
    }

    public function indexFields(): array
    {
        $fields = [
            ID::make(__('Id'), 'id'),
            BelongsTo::make(__('Student'), 'student', fn ($item) => "$item->firstname $item->lastname"),
            Date::make(__('Payment Date & Time'), 'payment_date')
                ->withTime()
                ->format('M d, Y h:i A'),
            Number::make(__('Amount'), 'amount'),
            Text::make(__('OR # / Reference #'), 'reference'),
            Textarea::make(__('Notes'), 'notes'),
        ];

        array_splice($fields, 2, 0, [
            BelongsTo::make(__('Payment Type'), 'paymentType', resource: PaymentTypeResource::class),
        ]);

        return $fields;
    }

    public function formFields(): array
    {
        $fields = [
            ID::make(__('Id'), 'id'),
            StudentBelongsTo::make(__('Student'))->nullable(),
            Date::make(__('Payment Date & Time'), 'payment_date')
                ->withTime()
                ->format('M d, Y h:i A'),
            Number::make(__('Amount'), 'amount'),
            Text::make(__('OR # / Reference #'), 'reference'),
            Textarea::make(__('Notes'), 'notes'),
        ];

        array_splice($fields, 2, 0, [
            BelongsTo::make(__('Payment Type'), 'paymentType', resource: PaymentTypeResource::class),
        ]);

        return $fields;
    }

    public function detailsFields(): array
    {
        return $this->indexFields();
    }
}
