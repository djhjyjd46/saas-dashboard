<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            if (!Schema::hasColumn('integrations', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->onDelete('cascade');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->onDelete('cascade');
            }
        });

        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->onDelete('cascade');
            }
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('ad_campaigns', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->onDelete('cascade');
            }
        });

        Schema::table('ad_stats', function (Blueprint $table) {
            if (!Schema::hasColumn('ad_stats', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained()->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('ad_stats', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
