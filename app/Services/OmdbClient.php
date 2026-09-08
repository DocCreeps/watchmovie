<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OmdbClient
{
    public function search(string $query): array
    {
        return $this->searchWithMeta($query)['results'];
    }

    /** @return array{results: array<int, array<string, mixed>>, error: ?string} */
    public function searchWithMeta(string $query): array
    {
        if (blank(config('services.omdb.key'))) {
            return ['results' => [], 'error' => 'La clé OMDb est absente de la configuration.'];
        }
        if (blank(trim($query))) {
            return ['results' => [], 'error' => null];
        }

        $cacheKey = 'omdb.search.'.md5(strtolower(trim($query)));
        if (($cachedResults = Cache::get($cacheKey)) && isset($cachedResults[0]['imdb_id'])) {
            return ['results' => $cachedResults, 'error' => null];
        }
        Cache::forget($cacheKey);

        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(12)->retry(2, 250, throw: false)
                ->get(config('services.omdb.url'), ['apikey' => config('services.omdb.key'), 's' => trim($query), 'type' => 'movie']);
        } catch (ConnectionException $exception) {
            Log::warning('OMDb search connection failed.', ['message' => $exception->getMessage()]);
            return ['results' => [], 'error' => 'OMDb est momentanément inaccessible. Réessayez dans quelques instants.'];
        }

        if ($response->failed()) {
            Log::warning('OMDb search request failed.', ['status' => $response->status()]);
            return ['results' => [], 'error' => 'La recherche n’a pas pu joindre OMDb. Réessayez dans quelques instants.'];
        }

        $data = $response->json();
        if (($data['Response'] ?? 'False') !== 'True') {
            $error = $data['Error'] ?? 'Résultat indisponible.';
            return ['results' => [], 'error' => $error === 'Too many results.' ? 'Recherche trop large : ajoutez quelques caractères.' : $error];
        }

        $results = collect($data['Search'] ?? [])->map(fn (array $movie) => [
            'imdb_id' => $movie['imdbID'], 'title' => $movie['Title'], 'year' => (int) strtok($movie['Year'] ?? '', '–'),
            'poster_url' => ($movie['Poster'] ?? 'N/A') === 'N/A' ? null : $movie['Poster'], 'type' => $movie['Type'] ?? 'movie',
        ])->all();

        Cache::put($cacheKey, $results, now()->addHour());

        return ['results' => $results, 'error' => null];
    }

    public function find(string $imdbId): ?array
    {
        if (blank(config('services.omdb.key'))) return null;
        return Cache::remember("omdb.movie.{$imdbId}", now()->addDay(), function () use ($imdbId) {
            try { $data = Http::timeout(8)->get(config('services.omdb.url'), ['apikey' => config('services.omdb.key'), 'i' => $imdbId, 'plot' => 'short'])->throw()->json(); }
            catch (ConnectionException|\Illuminate\Http\Client\RequestException) { return null; }
            if (($data['Response'] ?? 'False') !== 'True') return null;
            return [
                'imdb_id' => $data['imdbID'], 'title' => $data['Title'], 'year' => (int) strtok($data['Year'] ?? '', '–'),
                'poster_url' => ($data['Poster'] ?? 'N/A') === 'N/A' ? null : $data['Poster'], 'type' => strtolower($data['Type'] ?? 'movie'),
                'genre' => $data['Genre'] ?? null, 'runtime' => $data['Runtime'] ?? null,
                'imdb_rating' => is_numeric($data['imdbRating'] ?? null) ? $data['imdbRating'] : null, 'plot' => $data['Plot'] ?? null,
            ];
        });
    }
}
