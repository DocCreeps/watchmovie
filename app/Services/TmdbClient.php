<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbClient
{
    /** @return array{results: array<int, array<string, mixed>>, error: ?string} */
    public function searchFilms(string $query, string $mode = 'title', ?int $minYear = null): array
    {
        if (blank(config('services.tmdb.token'))) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }
        if (blank(trim($query))) {
            return ['results' => [], 'error' => null];
        }

        // Bump when the shape/logic of the cached results changes, so stale
        // entries from a previous version of this method never get served.
        $cacheVersion = 'v2';
        $cacheKey = 'tmdb.search.' . $cacheVersion . '.' . $mode . '.' . md5(strtolower(trim($query))) . '.y' . ($minYear ?? 'all');
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $movies = collect();

            if ($mode === 'title') {
                $response = $this->client()->get('search/movie', $this->withAuth([
                    'query' => trim($query),
                    'language' => 'fr-FR',
                    'page' => 1,
                ]));

                if ($response->failed()) {
                    Log::warning('TMDB search failed.', ['status' => $response->status(), 'body' => $response->body()]);
                    return ['results' => [], 'error' => 'Erreur TMDB.'];
                }

                $movies = collect($response->json('results'));
            } else {
                $personResponse = $this->client()->get('search/person', $this->withAuth([
                    'query' => trim($query),
                    'language' => 'fr-FR',
                    'page' => 1,
                ]));

                if ($personResponse->failed() || empty($personResponse->json('results'))) {
                    return ['results' => [], 'error' => 'Personne non trouvée.'];
                }

                $personId = $personResponse->json('results.0.id');

                $creditsResponse = $this->client()->get("person/{$personId}/movie_credits", $this->withAuth([
                    'language' => 'fr-FR',
                ]));

                if ($creditsResponse->failed()) {
                    return ['results' => [], 'error' => 'Erreur lors de la récupération des films.'];
                }

                $moviesKey = $mode === 'director' ? 'crew' : 'cast';
                $movies = collect($creditsResponse->json($moviesKey, []));

                if ($mode === 'director') {
                    $movies = $movies->filter(fn($m) => ($m['job'] ?? '') === 'Director');
                }
            }

            // Standardize basic movie format and filter out invalid ones
            $movies = $movies->map(function ($movie) {
                if (empty($movie['id']) || empty($movie['title'])) return null;
                $year = isset($movie['release_date']) && $movie['release_date'] ? (int) substr($movie['release_date'], 0, 4) : null;
                $character = strtolower($movie['character'] ?? '');

                return [
                    'tmdb_id' => (string) $movie['id'],
                    'title' => $movie['title'],
                    'year' => $year,
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : null,
                    'type' => 'movie',
                    'plot' => $movie['overview'] ?? null,
                    // TMDB has no clean "dubbing role" flag. Many voice credits do say
                    // so directly ("Woody (voice)", "Narrator (voice)"), but a lot of
                    // community-edited entries omit it — so a credit on a movie tagged
                    // Animation (genre id 16) is treated as a dubbing role too, since
                    // there's no such thing as being on-screen in an animated film.
                    'is_voice' => str_contains($character, 'voice')
                        || str_contains($character, 'narrat')
                        || in_array(16, $movie['genre_ids'] ?? [], true),
                ];
            })->filter()->unique('tmdb_id');

            // Apply min year filter
            if ($minYear) {
                $movies = $movies->filter(fn($m) => $m['year'] !== null && $m['year'] >= $minYear);
            }

            // Sort by year desc, nulls at bottom. No arbitrary cap here: for
            // 'title' TMDB already limits to ~20 results per page, but an
            // actor/director search must be able to return a full filmography.
            $movies = $movies->sortByDesc(fn($m) => $m['year'] ?? -9999)->values();

            // Fetch missing details (director, actors) via pool to show on cards
            $results = $movies->all();
            if (count($results) > 0) {
                $poolResponses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($results) {
                    return collect($results)->map(
                        fn($m) =>
                        $this->authorize($pool->as($m['tmdb_id']))
                            ->get(config('services.tmdb.url') . "movie/{$m['tmdb_id']}", $this->withAuth([
                                'language' => 'fr-FR',
                                'append_to_response' => 'credits',
                            ]))
                    )->all();
                });

                foreach ($results as &$movie) {
                    $res = $poolResponses[$movie['tmdb_id']] ?? null;
                    if ($res && $res->ok()) {
                        $data = $res->json();
                        $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null;
                        $actors = collect($data['credits']['cast'] ?? [])->take(3)->pluck('name')->implode(', ');
                        $movie['director'] = $director;
                        $movie['actors'] = $actors ?: null;
                        // Use the detailed plot if available
                        $movie['plot'] = $data['overview'] ?: $movie['plot'];
                    }
                }
            }

            Cache::put($cacheKey, $results, now()->addHours(6));

            return ['results' => $results, 'error' => empty($results) ? 'Aucun résultat.' : null];
        } catch (\Exception $e) {
            Log::warning('TMDB search failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    /**
     * Cinema releases (limited or wide theatrical) in France over the next two
     * months, sorted chronologically. Paginates through TMDB's discover
     * endpoint, capped at a handful of pages since two months of releases
     * fit comfortably within that.
     *
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function upcomingFilms(): array
    {
        if (blank(config('services.tmdb.token'))) {
            return ['results' => [], 'error' => 'La clé TMDB est absente de la configuration.'];
        }

        $cacheKey = 'tmdb.upcoming.v1.' . now()->toDateString();
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        try {
            $start = now()->toDateString();
            $end = now()->addMonths(2)->toDateString();

            $movies = collect();
            $maxPages = 6;

            for ($page = 1; $page <= $maxPages; $page++) {
                $response = $this->client()->get('discover/movie', $this->withAuth([
                    'language' => 'fr-FR',
                    'region' => 'FR',
                    // 2 = limited theatrical, 3 = wide theatrical
                    'with_release_type' => '2|3',
                    'sort_by' => 'primary_release_date.asc',
                    'primary_release_date.gte' => $start,
                    'primary_release_date.lte' => $end,
                    'page' => $page,
                ]));

                if ($response->failed()) {
                    Log::warning('TMDB upcoming failed.', ['status' => $response->status(), 'body' => $response->body()]);
                    break;
                }

                $data = $response->json();
                $movies = $movies->merge($data['results'] ?? []);

                if ($page >= ($data['total_pages'] ?? 1)) {
                    break;
                }
            }

            $results = $movies->map(function ($movie) {
                if (empty($movie['id']) || empty($movie['title']) || empty($movie['release_date'])) return null;

                return [
                    'tmdb_id' => (string) $movie['id'],
                    'title' => $movie['title'],
                    'year' => (int) substr($movie['release_date'], 0, 4),
                    'release_date' => $movie['release_date'],
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : null,
                    'plot' => $movie['overview'] ?? null,
                ];
            })->filter()->unique('tmdb_id')->sortBy('release_date')->values()->all();

            Cache::put($cacheKey, $results, now()->addHours(12));

            return ['results' => $results, 'error' => empty($results) ? 'Aucune sortie prévue sur cette période.' : null];
        } catch (\Exception $e) {
            Log::warning('TMDB upcoming failed.', ['message' => $e->getMessage()]);
            return ['results' => [], 'error' => 'Erreur de connexion à TMDB.'];
        }
    }

    public function find(string $tmdbId): ?array
    {
        if (blank(config('services.tmdb.token'))) return null;

        return Cache::remember("tmdb.movie.{$tmdbId}", now()->addDay(), function () use ($tmdbId) {
            try {
                $response = $this->client()->get("movie/{$tmdbId}", $this->withAuth([
                    'language' => 'fr-FR', // Ensure French
                    'append_to_response' => 'credits',
                ]));

                if ($response->failed()) return null;

                $data = $response->json();
                $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null;
                $actors = collect($data['credits']['cast'] ?? [])->take(3)->pluck('name')->implode(', ');

                return [
                    'tmdb_id' => (string) $data['id'],
                    'title' => $data['title'] ?? $data['original_title'],
                    'year' => isset($data['release_date']) ? (int) substr($data['release_date'], 0, 4) : null,
                    'poster_url' => isset($data['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : null,
                    'type' => 'movie',
                    'genre' => collect($data['genres'] ?? [])->pluck('name')->implode(', '),
                    'director' => $director,
                    'actors' => $actors ?: null,
                    'runtime' => isset($data['runtime']) ? $data['runtime'] . ' min' : null,
                    'imdb_rating' => $data['vote_average'] ?? null,
                    'plot' => $data['overview'] ?? null,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Base HTTP client for TMDB, pointed at the configured base URL, with the
     * v4 Bearer token attached when the configured credential is one.
     */
    private function client(): PendingRequest
    {
        return $this->authorize(Http::baseUrl(config('services.tmdb.url'))->acceptJson());
    }

    /**
     * Attach Bearer auth to a pending request, but only when the configured
     * credential is a v4 "API Read Access Token" (a JWT). A classic v3 API
     * key is not a valid Bearer token and must be sent as a query param
     * instead (see withAuth()).
     */
    private function authorize(PendingRequest $request): PendingRequest
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $request->withToken($token) : $request;
    }

    /**
     * Adds the TMDB credential to the query string when a v3 API key is
     * configured. v4 Bearer tokens are sent as a header instead (see
     * authorize()), so nothing is added to the query in that case.
     */
    private function withAuth(array $query): array
    {
        $token = (string) config('services.tmdb.token');

        return $this->isV4Token($token) ? $query : [...$query, 'api_key' => $token];
    }

    /**
     * TMDB's v4 "API Read Access Token" is a JWT (three dot-separated
     * segments). The older v3 API key is a plain 32-character string and
     * must never be sent as a Bearer token — TMDB rejects it with a 401.
     */
    private function isV4Token(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }
}
