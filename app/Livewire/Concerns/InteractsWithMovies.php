<?php

namespace App\Livewire\Concerns;

use App\Models\WatchlistItem;
use App\Services\TmdbClient;

/**
 * Shared behaviour for any page that lists TMDB movies (search results,
 * upcoming releases): opening the details modal and adding a movie to
 * the watchlist. $results is the current list shown on the page and is
 * used as a fallback source when a movie hasn't been fetched from TMDB
 * individually yet.
 */
trait InteractsWithMovies
{
    public array $results = [];

    public ?array $selectedMovie = null;
    public bool $showModal = false;

    /**
     * Opens the summary modal. An item already in the watchlist has everything stored locally
     * (no request needed) except the trailer/similar-films/collection info, which aren't
     * persisted and are always fetched live (each cached by TmdbClient, so repeat opens are
     * free either way). A movie not yet added is fetched from TMDB entirely, with the current
     * $results list as a fallback.
     */
    public function showDetails(string $tmdbId, TmdbClient $tmdb): void
    {
        $fetched = $tmdb->find($tmdbId);
        $similar = $fetched ? $tmdb->similarFilms($tmdbId) : [];
        $collection = ($fetched && !empty($fetched['collection_id']))
            ? ['id' => $fetched['collection_id'], 'name' => $fetched['collection_name']]
            : null;

        $item = WatchlistItem::where('tmdb_id', $tmdbId)->first();
        if ($item) {
            $this->selectedMovie = [
                'title' => $item->title,
                'year' => $item->year,
                'poster_url' => $item->poster_url,
                'director' => $item->director,
                'actors' => $item->actors,
                'plot' => $item->plot,
                'genre' => $item->genre,
                'runtime' => $item->runtime,
                'imdb_rating' => $item->imdb_rating,
                'trailer_key' => $fetched['trailer_key'] ?? null,
                'trailer_lang' => $fetched['trailer_lang'] ?? null,
                'similar' => $similar,
                'collection' => $collection,
            ];
            $this->showModal = true;
            return;
        }

        $fallback = collect($this->results)->firstWhere('tmdb_id', $tmdbId);
        if (! $fetched && ! $fallback) {
            session()->flash('notice', 'Détails indisponibles pour ce film.');
            return;
        }

        $this->selectedMovie = [
            'title' => $fetched['title'] ?? $fallback['title'] ?? '',
            'year' => $fetched['year'] ?? $fallback['year'] ?? null,
            'poster_url' => $fetched['poster_url'] ?? $fallback['poster_url'] ?? null,
            'director' => $fetched['director'] ?? $fallback['director'] ?? null,
            'actors' => $fetched['actors'] ?? $fallback['actors'] ?? null,
            'plot' => $fetched['plot'] ?? $fallback['plot'] ?? null,
            'genre' => $fetched['genre'] ?? null,
            'runtime' => $fetched['runtime'] ?? null,
            'imdb_rating' => $fetched['imdb_rating'] ?? null,
            'trailer_key' => $fetched['trailer_key'] ?? null,
            'trailer_lang' => $fetched['trailer_lang'] ?? null,
            'similar' => $similar,
            'collection' => $collection,
        ];
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedMovie = null;
    }

    /**
     * Classifies a movie by release date to decide which add-to-list tags to show: not yet
     * released, or released recently enough to still plausibly be in theaters (within the same
     * ~2-month window as the Upcoming page), keeps the single "+ Cinéma" tag; anything older
     * switches to the "Déjà vue" / "+ Streaming" / "Revoir" tags instead.
     */
    public function releaseWindow(?string $releaseDate): string
    {
        if (blank($releaseDate)) {
            return 'old';
        }

        $date = \Illuminate\Support\Carbon::parse($releaseDate);

        if ($date->isFuture()) {
            return 'upcoming';
        }

        return $date->diffInDays(now()) <= 60 ? 'in_cinema' : 'old';
    }

    public function add(string $tmdbId, string $source, string $status = 'to_watch', TmdbClient $tmdb): void
    {
        abort_unless(in_array($source, ['cinema', 'streaming'], true), 422);
        abort_unless(in_array($status, ['to_watch', 'watched', 'to_rewatch'], true), 422);
        if (WatchlistItem::where('tmdb_id', $tmdbId)->exists()) {
            session()->flash('notice', 'Ce film est déjà dans votre liste.');
            return;
        }
        $movie = $tmdb->find($tmdbId) ?? collect($this->results)->firstWhere('tmdb_id', $tmdbId);
        if (! $movie) {
            session()->flash('notice', 'Impossible de récupérer ce film.');
            return;
        }
        WatchlistItem::create([
            ...$movie,
            'source' => $source,
            'status' => $status,
            // "Déjà vue" and "Revoir" are added as already watched, so stamp the
            // date now; a plain "to watch" add leaves it unset like before.
            'watched_at' => $status !== 'to_watch' ? now() : null,
        ]);
        session()->flash('notice', 'Film ajouté à votre liste.');
    }

    /**
     * Adds every not-yet-added film in a TMDB collection (a saga) to the watchlist in one go,
     * each tagged "cinéma" or "streaming" per its own release date like a normal single add.
     */
    public function addCollection(int $collectionId, TmdbClient $tmdb): void
    {
        $parts = $tmdb->collectionFilms($collectionId);
        if (empty($parts)) {
            session()->flash('notice', 'Impossible de récupérer cette saga.');
            return;
        }

        $added = 0;
        foreach ($parts as $part) {
            if (WatchlistItem::where('tmdb_id', $part['tmdb_id'])->exists()) {
                continue;
            }

            $movie = $tmdb->find($part['tmdb_id']);
            if (! $movie) {
                continue;
            }

            $window = $this->releaseWindow($movie['release_date'] ?? $part['release_date'] ?? null);

            WatchlistItem::create([
                ...$movie,
                'source' => in_array($window, ['upcoming', 'in_cinema'], true) ? 'cinema' : 'streaming',
                'status' => 'to_watch',
                'watched_at' => null,
            ]);
            $added++;
        }

        session()->flash('notice', $added > 0
            ? $added . ' film' . ($added > 1 ? 's' : '') . ' de la saga ajouté' . ($added > 1 ? 's' : '') . ' à votre liste.'
            : 'Tous les films de cette saga sont déjà dans votre liste.');
        $this->closeModal();
    }
}
