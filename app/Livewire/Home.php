<?php

namespace App\Livewire;

use App\Models\WatchlistItem;
use Livewire\Component;

class Home extends Component
{
    public function with(): array
    {
        return [
            'counts' => [
                'all' => WatchlistItem::count(),
                'to_watch' => WatchlistItem::where('status', 'to_watch')->count(),
                'watched' => WatchlistItem::where('status', 'watched')->count(),
                'to_rewatch' => WatchlistItem::where('status', 'to_rewatch')->count(),
            ],
        ];
    }
}
