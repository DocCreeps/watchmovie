<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('watchlist_items', 'source')) {
            Schema::table('watchlist_items', function (Blueprint $table) {
                $table->string('source', 24)->default('cinema')->after('status');
            });
        }

        DB::table('watchlist_items')->where('status', 'watching')->update(['status' => 'to_watch']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('watchlist_items', 'source')) {
            Schema::table('watchlist_items', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
