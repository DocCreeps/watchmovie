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
                </div>
            </div>
        </section>

        <!-- Quick stats -->
        <section class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-zinc-800/80 bg-zinc-900/60 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Dans votre liste</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-zinc-100">{{ $counts['all'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['all'] > 1 ? 's' : '' }} enregistré{{ $counts['all'] > 1 ? 's' : '' }} au total.</p>
            </div>
            <div class="rounded-2xl border border-amber-800/40 bg-amber-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">En attente de séance</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-amber-400">{{ $counts['to_watch'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['to_watch'] > 1 ? 's' : '' }} à découvrir.</p>
            </div>
            <div class="rounded-2xl border border-sky-800/40 bg-sky-950/20 p-6">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-sky-500/80">Classiques</p>
                <p class="mt-1 text-4xl font-black tracking-tight text-sky-400">{{ $counts['to_rewatch'] }}</p>
                <p class="mt-1 text-xs text-zinc-500">film{{ $counts['to_rewatch'] > 1 ? 's' : '' }} à revoir.</p>
            </div>
        </section>

        <!-- Feature cards -->
        <section class="mt-8 grid gap-6 lg:grid-cols-3">
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
        </section>
    </div>
</main>
