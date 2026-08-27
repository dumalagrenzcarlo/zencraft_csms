<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\StudentPaymentHistory;
use App\Support\CsvCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        abort_unless((bool) config('school_portal.features.payments_module'), 404);

        $query = StudentPaymentHistory::query()
            ->with(['student', 'paymentType']);

        $this->applyFilters($query, $request);

        $filename = 'student-payments-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'ID',
                'Student',
                'Student Number',
                'Payment Type',
                'Payment Date & Time',
                'Amount',
                'OR # / Reference #',
                'Notes',
            ]);

            $query
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->chunk(500, function ($payments) use ($output): void {
                    foreach ($payments as $payment) {
                        fputcsv($output, CsvCell::row([
                            $payment->id,
                            trim(($payment->student?->firstname ?? '').' '.($payment->student?->lastname ?? '')),
                            $payment->student?->lrn,
                            $payment->paymentType?->name,
                            $payment->payment_date?->format('Y-m-d H:i:s'),
                            number_format((float) $payment->amount, 2, '.', ''),
                            $payment->reference,
                            $payment->notes,
                        ]));
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $filters = (array) $request->input('filter', []);
        $dateRange = (array) ($filters['payment_date'] ?? []);

        $query
            ->when($filters['student_id'] ?? null, fn (Builder $query, $studentId) => $query->where('student_id', $studentId))
            ->when($filters['payment_type_id'] ?? null, fn (Builder $query, $paymentTypeId) => $query->where('payment_type_id', $paymentTypeId))
            ->when($dateRange['from'] ?? null, fn (Builder $query, $from) => $query->whereDate('payment_date', '>=', $from))
            ->when($dateRange['to'] ?? null, fn (Builder $query, $to) => $query->whereDate('payment_date', '<=', $to))
            ->when($filters['amount'] ?? null, fn (Builder $query, $amount) => $query->where('amount', $amount))
            ->when($filters['reference'] ?? null, fn (Builder $query, $reference) => $query->where('reference', 'like', '%'.$reference.'%'));

        $search = trim((string) $request->input('search', ''));

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhere('notes', 'like', '%'.$search.'%')
                ->orWhereHas('student', function (Builder $query) use ($search): void {
                    $query
                        ->where('firstname', 'like', '%'.$search.'%')
                        ->orWhere('lastname', 'like', '%'.$search.'%')
                        ->orWhere('lrn', 'like', '%'.$search.'%');
                });

            if (ctype_digit($search)) {
                $query->orWhereKey((int) $search);
            }
        });
    }
}
