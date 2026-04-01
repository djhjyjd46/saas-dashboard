<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['integrations', 'leads', 'deals', 'ad_campaigns', 'ad_stats'];
        
        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                if (!Schema::hasColumn($table, 'user_id')) {
                    $tableBlueprint->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                }
            });
        }

        Schema::table('leads', function (Blueprint $tableBlueprint) {
            if (!Schema::hasColumn('leads', 'qualified_at')) {
                $tableBlueprint->timestamp('qualified_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'integration_id')) {
                $tableBlueprint->foreignId('integration_id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        Schema::table('deals', function (Blueprint $tableBlueprint) {
            if (!Schema::hasColumn('deals', 'integration_id')) {
                $tableBlueprint->foreignId('integration_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('deals', 'tenant_id')) {
                // Ensure tenant_id exists if missing
                $tableBlueprint->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        // Not reversible due to complexity
    }
};
