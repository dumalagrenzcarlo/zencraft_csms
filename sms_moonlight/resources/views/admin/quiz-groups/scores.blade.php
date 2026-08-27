<div class="space-y-4 rounded-2xl bg-white p-5 text-slate-700 shadow-sm">
    <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Scores for week {{ $quizGroup->week }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Student quiz scores for {{ $quizGroup->grade?->grade ?? 'selected grade level' }}.
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.quiz-groups.scores', $quizGroup) }}" class="grid gap-3 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto]">
        <select
            name="day"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm focus:border-cyan-500 focus:outline-none"
        >
            <option value="">-- Select Day --</option>
            @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                <option value="{{ $day }}" @selected($selectedDay === $day)>{{ $day }}</option>
            @endforeach
        </select>

        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Student Number or Name"
            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm focus:border-cyan-500 focus:outline-none"
        >

        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg bg-emerald-500 px-6 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600"
        >
            Search
        </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-semibold">Day</th>
                    <th class="px-4 py-3 font-semibold">Student Number</th>
                    <th class="px-4 py-3 font-semibold">Name</th>
                    <th class="px-4 py-3 font-semibold">Total Answered</th>
                    <th class="px-4 py-3 font-semibold">Correct Answers</th>
                    <th class="px-4 py-3 font-semibold">Score %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($scores as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">{{ $row['day'] }}</td>
                        <td class="px-4 py-3">{{ $row['lrn'] }}</td>
                        <td class="px-4 py-3">{{ $row['name'] }}</td>
                        <td class="px-4 py-3">{{ $row['total_answered'] }}</td>
                        <td class="px-4 py-3">{{ $row['correct_answers'] }}</td>
                        <td class="px-4 py-3">{{ number_format($row['score'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                            No score records found for this week.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between gap-4">
        <div class="text-sm text-slate-500">
            Displaying records {{ $scores->firstItem() ?? 0 }} - {{ $scores->lastItem() ?? 0 }} of {{ $scores->total() }}
        </div>

        <div>
            {{ $scores->links() }}
        </div>
    </div>
</div>
