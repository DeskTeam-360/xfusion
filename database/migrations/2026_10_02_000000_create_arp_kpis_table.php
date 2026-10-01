<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ARP Step 4 — Key Performance Indicators™. Table is actually created on
     * the WordPress/MySQL side via dbDelta
     * (app/Http/Plugin/xfusion-plugin/includes/annual-readiness-plan/arp-db-migration.php,
     * XFUSION_ARP_DB_VERSION 1.2) — this migration documents the same shape
     * for environments that run `php artisan migrate` directly.
     */
    public function up(): void
    {
        Schema::create('wp_fusion_arp_kpis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arp_id');
            $table->string('name')->default('');
            $table->string('type', 20)->default('leading');
            $table->text('description')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->string('current_baseline', 120)->nullable();
            $table->string('target_value', 120)->nullable();
            $table->date('target_date')->nullable();
            $table->string('measurement_frequency', 20)->default('quarterly');
            $table->string('data_source')->nullable();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->text('readiness_priority_ids')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('priority_rank')->default(0);
            $table->timestamps();

            $table->index('arp_id', 'arpkpi_arp_idx');
            $table->index(['arp_id', 'priority_rank'], 'arpkpi_rank_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_fusion_arp_kpis');
    }
};
