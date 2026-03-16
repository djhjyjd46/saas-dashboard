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
            $table->foreignId('tenant_id')->after('id')->index()->constrained()->onDelete('cascade');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->index()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_stats', function (Blueprint $table) {
            //
        });
    }
};
