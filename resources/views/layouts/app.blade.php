<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Cinélist' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col overflow-x-hidden bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500 selection:text-zinc-950">
    <div class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-8 lg:px-12 lg:pt-10">
        <header class="mb-10 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-800/80 pb-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-3.5" wire:navigate>
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-red-600 text-xl font-black text-white shadow-lg shadow-amber-950/40 ring-1 ring-amber-400/30 transition-transform duration-300 group-hover:scale-105">🍿</span>
                <span>
                    <span class="block text-2xl font-black tracking-wider uppercase text-zinc-100 group-hover:text-amber-400 transition-colors">Cinélist</span>
                    <span class="block text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-500">Watch & Rewatch</span>
                </span>
            </a>

            <nav class="flex flex-wrap items-center gap-1.5" aria-label="Navigation principale">
                @foreach ([
                'home' => 'Accueil',
                'watchlist.dashboard' => 'Tableau de bord',
                'search.index' => 'Recherche',
                'upcoming.index' => 'À venir',
                ] as $routeName => $label)
                <a href="{{ route($routeName) }}" wire:navigate @class([ 'rounded-xl px-3.5 py-2 text-xs font-bold uppercase tracking-wide transition' , 'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-950/40'=> request()->routeIs($routeName),
                    'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/60' => ! request()->routeIs($routeName),
                    ])>
                    {{ $label }}
                </a>
                @endforeach
            </nav>
        </header>
    </div>

    <main class="flex-1">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
