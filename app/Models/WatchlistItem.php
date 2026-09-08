<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistItem extends Model
{
    protected $fillable = ['imdb_id', 'title', 'year', 'poster_url', 'type', 'genre', 'runtime', 'imdb_rating', 'plot', 'status', 'source', 'priority', 'note', 'watched_at'];

    protected function casts(): array
    {
        return ['watched_at' => 'datetime', 'imdb_rating' => 'decimal:1'];
    }
}
