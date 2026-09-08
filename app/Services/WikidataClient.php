<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WikidataClient
{
    /** @return array{results: array<int, array<string, mixed>>, error: ?string} */
    public function searchFilms(string $query, string $mode): array
    {
        if (! in_array($mode, ['director', 'actor'], true) || blank(trim($query))) {
            return ['results' => [], 'error' => null];
        }

        $cacheKey = 'wikidata.'.$mode.'.'.md5(strtolower(trim($query)));
        if ($cached = Cache::get($cacheKey)) {
            return ['results' => $cached, 'error' => null];
        }

        $person = $this->findPerson($query);
        if (! $person) {
            return ['results' => [], 'error' => 'Cette personne n’a pas été trouvée. Essayez avec son nom complet.'];
        }

        $property = $mode === 'director' ? 'P57' : 'P161';
        $sparql = <<<SPARQL
PREFIX wd: <http://www.wikidata.org/entity/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wikibase: <http://wikiba.se/ontology#>
PREFIX bd: <http://www.bigdata.com/rdf#>
SELECT DISTINCT ?imdbId ?filmLabel ?year ?image WHERE {
  ?film wdt:P31 wd:Q11424;
        wdt:P345 ?imdbId;
        wdt:$property wd:{$person['id']}.
  OPTIONAL { ?film wdt:P577 ?date. BIND(YEAR(?date) AS ?year) }
  OPTIONAL { ?film wdt:P18 ?image }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
}
LIMIT 18
SPARQL;

        try {
            $response = Http::acceptJson()->withUserAgent(config('app.name').'/1.0 movie watchlist')
                ->connectTimeout(5)->timeout(15)->retry(2, 300, throw: false)
                ->get(config('services.wikidata.url'), ['query' => $sparql, 'format' => 'json']);
        } catch (ConnectionException $exception) {
            Log::warning('Wikidata search connection failed.', ['message' => $exception->getMessage()]);
            return ['results' => [], 'error' => 'Wikidata est momentanément inaccessible. Réessayez dans quelques instants.'];
        }

        if ($response->failed()) {
            Log::warning('Wikidata search request failed.', ['status' => $response->status()]);
            return ['results' => [], 'error' => 'La recherche avancée est momentanément indisponible.'];
        }

        $results = collect($response->json('results.bindings', []))->map(fn (array $row) => [
            'imdb_id' => data_get($row, 'imdbId.value'),
            'title' => data_get($row, 'filmLabel.value'),
            'year' => (int) data_get($row, 'year.value'),
            'poster_url' => str_replace('http://', 'https://', data_get($row, 'image.value')) ?: null,
            'director' => $mode === 'director' ? $person['label'] : null,
            'actors' => $mode === 'actor' ? $person['label'] : null,
            'type' => 'movie',
        ])->filter(fn (array $movie) => filled($movie['imdb_id']) && filled($movie['title']))->unique('imdb_id')->values()->all();

        Cache::put($cacheKey, $results, now()->addHours(6));

        return ['results' => $results, 'error' => null];
    }

    /** @return array{id: string, label: string}|null */
    private function findPerson(string $query): ?array
    {
        $cacheKey = 'wikidata.person.'.md5(strtolower(trim($query)));
        if ($person = Cache::get($cacheKey)) {
            return $person;
        }

        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(8)->retry(1, 250, throw: false)
                ->get(config('services.wikidata.api_url'), [
                    'action' => 'wbsearchentities', 'search' => trim($query), 'language' => 'fr',
                    'uselang' => 'fr', 'type' => 'item', 'limit' => 1, 'format' => 'json', 'origin' => '*',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Wikidata person lookup failed.', ['message' => $exception->getMessage()]);
            return null;
        }

        if ($response->failed()) {
            Log::warning('Wikidata person lookup request failed.', ['status' => $response->status()]);
            return null;
        }

        $match = collect($response->json('search', []))->first();
        if (! $match || ! filled($match['id'] ?? null)) {
            return null;
        }

        $person = ['id' => $match['id'], 'label' => $match['label'] ?? trim($query)];
        Cache::put($cacheKey, $person, now()->addDay());

        return $person;
    }
}
