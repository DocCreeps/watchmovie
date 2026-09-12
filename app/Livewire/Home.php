<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use Livewire\Component;

class Home extends Component
{
    use InteractsWithMovies;

    public function with(TmdbClient $tmdb): array
    {
        // Grouped SQL counts rather than loading every row, same as the dashboard.
        $statusCounts = WatchlistItem::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $toWatch = WatchlistItem::query()->where('status', 'to_watch')->orderBy('priority')->latest()->limit(6)->get();

        // Cinema films still "to watch", cross-referenced against TMDB's upcoming releases
        // (same window as the /a-venir page) so only ones with a confirmed date show up.
        $watchlistCinemaIds = WatchlistItem::query()
            ->where('source', 'cinema')
            ->where('status', 'to_watch')
            ->pluck('tmdb_id');

        $upcomingInWatchlist = collect();
        if ($watchlistCinemaIds->isNotEmpty()) {
            $upcoming = $tmdb->upcomingFilms();
            $upcomingInWatchlist = collect($upcoming['results'])
                ->whereIn('tmdb_id', $watchlistCinemaIds->all())
                ->take(4)
                ->values();
        }

        return [
            'counts' => [
                'all' => $statusCounts->sum(),
                'to_watch' => (int) $statusCounts->get('to_watch', 0),
                'watched' => (int) $statusCounts->get('watched', 0),
                'to_rewatch' => (int) $statusCounts->get('to_rewatch', 0),
            ],
            'toWatch' => $toWatch,
            'upcomingInWatchlist' => $upcomingInWatchlist,
        ];
    }
}
