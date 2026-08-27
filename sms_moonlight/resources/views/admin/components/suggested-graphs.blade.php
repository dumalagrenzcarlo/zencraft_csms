<div class="col-span-12">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-800">Suggested graphs to add next</h3>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach($items as $item)
                <div class="rounded-md border border-slate-100 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
