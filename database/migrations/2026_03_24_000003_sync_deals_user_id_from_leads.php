<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Sync deals.user_id to match their parent lead's user_id.
     * This is relationship-based — no name/role guessing.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'user_id') || !Schema::hasColumn('deals', 'user_id')) {
            Log::warning('Migration 000003: user_id column missing in leads or deals. Skipping sync.');
            return;
        }

        // Core fix: each deal gets the user_id of its parent lead
        $affected = DB::table('deals')
            ->join('leads', 'deals.lead_id', '=', 'leads.id')
            ->whereColumn('deals.user_id', '!=', 'leads.user_id')
            ->update(['deals.user_id' => DB::raw('leads.user_id')]);

        Log::info("Migration 000003: Synced {$affected} deal(s) user_id to match their lead's user_id.");
    }

    public function down(): void
    {
        // Not reversible
    }
};
