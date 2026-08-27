<div class="border-b border-slate-200 p-4 sm:p-5">
    <form method="GET" action="{{ route('student.dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <input type="hidden" name="tab" value="payments">

        <div class="flex-1">
            <label for="payment-search" class="mb-2 block text-sm font-semibold text-slate-700">Search payments</label>
            <input
                id="payment-search"
                type="search"
                name="payment_search"
                value="{{ request('payment_search') }}"
                placeholder="Search date, type, reference, notes, or amount"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none"
            >
        </div>

        <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
            Search
        </button>
    </form>
</div>

<div class="divide-y divide-slate-100 sm:hidden">
    @forelse ($paymentHistories as $payment)
        <article class="space-y-3 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-slate-900">{{ $payment->paymentType?->name ?? 'Unspecified' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $payment->payment_date?->format('M j, Y g:i A') ?? '-' }}</p>
                </div>
                <p class="shrink-0 font-bold text-emerald-700">&#8369;{{ number_format((float) $payment->amount, 2) }}</p>
            </div>
            <dl class="grid grid-cols-[88px_1fr] gap-x-3 gap-y-2 text-sm">
                <dt class="font-semibold text-slate-500">Reference</dt>
                <dd class="break-words text-slate-700">{{ $payment->reference ?: '-' }}</dd>
                <dt class="font-semibold text-slate-500">Notes</dt>
                <dd class="break-words text-slate-700">{{ $payment->notes ?: '-' }}</dd>
            </dl>
        </article>
    @empty
        <div class="px-5 py-12 text-center">
            <p class="text-lg font-semibold text-slate-900">No payment history yet.</p>
            <p class="mt-2 text-sm text-slate-500">Recorded payments will appear here.</p>
        </div>
    @endforelse
</div>

<div class="hidden overflow-x-auto sm:block">
    <table class="min-w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Payment Date</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Payment Type</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($paymentHistories as $payment)
                <tr class="transition hover:bg-slate-50">
                    <td class="whitespace-nowrap px-6 py-5 text-sm text-slate-700">
                        {{ $payment->payment_date?->format('M j, Y g:i A') ?? '-' }}
                    </td>
                    <td class="px-6 py-5 text-sm font-semibold text-slate-800">
                        {{ $payment->paymentType?->name ?? 'Unspecified' }}
                    </td>
                    <td class="px-6 py-5 text-sm text-slate-600">{{ $payment->reference ?: '-' }}</td>
                    <td class="px-6 py-5 text-sm text-slate-600">{{ $payment->notes ?: '-' }}</td>
                    <td class="whitespace-nowrap px-6 py-5 text-right text-sm font-bold text-emerald-700">
                        &#8369;{{ number_format((float) $payment->amount, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <p class="text-lg font-semibold text-slate-900">No payment history yet.</p>
                        <p class="mt-2 text-sm text-slate-500">Recorded payments will appear here.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
