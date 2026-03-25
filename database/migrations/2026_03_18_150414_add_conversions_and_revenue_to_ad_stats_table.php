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
        Schema::table('ad_stats', function (Blueprint $table) {
            $table->integer('conversions')->default(0)->after('impressions');
            $table->decimal('revenue', 15, 2)->default(0)->after('conversions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_stats', function (Blueprint $table) {
            $table->dropColumn(['conversions', 'revenue']);
        });
    }
};
