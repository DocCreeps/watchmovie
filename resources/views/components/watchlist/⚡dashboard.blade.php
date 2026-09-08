<?php

use App\Models\WatchlistItem;
use App\Services\OmdbClient;
use App\Services\WikidataClient;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component {
    public string $query = '';
    public string $searchMode = 'title';
    public string $filter = 'all';
    public array $results = [];
    public bool $hasSearched = false;
    public ?string $searchError = null;

    public function search(): void
    {
        $this->validate(['query' => 'required|string|min:2|max:100']);
        $this->performSearch();
    }

    public function updatedQuery(): void
    {
        $this->resetErrorBag('query');

        if (mb_strlen(trim($this->query)) < 2) {
            $this->results = [];
            $this->hasSearched = false;
            $this->searchError = null;
            return;
        }

        $this->performSearch();
    }

    public function setSearchMode(string $mode): void
    {
        abort_unless(in_array($mode, ['title', 'director', 'actor'], true), 422);
        $this->searchMode = $mode;

        if (mb_strlen(trim($this->query)) >= 2) {
            $this->performSearch();
        }
    }

    private function performSearch(): void
    {
        $response = $this->searchMode === 'title'
            ? app(OmdbClient::class)->searchWithMeta($this->query)
            : app(WikidataClient::class)->searchFilms($this->query, $this->searchMode);
        $this->results = $response['results'];
        $this->searchError = $response['error'];
        $this->hasSearched = true;
    }

    public function clearSearch(): void
    {
        $this->query = '';
        $this->results = [];
        $this->hasSearched = false;
        $this->searchError = null;
    }

    public function add(string $imdbId, string $source, OmdbClient $omdb): void
    {
        abort_unless(in_array($source, ['cinema', 'streaming'], true), 422);
        if (WatchlistItem::where('imdb_id', $imdbId)->exists()) {
            session()->flash('notice', 'Ce film est déjà dans votre liste.');
            return;
        }
        $movie = $omdb->find($imdbId) ?? collect($this->results)->firstWhere('imdb_id', $imdbId);
        if (!$movie) { session()->flash('notice', 'Impossible de récupérer ce film.'); return; }
        WatchlistItem::create([...$movie, 'source' => $source]);
        session()->flash('notice', 'Film ajouté à votre liste.');
    }

    public function cycleStatus(int $id): void
    {
        $item = WatchlistItem::findOrFail($id);
        $status = $item->status === 'to_watch' ? 'watched' : 'to_watch';
        $item->update(['status' => $status, 'watched_at' => $status === 'watched' ? now() : null]);
    }

    public function remove(int $id): void { WatchlistItem::findOrFail($id)->delete(); }
    public function setFilter(string $filter): void { $this->filter = $filter; }

    public function with(): array
    {
        $items = WatchlistItem::query()
            ->when($this->filter === 'to_watch', fn ($query) => $query->where('status', 'to_watch'))
            ->when(in_array($this->filter, ['cinema', 'streaming'], true), fn ($query) => $query->where('status', 'to_watch')->where('source', $this->filter))
            ->when($this->filter === 'watched', fn ($query) => $query->where('status', 'watched'))
            ->orderBy('priority')->latest()->get();
        return [
            'items' => $items,
            'counts' => ['all' => WatchlistItem::count(), 'to_watch' => WatchlistItem::where('status', 'to_watch')->count(), 'cinema' => WatchlistItem::where('status', 'to_watch')->where('source', 'cinema')->count(), 'streaming' => WatchlistItem::where('status', 'to_watch')->where('source', 'streaming')->count(), 'watched' => WatchlistItem::where('status', 'watched')->count()],
        ];
    }
};
?>

    <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8 lg:px-12 lg:py-8">
        <header class="mb-8 flex items-center justify-between border-b border-slate-200/80 pb-6">
            <a href="/" class="flex items-center gap-3" wire:navigate>
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-orange-500 text-lg font-bold text-white shadow-md shadow-orange-200">C</span>
                <span><span class="block text-xl font-bold tracking-tight">Cinélist</span><span class="block text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Movie library</span></span>
            </a>
            <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm">Ma collection</span>
        </header>

        <section x-data="{ showHint: false }" class="relative overflow-hidden rounded-[2rem] border border-orange-100 bg-[#fffaf1] px-6 py-9 sm:px-10 lg:grid lg:grid-cols-[minmax(0,1fr)_240px] lg:gap-10 lg:px-14 lg:py-12">
            <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-orange-200/45 blur-3xl"></div>
            <div class="relative max-w-2xl">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-orange-600">Votre prochain coup de cœur</p>
                <h1 class="font-serif text-4xl leading-[1.08] text-slate-900 sm:text-5xl">Gardez une place pour les films qui comptent.</h1>
                <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">Une bibliothèque personnelle, simple à parcourir et agréable à retrouver.</p>
                <div class="group mt-8 max-w-xl rounded-2xl bg-white p-1.5 shadow-lg shadow-orange-950/5 ring-1 ring-slate-200 transition focus-within:ring-4 focus-within:ring-orange-300/40">
                    <div class="flex items-center gap-3 rounded-xl px-4 py-3">
                        <svg class="h-5 w-5 shrink-0 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.35-4.35"/></svg>
                        <input wire:model.live.debounce.500ms="query" type="search" placeholder="{{ match($searchMode) { 'director' => 'Rechercher un réalisateur…', 'actor' => 'Rechercher un acteur ou une actrice…', default => 'Rechercher un film…' } }}" autocomplete="off" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-base font-medium text-slate-900 outline-none placeholder:font-normal placeholder:text-slate-400 focus:ring-0">
                        <span wire:loading.remove wire:target="query" class="hidden rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 sm:block">Instantané</span>
                        <svg wire:loading wire:target="query" class="h-5 w-5 shrink-0 animate-spin text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-label="Recherche en cours"><path d="M21 12a9 9 0 1 1-6.22-8.56"/></svg>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Type de recherche">
                    @foreach (['title' => 'Titre', 'director' => 'Réalisateur', 'actor' => 'Acteur'] as $mode => $label)
                        <button wire:click="setSearchMode('{{ $mode }}')" @class(['rounded-full px-3.5 py-2 text-sm font-semibold transition', 'bg-slate-900 text-white shadow-sm' => $searchMode === $mode, 'border border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:text-orange-700' => $searchMode !== $mode])>{{ $label }}</button>
                    @endforeach
                    @if($searchMode !== 'title')<span class="self-center text-xs font-medium text-slate-400">Propulsé par Wikidata</span>@endif
                </div>
                @error('query') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                <p class="mt-3 text-xs font-medium text-slate-400">Les résultats apparaissent automatiquement après deux caractères.</p>
                <button x-on:click="showHint = !showHint" class="mt-4 text-sm font-semibold text-slate-500 transition hover:text-orange-700" x-text="showHint ? 'Masquer le conseil' : 'Comment ça marche ?'"></button>
                <p x-cloak x-show="showHint" x-transition class="mt-2 text-sm leading-6 text-slate-600">Cherchez un titre, ajoutez-le, puis cliquez sur son statut pour le faire progresser dans votre liste.</p>
            </div>
            <aside class="relative mt-8 hidden self-end rounded-2xl border border-orange-100 bg-white/70 p-5 lg:block"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-lg text-orange-600">✦</div><p class="mt-5 text-xs font-bold uppercase tracking-[0.15em] text-slate-400">Votre rythme</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $counts['to_watch'] }}</p><p class="mt-1 text-sm leading-5 text-slate-500">films prêts pour votre prochaine séance.</p></aside>
        </section>

        @if (session('notice'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('notice') }}</div>
        @endif

        @if ($hasSearched)
            <section class="mt-8" aria-live="polite">
                <div class="mb-4 flex items-baseline justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-600">Recherche {{ $searchMode === 'title' ? 'par titre' : ($searchMode === 'director' ? 'par réalisateur' : 'par acteur') }}</p><h2 class="mt-1 text-lg font-bold">Résultats pour « {{ $query }} »</h2></div><button wire:click="clearSearch" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Effacer</button></div>
                @if ($searchError)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4"><p class="font-semibold text-amber-900">La recherche a besoin d’être affinée</p><p class="mt-1 text-sm text-amber-800">{{ $searchError }}</p></div>
                @elseif (count($results))
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($results as $result)
                            <article wire:key="result-{{ $result['imdb_id'] }}" class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-orange-200 hover:shadow-md">
                                <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">@if($result['poster_url'])<img src="{{ $result['poster_url'] }}" alt="" class="h-full w-full object-cover">@endif</div>
                                <div class="min-w-0 flex-1"><h3 class="truncate font-bold">{{ $result['title'] }}</h3><p class="mt-1 text-sm text-slate-500">{{ $result['year'] ?: '—' }} · Film</p>@if(!empty($result['director']))<p class="mt-1 truncate text-xs font-medium text-slate-500">Réalisé par {{ $result['director'] }}</p>@elseif(!empty($result['actors']))<p class="mt-1 truncate text-xs font-medium text-slate-500">Avec {{ $result['actors'] }}</p>@endif<div class="mt-2 flex gap-3"><button wire:click="add('{{ $result['imdb_id'] }}', 'cinema')" class="text-xs font-bold text-orange-600 hover:text-orange-700">+ Cinéma</button><button wire:click="add('{{ $result['imdb_id'] }}', 'streaming')" class="text-xs font-bold text-violet-600 hover:text-violet-700">+ Streaming</button></div></div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Aucun résultat. Vérifiez votre clé OMDb ou essayez un autre titre.</p>
                @endif
            </section>
        @endif

        <section class="mt-12">
            <div class="flex flex-col gap-5 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Ma watchlist</p><h2 class="mt-2 font-serif text-3xl text-slate-900">À regarder bientôt</h2></div><p class="text-sm font-medium text-slate-500">{{ $counts['all'] }} film{{ $counts['all'] > 1 ? 's' : '' }} au total</p></div>
            <div class="mt-6 flex flex-wrap items-center gap-2" x-data="{ watchOpen: true }">
                <button wire:click="setFilter('all')" @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-slate-900 text-white' => $filter === 'all', 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $filter !== 'all'])>Tout <span class="ml-1 opacity-60">{{ $counts['all'] }}</span></button>
                <div class="flex items-center gap-1 rounded-full bg-orange-50 p-1 ring-1 ring-orange-100"><button wire:click="setFilter('to_watch')" x-on:click="watchOpen = true" @class(['rounded-full px-3 py-1 text-sm font-semibold', 'bg-orange-500 text-white' => $filter === 'to_watch', 'text-orange-800' => $filter !== 'to_watch'])>À voir <span class="ml-1 opacity-70">{{ $counts['to_watch'] }}</span></button><button x-on:click="watchOpen = !watchOpen" class="rounded-full px-2 py-1 text-orange-700" aria-label="Afficher les sous-catégories">⌄</button></div>
                <div x-show="watchOpen" x-transition class="flex items-center gap-2"><button wire:click="setFilter('cinema')" @class(['rounded-full px-3 py-2 text-sm font-semibold transition', 'bg-orange-100 text-orange-800 ring-1 ring-orange-200' => $filter === 'cinema', 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $filter !== 'cinema'])>Cinéma <span class="ml-1 opacity-60">{{ $counts['cinema'] }}</span></button><button wire:click="setFilter('streaming')" @class(['rounded-full px-3 py-2 text-sm font-semibold transition', 'bg-violet-100 text-violet-800 ring-1 ring-violet-200' => $filter === 'streaming', 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $filter !== 'streaming'])>Streaming <span class="ml-1 opacity-60">{{ $counts['streaming'] }}</span></button></div>
                <button wire:click="setFilter('watched')" @class(['rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' => $filter === 'watched', 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $filter !== 'watched'])>Vus <span class="ml-1 opacity-60">{{ $counts['watched'] }}</span></button>
            </div>
            @if ($items->isEmpty())
                <div class="mt-8 rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-6 py-16 text-center"><div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-orange-50 text-xl text-orange-600">+</div><h3 class="mt-4 font-bold">Votre liste est encore libre</h3><p class="mt-1 text-sm text-slate-500">Utilisez la recherche ci-dessus pour ajouter votre premier film.</p></div>
            @else
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($items as $item)
                        <article wire:key="movie-{{ $item->id }}" class="group overflow-hidden rounded-[1.5rem] bg-white shadow-sm ring-1 ring-slate-200 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/70">
                            <div class="relative aspect-[2/3] overflow-hidden bg-gradient-to-br from-orange-100 to-amber-50">@if($item->poster_url)<img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">@else <span class="absolute inset-0 grid place-items-center font-serif text-2xl text-orange-300">{{ $item->title }}</span>@endif
                                <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-bold text-slate-700 shadow">{{ $item->year ?: '—' }}</span>
                            </div>
                            <div class="p-4"><div class="flex items-start justify-between gap-3"><h3 class="line-clamp-1 font-bold">{{ $item->title }}</h3>@if($item->imdb_rating)<span class="shrink-0 text-sm font-bold text-amber-500">★ {{ $item->imdb_rating }}</span>@endif</div><p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ $item->genre ?: 'Film à découvrir' }}</p>
                                <div class="mt-4 flex items-center justify-between"><div class="flex items-center gap-2"><button wire:click="cycleStatus({{ $item->id }})" class="rounded-lg px-2.5 py-1.5 text-xs font-bold {{ $item->status === 'watched' ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-700' }}">{{ $item->status === 'watched' ? 'Vu' : 'À voir' }}</button>@if($item->status === 'to_watch')<span class="text-xs font-semibold {{ $item->source === 'streaming' ? 'text-violet-600' : 'text-orange-600' }}">{{ $item->source === 'streaming' ? 'Streaming' : 'Cinéma' }}</span>@endif</div><button wire:click="remove({{ $item->id }})" wire:confirm="Retirer ce film de votre liste ?" class="text-xs font-semibold text-slate-400 hover:text-rose-600">Retirer</button></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
