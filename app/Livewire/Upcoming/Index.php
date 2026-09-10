<?php

namespace App\Livewire\Upcoming;

use App\Livewire\Concerns\InteractsWithMovies;
use App\Services\TmdbClient;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Index extends Component
{
    use InteractsWithMovies;

    public ?string $loadError = null;

    private const MONTHS_FR = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function mount(TmdbClient $tmdb): void
    {
        $response = $tmdb->upcomingFilms();
        $this->results = $response['results'];
        $this->loadError = $response['error'];
    }

    /** Upcoming releases grouped by month, in chronological order. */
    public function with(): array
    {
        $groups = collect($this->results)
            ->groupBy(function ($movie) {
                $date = Carbon::parse($movie['release_date']);
                return self::MONTHS_FR[$date->month] . ' ' . $date->year;
            });

        return ['groups' => $groups];
    }
}
