<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'lead_name')) {
                $table->string('lead_name')->nullable()->index();
            }
            if (!Schema::hasColumn('leads', 'phone')) {
                $table->string('phone')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['lead_name', 'phone']);
        });
    }
};
