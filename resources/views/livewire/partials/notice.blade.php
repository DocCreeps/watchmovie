@if (session('notice'))
<div class="mt-6 flex items-center gap-3 rounded-2xl border border-emerald-900/60 bg-emerald-950/40 px-4 py-3.5 text-sm font-medium text-emerald-300 shadow-lg backdrop-blur-sm">
    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-800/50 text-emerald-300 text-xs font-bold">✓</span>
    {{ session('notice') }}
</div>
@endif
