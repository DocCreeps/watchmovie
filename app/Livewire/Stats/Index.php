<?php

namespace App\Livewire\Stats;

use App\Models\WatchlistItem;
use Livewire\Component;

class Index extends Component
{
    /**
     * "Watched" here means any film with a `watched_at` timestamp (status watched or
     * to_rewatch both qualify, since both were seen at least once). Loading them all is fine:
     * unlike the dashboard's full table, this set only ever grows with actual viewing habits,
     * and every aggregate below (genre/director breakdown, monthly grouping) needs the full
     * set in memory since genres/directors aren't normalized columns.
     */
    public function with(): array
    {
        $watched = WatchlistItem::query()->whereNotNull('watched_at')->get();

        $genreCounts = $watched->pluck('genre')
            ->flatMap(fn($g) => array_map('trim', explode(',', (string) $g)))
            ->filter()
            ->countBy()
            ->sortDesc();

        $directorCounts = $watched->pluck('director')->filter()->countBy()->sortDesc();

        $rated = $watched->whereNotNull('personal_rating');

        $watchedThisYear = $watched->filter(fn($item) => $item->watched_at?->year === now()->year);

        $timeline = $watched->sortByDesc('watched_at')
            ->groupBy(fn($item) => ucfirst($item->watched_at->translatedFormat('F Y')));

        return [
            'totalWatched' => $watched->count(),
            'watchedThisYear' => $watchedThisYear->count(),
            'averageRating' => $rated->isNotEmpty() ? round((float) $rated->avg('personal_rating'), 1) : null,
            'topGenre' => $genreCounts->keys()->first(),
            'topGenreCount' => $genreCounts->first(),
            'topDirector' => $directorCounts->keys()->first(),
            'topDirectorCount' => $directorCounts->first(),
            'cinemaCount' => $watched->where('source', 'cinema')->count(),
            'streamingCount' => $watched->where('source', 'streaming')->count(),
            'timeline' => $timeline,
        ];
    }
}
