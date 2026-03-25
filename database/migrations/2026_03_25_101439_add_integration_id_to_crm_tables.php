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
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete();
        });
        
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('integration_id');
        });
        
        Schema::table('deals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('integration_id');
        });
    }
};
