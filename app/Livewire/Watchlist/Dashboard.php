<?php

namespace App\Livewire\Watchlist;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
use App\Services\TmdbClient;
use Livewire\Component;

class Dashboard extends Component
{
    use InteractsWithMovies;

    /** @var array<int, string> Empty means "no filter" (all statuses). */
    public array $statusFilter = [];

    /** @var array<int, string> Empty means "no filter" (all sources). */
    public array $sourceFilter = [];

    /** Single-value filters (open-ended sets of values, so a dropdown rather than chips). */
    public string $genreFilter = '';
    public string $directorFilter = '';
    public string $studioFilter = '';

    public string $sortBy = 'priority';

    /** Whether the "already watched" section is expanded (collapsed/hidden by default). */
    public bool $showWatched = false;

    public function toggleShowWatched(): void
    {
        $this->showWatched = ! $this->showWatched;
    }

    public function toggleStatusFilter(string $status): void
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);
        $this->statusFilter = $this->toggled($this->statusFilter, $status);
    }

    public function toggleSourceFilter(string $source): void
    {
        abort_unless(in_array($source, ['cinema', 'streaming'], true), 422);
        $this->sourceFilter = $this->toggled($this->sourceFilter, $source);
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilter = [];
    }

    public function clearSourceFilter(): void
    {
        $this->sourceFilter = [];
    }

    public function setSort(string $sort): void
    {
        abort_unless(in_array($sort, ['priority', 'added_desc', 'year_desc', 'rating_desc', 'alpha'], true), 422);
        $this->sortBy = $sort;
    }

    /** Adds $value to $list, or removes it if already present, so filter chips act as toggles. */
    private function toggled(array $list, string $value): array
    {
        return in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);
        $item = WatchlistItem::findOrFail($id);

        // Clears the watched date when sent back to "to watch"; sets it the first
        // time it's marked watched/to-rewatch, but keeps the original date when
        // toggling between "watched" and "to rewatch" for the same item.
        $watchedAt = match (true) {
            $status === 'to_watch' => null,
            $item->watched_at !== null => $item->watched_at,
            default => now(),
        };

        $item->update(['status' => $status, 'watched_at' => $watchedAt]);
    }

    public function setPriority(int $id, int $priority): void
    {
        abort_unless(in_array($priority, [1, 2, 3], true), 422);
        WatchlistItem::findOrFail($id)->update(['priority' => $priority]);
    }

    public function setPersonalRating(int $id, int $rating): void
    {
        abort_unless(in_array($rating, [1, 2, 3, 4, 5], true), 422);
        $item = WatchlistItem::findOrFail($id);
        // Clicking the currently-set star again clears the rating, like a toggle.
        $item->update(['personal_rating' => $item->personal_rating === $rating ? null : $rating]);
    }

    public function remove(int $id): void
    {
        WatchlistItem::findOrFail($id)->delete();
    }

    /** Opens a random "to watch" film's details modal, to help pick something to watch. */
    public function surpriseMe(TmdbClient $tmdb): void
    {
        $item = WatchlistItem::where('status', 'to_watch')->inRandomOrder()->first();
        if (! $item) {
            session()->flash('notice', 'Aucun film "à voir" dans votre liste pour le moment.');
            return;
        }
        $this->showDetails($item->tmdb_id, $tmdb);
    }

    public function with(): array
    {
        $query = WatchlistItem::query()
            ->when(!empty($this->statusFilter), fn($q) => $q->whereIn('status', $this->statusFilter))
            ->when(!empty($this->sourceFilter), fn($q) => $q->whereIn('source', $this->sourceFilter))
            ->when($this->genreFilter !== '', fn($q) => $q->where('genre', 'like', '%' . $this->genreFilter . '%'))
            ->when($this->directorFilter !== '', fn($q) => $q->where('director', $this->directorFilter))
            ->when($this->studioFilter !== '', fn($q) => $q->where('studio', 'like', '%' . $this->studioFilter . '%'));

        match ($this->sortBy) {
            'added_desc' => $query->latest(),
            'year_desc' => $query->orderByDesc('year'),
            'rating_desc' => $query->orderByDesc('imdb_rating'),
            'alpha' => $query->orderBy('title'),
            default => $query->orderBy('priority')->latest(),
        };

        $items = $query->get();
        $all = WatchlistItem::all();

        // By default, "watched" films are pulled out of the main grid and tucked into a
        // separate, collapsible section below — unless the user explicitly filtered for
        // "watched" via the status chips, in which case they were asking to see them.
        $watchedItems = collect();
        if (empty($this->statusFilter)) {
            $watchedItems = $items->where('status', 'watched')->values();
            $items = $items->reject(fn($item) => $item->status === 'watched')->values();
        }

        return [
            'items' => $items,
            'watchedItems' => $watchedItems,
            'counts' => [
                'all' => $all->count(),
                'to_watch' => $all->where('status', 'to_watch')->count(),
                'watched' => $all->where('status', 'watched')->count(),
                'to_rewatch' => $all->where('status', 'to_rewatch')->count(),
                'cinema' => $all->where('source', 'cinema')->count(),
                'streaming' => $all->where('source', 'streaming')->count(),
            ],
            // Distinct values across the whole list (not the filtered set), for the filter
            // dropdowns. `genre` and `studio` are stored as comma-separated lists, so they're
            // exploded first; `director` is a single value already.
            'genreOptions' => $all->pluck('genre')->flatMap(fn($g) => array_map('trim', explode(',', (string) $g)))->filter()->unique()->sort()->values(),
            'directorOptions' => $all->pluck('director')->filter()->unique()->sort()->values(),
            'studioOptions' => $all->pluck('studio')->flatMap(fn($s) => array_map('trim', explode(',', (string) $s)))->filter()->unique()->sort()->values(),
        ];
    }
}
