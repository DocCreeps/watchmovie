<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->renameColumn('imdb_id', 'tmdb_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->renameColumn('tmdb_id', 'imdb_id');
        });
    }
};
