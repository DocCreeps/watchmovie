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
            // Studio(s)/production companies, comma-separated like `actors` — used to
            // filter the dashboard now that studio search exists.
            $table->string('studio')->nullable()->after('director');

            // A separate 1-5 star personal rating, distinct from the free-text `note`
            // column and from TMDB's own `imdb_rating` — set once a film is watched.
            $table->unsignedTinyInteger('personal_rating')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watchlist_items', function (Blueprint $table) {
            $table->dropColumn(['studio', 'personal_rating']);
        });
    }
};
