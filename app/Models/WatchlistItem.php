<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistItem extends Model
{
    protected $fillable = ['tmdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'director', 'actors', 'studio', 'runtime', 'imdb_rating', 'plot', 'status', 'source', 'priority', 'note', 'personal_rating', 'watched_at'];

    protected function casts(): array
    {
        return ['watched_at' => 'datetime', 'imdb_rating' => 'decimal:1'];
    }
}
