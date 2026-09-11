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
        </div>
    </div>
</div>
@endif
