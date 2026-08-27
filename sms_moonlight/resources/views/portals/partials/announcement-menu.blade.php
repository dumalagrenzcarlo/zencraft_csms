<div class="fixed right-4 top-[4.5rem] z-50 max-h-[70vh] w-[min(22rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 text-left shadow-2xl sm:absolute sm:right-0 sm:top-auto sm:mt-2">
    <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
        <p class="font-bold text-slate-900">Announcements</p>
        <span class="text-xs font-semibold text-slate-500">{{ $headerAnnouncements->count() }} active</span>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse ($headerAnnouncements as $announcement)
            <button
                type="button"
                class="block w-full rounded-xl px-3 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[var(--hanan-primary)]"
                onclick="openPortalAnnouncement(
                    'portal-announcement-{{ $announcement->id }}',
                    @js($announcement->title),
                    @js($announcement->created_at?->format('F j, Y') ?? '')
                )"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="font-semibold text-slate-900">{{ $announcement->title }}</p>
                    @if ($announcement->created_at)
                        <time class="shrink-0 text-[11px] text-slate-400" datetime="{{ $announcement->created_at->toDateString() }}">
                            {{ $announcement->created_at->format('M j') }}
                        </time>
                    @endif
                </div>
                <p class="mt-1 whitespace-normal text-sm leading-relaxed text-slate-600">
                    {{ \Illuminate\Support\Str::limit(trim(strip_tags($announcement->content)), 110) }}
                </p>
                <span class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-sky-600">
                    Read announcement
                    <span aria-hidden="true">&rarr;</span>
                </span>
            </button>
            <template id="portal-announcement-{{ $announcement->id }}">{!! $announcement->content !!}</template>
        @empty
            <div class="px-4 py-8 text-center">
                <p class="font-semibold text-slate-800">No active announcements</p>
                <p class="mt-1 text-xs text-slate-500">New notices will appear here.</p>
            </div>
        @endforelse
    </div>
</div>
