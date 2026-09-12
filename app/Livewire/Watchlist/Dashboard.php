<?php

namespace App\Livewire\Watchlist;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Models\WatchlistItem;
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

    /** Optional release-year bounds, same idea as the search page's minYear. */
    public ?int $minYear = null;
    public ?int $maxYear = null;

    /** Matches title or personal note (case-insensitive substring). */
    public string $searchQuery = '';

    /** When true, restricts the grid to "to watch" films added more than 3 months ago. */
    public bool $staleOnly = false;

    /** @var array<int, int> IDs currently checked in the grid, for the bulk-action toolbar. */
    public array $selectedIds = [];

    public function toggleShowWatched(): void
    {
        $this->showWatched = ! $this->showWatched;
    }

    public function toggleStale(): void
    {
        $this->staleOnly = ! $this->staleOnly;
    }

    public function toggleSelect(int $id): void
    {
        $this->selectedIds = in_array($id, $this->selectedIds, true)
            ? array_values(array_diff($this->selectedIds, [$id]))
            : [...$this->selectedIds, $id];
    }

    /**
     * Adds every currently-displayed film to the selection (called with the visible IDs from
     * the view). Acts as a toggle: if every visible film is already selected, it deselects
     * them instead, so the button can also be used to clear the current view's selection.
     */
    public function selectAllVisible(int ...$ids): void
    {
        $allVisibleAlreadySelected = ! empty($ids) && empty(array_diff($ids, $this->selectedIds));

        $this->selectedIds = $allVisibleAlreadySelected
            ? array_values(array_diff($this->selectedIds, $ids))
            : array_values(array_unique([...$this->selectedIds, ...$ids]));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    /** Applies the same watched-date logic as setStatus() to every selected film. */
    public function bulkSetStatus(string $status): void
    {
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);
        foreach ($this->selectedIds as $id) {
            $this->setStatus($id, $status);
        }
        $this->clearSelection();
    }

    public function bulkSetPriority(int $priority): void
    {
        abort_unless(in_array($priority, [1, 2, 3], true), 422);
        WatchlistItem::whereIn('id', $this->selectedIds)->update(['priority' => $priority]);
        $this->clearSelection();
    }

    public function bulkRemove(): void
    {
        WatchlistItem::whereIn('id', $this->selectedIds)->delete();
        $this->clearSelection();
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

    public function remove(int $id): void
    {
        WatchlistItem::findOrFail($id)->delete();
    }

    public function with(): array
    {
        $query = WatchlistItem::query()
            ->when(!empty($this->statusFilter), fn($q) => $q->whereIn('status', $this->statusFilter))
            ->when(!empty($this->sourceFilter), fn($q) => $q->whereIn('source', $this->sourceFilter))
            ->when($this->genreFilter !== '', fn($q) => $q->where('genre', 'like', '%' . $this->genreFilter . '%'))
            ->when($this->directorFilter !== '', fn($q) => $q->where('director', $this->directorFilter))
            ->when($this->studioFilter !== '', fn($q) => $q->where('studio', 'like', '%' . $this->studioFilter . '%'))
            ->when($this->minYear !== null, fn($q) => $q->where('year', '>=', $this->minYear))
            ->when($this->maxYear !== null, fn($q) => $q->where('year', '<=', $this->maxYear))
            ->when($this->searchQuery !== '', fn($q) => $q->where(
                fn($qq) => $qq->where('title', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('note', 'like', '%' . $this->searchQuery . '%')
            ))
            ->when($this->staleOnly, fn($q) => $q->where('status', 'to_watch')->where('created_at', '<=', now()->subMonths(3)));

        match ($this->sortBy) {
            'added_desc' => $query->latest(),
            'year_desc' => $query->orderByDesc('year'),
            'rating_desc' => $query->orderByDesc('imdb_rating'),
            'alpha' => $query->orderBy('title'),
            default => $query->orderBy('priority')->latest(),
        };

        $items = $query->get();

        // By default, "watched" films are pulled out of the main grid and tucked into a
        // separate, collapsible section below — unless the user explicitly filtered for
        // "watched" via the status chips, in which case they were asking to see them.
        $watchedItems = collect();
        if (empty($this->statusFilter)) {
            $watchedItems = $items->where('status', 'watched')->values();
            $items = $items->reject(fn($item) => $item->status === 'watched')->values();
        }

        // The main grid is further split into two clearly separated sections — "à voir"
        // and "à revoir" — instead of mixing both statuses together. Order is preserved
        // from the sort applied above.
        $toWatchItems = $items->where('status', 'to_watch')->values();
        $toRewatchItems = $items->where('status', 'to_rewatch')->values();

        // Counted with grouped SQL queries rather than loading every row into memory
        // (`WatchlistItem::all()`), so this stays cheap even once the list grows large.
        $statusCounts = WatchlistItem::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $sourceCounts = WatchlistItem::query()->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');
        $staleCount = WatchlistItem::query()->where('status', 'to_watch')->where('created_at', '<=', now()->subMonths(3))->count();

        // Only the three columns the filter dropdowns actually need, instead of hydrating
        // full WatchlistItem models (poster, plot, etc.) for every row just to list values.
        $filterFields = WatchlistItem::query()->select(['genre', 'director', 'studio'])->get();

        return [
            'items' => $items,
            'toWatchItems' => $toWatchItems,
            'toRewatchItems' => $toRewatchItems,
            'watchedItems' => $watchedItems,
            'counts' => [
                'all' => $statusCounts->sum(),
                'to_watch' => (int) $statusCounts->get('to_watch', 0),
                'watched' => (int) $statusCounts->get('watched', 0),
                'to_rewatch' => (int) $statusCounts->get('to_rewatch', 0),
                'cinema' => (int) $sourceCounts->get('cinema', 0),
                'streaming' => (int) $sourceCounts->get('streaming', 0),
                'stale' => $staleCount,
            ],
            // Distinct values across the whole list (not the filtered set), for the filter
            // dropdowns. `genre` and `studio` are stored as comma-separated lists, so they're
            // exploded first; `director` is a single value already.
            'genreOptions' => $filterFields->pluck('genre')->flatMap(fn($g) => array_map('trim', explode(',', (string) $g)))->filter()->unique()->sort()->values(),
            'directorOptions' => $filterFields->pluck('director')->filter()->unique()->sort()->values(),
            'studioOptions' => $filterFields->pluck('studio')->flatMap(fn($s) => array_map('trim', explode(',', (string) $s)))->filter()->unique()->sort()->values(),
        ];
    }
}
