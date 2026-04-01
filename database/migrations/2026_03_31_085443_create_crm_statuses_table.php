<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('pipeline_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->timestamps();

            $table->unique(['external_id', 'tenant_id', 'pipeline_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_statuses');
    }
};
