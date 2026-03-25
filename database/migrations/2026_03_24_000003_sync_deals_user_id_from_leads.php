<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    /**
     * Sync deals.user_id to match their parent lead's user_id.
     * This is relationship-based — no name/role guessing.
     */
    public function up(): void
    {
        // Diagnostic: show current state
        $leadUsers  = DB::table('leads')->select('user_id', DB::raw('COUNT(*) as cnt'))->groupBy('user_id')->get();
        $dealUsers  = DB::table('deals')->select('user_id', DB::raw('COUNT(*) as cnt'))->groupBy('user_id')->get();
        $allUsers   = DB::table('users')->select('id', 'name', 'email', 'role')->get();

        Log::info('Migration 000003 diagnostic — BEFORE fix', [
            'users'      => $allUsers->toArray(),
            'lead_dist'  => $leadUsers->toArray(),
            'deal_dist'  => $dealUsers->toArray(),
        ]);

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
