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

    public function remove(int $id): void
    {
        WatchlistItem::findOrFail($id)->delete();
    }

    public function with(): array
    {
        $items = WatchlistItem::query()
            ->when(!empty($this->statusFilter), fn($query) => $query->whereIn('status', $this->statusFilter))
            ->when(!empty($this->sourceFilter), fn($query) => $query->whereIn('source', $this->sourceFilter))
            ->orderBy('priority')->latest()->get();

        return [
            'items' => $items,
            'counts' => [
                'all' => WatchlistItem::count(),
                'to_watch' => WatchlistItem::where('status', 'to_watch')->count(),
                'watched' => WatchlistItem::where('status', 'watched')->count(),
                'to_rewatch' => WatchlistItem::where('status', 'to_rewatch')->count(),
                'cinema' => WatchlistItem::where('source', 'cinema')->count(),
                'streaming' => WatchlistItem::where('source', 'streaming')->count(),
            ],
        ];
    }
}
