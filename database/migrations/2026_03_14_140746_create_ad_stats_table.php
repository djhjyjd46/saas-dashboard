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
        Schema::create('ad_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained()->onDelete('cascade');
            $table->date('date')->index();
            $table->decimal('spend', 15, 2)->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->timestamps();

            $table->unique(['ad_campaign_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_stats');
    }
};
