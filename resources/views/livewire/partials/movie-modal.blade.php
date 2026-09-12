@if ($showModal && $selectedMovie)
<div x-data x-on:keydown.escape.window="$wire.closeModal()" class="fixed inset-0 z-50 grid place-items-center bg-black/80 px-4 py-8 backdrop-blur-md transition-opacity" wire:click.self="closeModal">
    <div class="relative flex w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-zinc-900 border border-zinc-800 shadow-2xl sm:flex-row max-h-[90vh]" wire:click.stop>
        <button wire:click="closeModal" class="absolute right-3 top-3 z-10 grid h-8 w-8 place-items-center rounded-full bg-black/60 text-zinc-400 backdrop-blur-md transition hover:bg-black hover:text-white" aria-label="Fermer">✕</button>

        <!-- Poster in Modal -->
        <div class="h-64 shrink-0 overflow-hidden bg-zinc-950 sm:h-auto sm:w-64">
            @if($selectedMovie['poster_url'])
            <img src="{{ $selectedMovie['poster_url'] }}" alt="Affiche de {{ $selectedMovie['title'] }}" class="h-full w-full object-cover">
            @else
            <div class="grid h-full w-full place-items-center p-6 text-center font-serif text-2xl text-zinc-700">
                {{ $selectedMovie['title'] }}
            </div>
            @endif
        </div>

        <!-- Content in Modal -->
        <div class="flex-1 overflow-y-auto p-6 sm:p-8">
            <div class="flex items-center gap-2">
                <span class="rounded-md bg-amber-950 border border-amber-800/60 px-2 py-0.5 text-xs font-bold text-amber-400">
                    {{ $selectedMovie['year'] ?: 'Année inconnue' }}
                </span>
                @if($selectedMovie['runtime'])
                <span class="text-xs text-zinc-400 font-medium">• {{ $selectedMovie['runtime'] }}</span>
                @endif
            </div>

            <h2 class="mt-2 font-serif text-2xl font-normal text-zinc-100 sm:text-3xl">{{ $selectedMovie['title'] }}</h2>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                @if($selectedMovie['imdb_rating'])
                <span class="flex items-center gap-1 text-sm font-bold text-amber-400">
                    ★ {{ $selectedMovie['imdb_rating'] }} <span class="text-xs text-zinc-500 font-normal">/10</span>
                </span>
                @endif
                @if($selectedMovie['genre'])
                <span class="text-xs font-medium text-zinc-300 bg-zinc-800 px-2.5 py-1 rounded-md">{{ $selectedMovie['genre'] }}</span>
                @endif
            </div>

            <dl class="mt-5 space-y-2 text-sm border-t border-zinc-800 pt-4">
                @if($selectedMovie['director'])
                <div class="flex gap-2">
                    <dt class="font-bold text-zinc-400">Réalisation :</dt>
                    <dd class="text-zinc-200">{{ $selectedMovie['director'] }}</dd>
                </div>
                @endif
                @if($selectedMovie['actors'])
                <div class="flex gap-2">
                    <dt class="font-bold text-zinc-400">Casting :</dt>
                    <dd class="text-zinc-200">{{ $selectedMovie['actors'] }}</dd>
                </div>
                @endif
            </dl>

            @if($selectedMovie['plot'])
            <p class="mt-4 text-sm leading-relaxed text-zinc-400 border-t border-zinc-800 pt-4">
                {{ $selectedMovie['plot'] }}
            </p>
            @else
            <p class="mt-4 text-sm italic text-zinc-600 border-t border-zinc-800 pt-4">Résumé indisponible.</p>
            @endif

            @if($selectedMovie['trailer_key'] ?? null)
            <div class="mt-4 border-t border-zinc-800 pt-4">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-bold text-zinc-400">Bande-annonce</p>
                    @if($selectedMovie['trailer_lang'] ?? null)
                    <span class="rounded-full border border-amber-800/50 bg-amber-950/60 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-400">{{ $selectedMovie['trailer_lang'] }}</span>
                    @endif
                </div>
                <div class="aspect-video overflow-hidden rounded-xl bg-black">
                    <iframe
                        src="https://www.youtube.com/embed/{{ $selectedMovie['trailer_key'] }}"
                        title="Bande-annonce de {{ $selectedMovie['title'] }}"
                        class="h-full w-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
            @endif
            @if($selectedMovie['collection'] ?? null)
            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-amber-800/40 bg-amber-950/30 px-4 py-3">
                <p class="text-xs text-amber-300">
                    Fait partie de la saga <span class="font-bold">{{ $selectedMovie['collection']['name'] }}</span>
                </p>
                <button wire:click="addCollection({{ $selectedMovie['collection']['id'] }})" wire:confirm="Ajouter tous les films de cette saga qui ne sont pas déjà dans votre liste ?" class="shrink-0 rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-bold text-zinc-950 transition hover:bg-amber-400">
                    + Toute la saga
                </button>
            </div>
            @endif

            @if(!empty($selectedMovie['watch_providers']) && (!empty($selectedMovie['watch_providers']['flatrate']) || !empty($selectedMovie['watch_providers']['rent']) || !empty($selectedMovie['watch_providers']['buy'])))
            <div class="mt-4 border-t border-zinc-800 pt-4">
                <p class="mb-2 text-sm font-bold text-zinc-400">Où regarder (France)</p>

                @foreach ([
                    ['key' => 'flatrate', 'label' => 'Abonnement', 'color' => 'text-emerald-500/80'],
                    ['key' => 'rent', 'label' => 'Location', 'color' => 'text-sky-500/80'],
                    ['key' => 'buy', 'label' => 'Achat', 'color' => 'text-zinc-500'],
                ] as $group)
                @if(!empty($selectedMovie['watch_providers'][$group['key']]))
                <div class="mb-2 last:mb-0">
                    <p class="mb-1 text-[10px] font-bold uppercase tracking-widest {{ $group['color'] }}">{{ $group['label'] }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($selectedMovie['watch_providers'][$group['key']] as $provider)
                        <span class="flex items-center gap-1.5 rounded-lg bg-zinc-800/80 px-2 py-1 text-[11px] font-semibold text-zinc-200">
                            @if($provider['logo_url'])
                            <img src="{{ $provider['logo_url'] }}" alt="" class="h-4 w-4 rounded">
                            @endif
                            {{ $provider['name'] }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach

                <p class="mt-2 text-[10px] text-zinc-600">Données fournies par JustWatch.</p>
            </div>
            @endif

            @if(isset($selectedMovie['item_id']))
            <div class="mt-4 border-t border-zinc-800 pt-4">
                <label for="watchlist-note" class="mb-2 block text-sm font-bold text-zinc-400">Votre note personnelle</label>
                <textarea
                    id="watchlist-note"
                    wire:model.blur="selectedMovie.note"
                    rows="3"
                    placeholder="Une remarque, un souvenir, une raison de le (re)voir…"
                    class="w-full resize-none rounded-xl border border-zinc-800 bg-zinc-950/80 px-3 py-2 text-sm text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50"
                >{{ $selectedMovie['note'] }}</textarea>
                <div class="mt-2 flex justify-end">
                    <button wire:click="saveNote" class="rounded-lg bg-zinc-800 px-3 py-1.5 text-[11px] font-bold text-zinc-200 transition hover:bg-zinc-700">
                        Enregistrer la note
                    </button>
                </div>
            </div>
            @endif

            @if(!empty($selectedMovie['similar']))
            <div class="mt-4 border-t border-zinc-800 pt-4">
                <p class="mb-2 text-sm font-bold text-zinc-400">Films similaires</p>
                <div class="flex gap-2.5 overflow-x-auto pb-1">
                    @foreach($selectedMovie['similar'] as $movie)
                    <button wire:click="showDetails('{{ $movie['tmdb_id'] }}')" class="group w-16 shrink-0 text-left">
                        <div class="aspect-[2/3] w-16 overflow-hidden rounded-lg bg-zinc-950">
                            @if($movie['poster_url'])
                            <img src="{{ $movie['poster_url'] }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
                            @else
                            <div class="grid h-full w-full place-items-center text-[9px] text-zinc-700">N/A</div>
                            @endif
                        </div>
                        <p class="mt-1 line-clamp-2 text-[10px] leading-tight text-zinc-400 group-hover:text-amber-400">{{ $movie['title'] }}</p>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
