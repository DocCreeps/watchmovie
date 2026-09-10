<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <!-- Page intro -->
        <div class="flex flex-col gap-4 border-b border-zinc-800 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-400">Ma sélection</p>
                <h1 class="mt-1 font-serif text-3xl font-normal text-zinc-100">Films à voir & à revoir</h1>
            </div>
            <span class="text-xs font-semibold text-zinc-400 bg-zinc-900 border border-zinc-800 px-3.5 py-1.5 rounded-full self-start sm:self-auto">
                {{ $counts['all'] }} film{{ $counts['all'] > 1 ? 's' : '' }} dans votre liste
            </span>
        </div>

        @include('livewire.partials.notice')

        <!-- Filter Controls (multi-select: several statuses / sources can be active at once) -->
        <div class="mt-6 flex flex-col gap-4 rounded-2xl bg-zinc-900/60 p-3 border border-zinc-800/80 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filtrer par statut">
                <span class="mr-2 text-[10px] font-bold uppercase tracking-widest text-zinc-500 pl-1">Visionnage</span>
                <button wire:click="clearStatusFilter" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-zinc-100 text-zinc-950 shadow-sm'=> empty($statusFilter), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !empty($statusFilter)])>
                    Tous <span class="ml-1 opacity-60">({{ $counts['all'] }})</span>
                </button>
                <button wire:click="toggleStatusFilter('to_watch')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-950/40'=> in_array('to_watch', $statusFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('to_watch', $statusFilter, true)])>
                    À voir <span class="ml-1 opacity-80">({{ $counts['to_watch'] }})</span>
                </button>
                <button wire:click="toggleStatusFilter('watched')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-emerald-600 text-white shadow-lg shadow-emerald-950/40'=> in_array('watched', $statusFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('watched', $statusFilter, true)])>
                    Déjà vus <span class="ml-1 opacity-70">({{ $counts['watched'] }})</span>
                </button>
                <button wire:click="toggleStatusFilter('to_rewatch')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-sky-600 text-white shadow-lg shadow-sky-950/40'=> in_array('to_rewatch', $statusFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('to_rewatch', $statusFilter, true)])>
                    À revoir <span class="ml-1 opacity-70">({{ $counts['to_rewatch'] }})</span>
                </button>
            </div>

            <div class="h-px sm:h-5 sm:w-px bg-zinc-800"></div>

            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filtrer par source">
                <span class="mr-2 text-[10px] font-bold uppercase tracking-widest text-zinc-500 pl-1">Où regarder</span>
                <button wire:click="clearSourceFilter" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-zinc-100 text-zinc-950 shadow-sm'=> empty($sourceFilter), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !empty($sourceFilter)])>
                    Tous
                </button>
                <button wire:click="toggleSourceFilter('cinema')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-amber-950 text-amber-300 border border-amber-800/60'=> in_array('cinema', $sourceFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('cinema', $sourceFilter, true)])>
                    Cinéma <span class="ml-1 opacity-60">({{ $counts['cinema'] }})</span>
                </button>
                <button wire:click="toggleSourceFilter('streaming')" @class(['rounded-xl px-3.5 py-1.5 text-xs font-bold transition', 'bg-violet-950 text-violet-300 border border-violet-800/60'=> in_array('streaming', $sourceFilter, true), 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => !in_array('streaming', $sourceFilter, true)])>
                    Streaming <span class="ml-1 opacity-60">({{ $counts['streaming'] }})</span>
                </button>
            </div>
        </div>

        <!-- Movie Grid or Empty State -->
        @if ($items->isEmpty())
        <div class="mt-8 rounded-3xl border border-dashed border-zinc-800 bg-zinc-900/30 px-6 py-16 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-zinc-800 text-xl text-amber-400 font-bold">🍿</div>
            <h3 class="mt-4 text-base font-bold text-zinc-100">
                @if ($counts['all'] > 0)
                Aucun film ne correspond à ces filtres.
                @else
                Votre liste est vide pour le moment
                @endif
            </h3>
            <p class="mt-1 text-sm text-zinc-500 max-w-sm mx-auto">
                @if ($counts['all'] > 0)
                Essayez de combiner d'autres statuts ou sources, ou réinitialisez les filtres.
                @else
                Recherchez un film pour préparer vos prochaines soirées ciné.
                @endif
            </p>
            @if ($counts['all'] === 0)
            <a href="{{ route('search.index') }}" wire:navigate class="mt-5 inline-block rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-zinc-950 transition hover:bg-amber-400">
                🔍 Chercher un film
            </a>
            @endif
        </div>
        @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($items as $item)
            <article wire:key="movie-{{ $item->id }}" class="group relative flex flex-col overflow-hidden rounded-2xl bg-zinc-900/90 border border-zinc-800/80 transition duration-300 hover:-translate-y-1 hover:border-zinc-700 hover:shadow-2xl hover:shadow-amber-950/20">

                <!-- Poster Container -->
                <button wire:click="showDetails('{{ $item->tmdb_id }}')" class="relative aspect-[2/3] w-full cursor-pointer overflow-hidden bg-zinc-950 text-left focus:outline-none" aria-label="Voir le résumé de {{ $item->title }}">
                    @if($item->poster_url)
                    <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105 group-hover:opacity-90">
                    @else
                    <div class="grid h-full w-full place-items-center p-4 text-center font-serif text-xl text-zinc-700">
                        {{ $item->title }}
                    </div>
                    @endif

                    <!-- Badges Overlay -->
                    <div class="absolute inset-x-0 top-0 flex items-center justify-between p-3 bg-gradient-to-b from-black/80 via-black/40 to-transparent">
                        <span class="rounded-lg bg-black/60 backdrop-blur-md px-2 py-1 text-[11px] font-bold text-zinc-300 border border-white/10">
                            {{ $item->year ?: '—' }}
                        </span>
                        @if($item->imdb_rating)
                        <span class="flex items-center gap-1 rounded-lg bg-amber-400 backdrop-blur-md px-2 py-1 text-[11px] font-black text-zinc-950 shadow-md">
                            ★ {{ $item->imdb_rating }}
                        </span>
                        @endif
                    </div>
                </button>

                <!-- Movie Info -->
                <div class="flex flex-1 flex-col justify-between p-4">
                    <div>
                        <h3 class="line-clamp-1 font-bold text-zinc-100 text-base group-hover:text-amber-400 transition-colors" title="{{ $item->title }}">
                            {{ $item->title }}
                        </h3>
                        <p class="mt-0.5 line-clamp-1 text-xs text-zinc-500">
                            {{ $item->genre ?: 'Film' }}
                        </p>
                        <p class="mt-1 text-[11px] font-semibold {{ $item->source === 'streaming' ? 'text-violet-400' : 'text-amber-400' }}">
                            {{ $item->source === 'streaming' ? 'Streaming' : 'Cinéma' }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between pt-3 border-t border-zinc-800/80">
                        <div class="flex items-center gap-1" role="group" aria-label="Statut de visionnage">
                            <button wire:click="setStatus({{ $item->id }}, 'to_watch')" title="Marquer à voir" @class(['grid h-7 w-7 place-items-center rounded-lg text-sm font-bold transition', 'bg-amber-950/80 text-amber-400 border border-amber-800/50'=> $item->status === 'to_watch', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $item->status !== 'to_watch'])>○</button>
                            <button wire:click="setStatus({{ $item->id }}, 'watched')" title="Marquer comme vu" @class(['grid h-7 w-7 place-items-center rounded-lg text-sm font-bold transition', 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50'=> $item->status === 'watched', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $item->status !== 'watched'])>✓</button>
                            <button wire:click="setStatus({{ $item->id }}, 'to_rewatch')" title="Marquer à revoir" @class(['grid h-7 w-7 place-items-center rounded-lg text-sm font-bold transition', 'bg-sky-950/80 text-sky-400 border border-sky-800/50'=> $item->status === 'to_rewatch', 'text-zinc-600 border border-transparent hover:text-zinc-300 hover:bg-zinc-800/60' => $item->status !== 'to_rewatch'])>↺</button>
                        </div>
                        <button wire:click="remove({{ $item->id }})" wire:confirm="Retirer ce film de votre liste ?" class="p-1 text-zinc-600 hover:text-red-400 transition" title="Retirer de la liste">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
        @endif

        @include('livewire.partials.movie-modal')
    </div>
</main>
