<main class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pb-10 sm:px-8 lg:px-12 lg:pb-14">

        <!-- Hero -->
        <section class="relative overflow-hidden rounded-3xl border border-zinc-800/80 bg-gradient-to-b from-zinc-900/90 via-zinc-900/40 to-zinc-950 p-6 sm:p-10 lg:p-14 shadow-2xl">
            <div class="absolute -right-20 -top-20 h-96 w-96 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute left-1/3 -bottom-20 h-80 w-80 rounded-full bg-red-600/10 blur-3xl pointer-events-none"></div>

            <div class="relative max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-950/60 px-3.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-800/50 mb-4">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    Votre compagnon ciné
                </span>

                <h1 class="font-serif text-3xl font-normal tracking-tight text-zinc-100 sm:text-5xl leading-[1.15]">
                    Ne cherchez plus quoi regarder, <span class="italic font-serif text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-red-400 to-rose-300">gardez tout au même endroit.</span>
                </h1>

                <p class="mt-4 text-base leading-relaxed text-zinc-400 max-w-lg">
                    Cinélist vous aide à constituer votre liste de films à voir ou à revoir, au cinéma comme en streaming,
                    et à ne rater aucune sortie salle des prochains mois.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('search.index') }}" wire:navigate class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
                        🔍 Chercher un film
                    </a>
                    <a href="{{ route('watchlist.dashboard') }}" wire:navigate class="rounded-xl border border-zinc-700 bg-zinc-900/80 px-5 py-3 text-sm font-bold text-zinc-100 transition hover:border-zinc-600 hover:bg-zinc-800">
                        🍿 Voir mon tableau de bord
                    </a>
                    @if($counts['to_watch'] > 0)
                    <button wire:click="surpriseMe" class="rounded-xl border border-zinc-700 bg-zinc-900/80 px-5 py-3 text-sm font-bold text-zinc-100 transition hover:border-amber-500/50 hover:bg-zinc-800">
                        🎲 Surprends-moi
                    </button>
                    @endif
                </div>
            </div>
        </section>

        @include('livewire.partials.notice')

        <!-- Quick stats: each status kept as its own distinct category, matching the dashboard's filter chips -->
        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Dans votre liste</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-zinc-100">{{ $counts['all'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['all'] > 1 ? 's' : '' }} enregistré{{ $counts['all'] > 1 ? 's' : '' }} au total.</p>
            </div>
            <div class="rounded-2xl border border-amber-800/40 bg-amber-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">À voir</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-amber-400">{{ $counts['to_watch'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['to_watch'] > 1 ? 's' : '' }} à découvrir.</p>
            </div>
            <div class="rounded-2xl border border-emerald-800/40 bg-emerald-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500/80">Déjà vus</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-emerald-400">{{ $counts['watched'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['watched'] > 1 ? 's' : '' }} visionné{{ $counts['watched'] > 1 ? 's' : '' }}.</p>
            </div>
            <div class="rounded-2xl border border-sky-800/40 bg-sky-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-sky-500/80">À revoir</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-sky-400">{{ $counts['to_rewatch'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['to_rewatch'] > 1 ? 's' : '' }} à revoir.</p>
            </div>
        </section>

        <!-- Recently added -->
        @if($recentlyAdded->isNotEmpty())
        <section class="mt-8">
            <div class="flex items-end justify-between border-b border-zinc-800 pb-3">
                <h2 class="font-serif text-xl font-normal text-zinc-100">Ajoutés récemment</h2>
                <a href="{{ route('watchlist.dashboard') }}" wire:navigate class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">Voir tout le tableau de bord →</a>
            </div>
            <div class="mt-5 grid grid-cols-3 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                @foreach($recentlyAdded as $item)
                <button wire:key="recent-{{ $item->id }}" wire:click="showDetails('{{ $item->tmdb_id }}')" class="group text-left">
                    <div class="aspect-[2/3] w-full overflow-hidden rounded-xl bg-zinc-950 border border-zinc-800/80 transition group-hover:border-amber-500/50">
                        @if($item->poster_url)
                        <img src="{{ $item->poster_url }}" alt="Affiche de {{ $item->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                        <div class="grid h-full w-full place-items-center p-2 text-center text-[11px] text-zinc-700">{{ $item->title }}</div>
                        @endif
                    </div>
                    <p class="mt-1.5 line-clamp-1 text-xs font-semibold text-zinc-300 group-hover:text-amber-400 transition-colors">{{ $item->title }}</p>
                </button>
                @endforeach
            </div>
        </section>
        @endif

        <!-- Upcoming cinema releases already in the watchlist -->
        @if($upcomingInWatchlist->isNotEmpty())
        <section class="mt-8">
            <div class="flex items-end justify-between border-b border-zinc-800 pb-3">
                <h2 class="font-serif text-xl font-normal text-zinc-100">Bientôt au cinéma, dans votre liste</h2>
                <a href="{{ route('upcoming.index') }}" wire:navigate class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">Toutes les sorties →</a>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($upcomingInWatchlist as $movie)
                <button wire:key="upcoming-{{ $movie['tmdb_id'] }}" wire:click="showDetails('{{ $movie['tmdb_id'] }}')" class="group flex gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-3 text-left transition hover:border-amber-500/50 hover:bg-zinc-900">
                    <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg bg-zinc-950">
                        @if($movie['poster_url'])
                        <img src="{{ $movie['poster_url'] }}" alt="" class="h-full w-full object-cover">
                        @else
                        <div class="grid h-full w-full place-items-center text-[9px] text-zinc-700">N/A</div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="line-clamp-2 text-sm font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">{{ $movie['title'] }}</p>
                        <p class="mt-1 text-xs font-semibold text-amber-400">{{ \Illuminate\Support\Carbon::parse($movie['release_date'])->translatedFormat('d F Y') }}</p>
                    </div>
                </button>
                @endforeach
            </div>
        </section>
        @endif

        <!-- Feature cards -->
        <section class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('watchlist.dashboard') }}" wire:navigate class="group rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-amber-950/80 text-xl border border-amber-800/50">🍿</div>
                <h2 class="mt-4 text-base font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Tableau de bord</h2>
                <p class="mt-1.5 text-sm text-zinc-500 leading-relaxed">
                    Retrouvez vos films à voir, déjà vus et à revoir, filtrables par statut et par source (cinéma ou streaming).
                </p>
            </a>

            <a href="{{ route('search.index') }}" wire:navigate class="group rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-violet-950/80 text-xl border border-violet-800/50">🔍</div>
                <h2 class="mt-4 text-base font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Recherche</h2>
                <p class="mt-1.5 text-sm text-zinc-500 leading-relaxed">
                    Cherchez un film par titre, un réalisateur ou un acteur, puis ajoutez-le d'un clic à votre liste.
                </p>
            </a>

            <a href="{{ route('upcoming.index') }}" wire:navigate class="group rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-sky-950/80 text-xl border border-sky-800/50">🎬</div>
                <h2 class="mt-4 text-base font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Sorties à venir</h2>
                <p class="mt-1.5 text-sm text-zinc-500 leading-relaxed">
                    Parcourez les sorties cinéma des deux prochains mois pour ne rater aucun film attendu.
                </p>
            </a>

            <a href="{{ route('stats.index') }}" wire:navigate class="group rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6 transition hover:border-amber-500/50 hover:bg-zinc-900">
                <div class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-950/80 text-xl border border-emerald-800/50">📊</div>
                <h2 class="mt-4 text-base font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">Mon année ciné</h2>
                <p class="mt-1.5 text-sm text-zinc-500 leading-relaxed">
                    Note moyenne, genre et réalisateur favoris, et l'historique complet de vos films vus.
                </p>
            </a>
        </section>

        @include('livewire.partials.movie-modal')
    </div>
</main>
