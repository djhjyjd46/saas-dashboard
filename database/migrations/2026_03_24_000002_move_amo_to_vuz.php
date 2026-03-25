<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    public function up(): void
    {
        // Find VUZ user — we look by role if exists, otherwise by name/email
        $vuz = DB::table('users')
            ->where('role', 'manager')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%ВУЗ%')
                  ->orWhere('name', 'like', '%Vuz%')
                  ->orWhere('email', 'like', '%vuz%');
            })
            ->orderBy('id')
            ->first();

        if (!$vuz) {
            Log::warning('Migration 000002: VUZ user not found. Skipping integration reassignment.');
            return;
        }

        $affected = DB::table('integrations')
            ->where('type', 'amocrm')
            ->where('user_id', '!=', $vuz->id)
            ->update(['user_id' => $vuz->id]);

        Log::info('Migration 000002: Moved AmoCRM integrations to VUZ user.', [
            'user_id'  => $vuz->id,
            'email'    => $vuz->email,
            'affected' => $affected,
        ]);

        // Also move all leads and deals to VUZ user
        $leadsAffected = DB::table('leads')
            ->where('user_id', '!=', $vuz->id)
            ->orWhereNull('user_id')
            ->update(['user_id' => $vuz->id]);

        $dealsAffected = DB::table('deals')
            ->where('user_id', '!=', $vuz->id)
            ->orWhereNull('user_id')
            ->update(['user_id' => $vuz->id]);

        Log::info('Migration 000002: Moved leads/deals to VUZ user.', [
            'leads' => $leadsAffected,
            'deals' => $dealsAffected,
        ]);
    }

    public function down(): void
    {
        // Not reversible
    }
};
