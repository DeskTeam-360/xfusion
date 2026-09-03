<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARP rescoping: back to one ARP per COMPANY per year (reverses the earlier
 * "scope to group" migration), matching ARR's already-proven scoping model.
 * ARP no longer stores company_group_id at all — the "create new ARP" form
 * still picks a group (leader-identification UX only), but Laravel resolves
 * and stores company_id, same pattern as wp_fusion_arrs.
 *
 * Drops are wrapped in try/catch rather than a doctrine/dbal existence
 * check (not installed in this project) - this DB is often patched by hand
 * via the matching SQL file rather than `php artisan migrate`, so a prior
 * key/column may or may not actually be present.
 *
 * See also database/sql/wp_fusion_arp_scope_to_company.sql for servers
 * where `php artisan migrate` isn't run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wp_fusion_arps')) {
            return;
        }

        try {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->dropUnique('arp_group_year_uq');
            });
        } catch (\Throwable $e) {
            // already absent
        }

        try {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->dropIndex('arp_group_idx');
            });
        } catch (\Throwable $e) {
            // already absent
        }

        if (Schema::hasColumn('wp_fusion_arps', 'company_group_id')) {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->dropColumn('company_group_id');
            });
        }

        try {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->unique(['company_id', 'year'], 'arp_company_year_uq');
            });
        } catch (\Throwable $e) {
            // already exists
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('wp_fusion_arps')) {
            return;
        }

        try {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->dropUnique('arp_company_year_uq');
            });
        } catch (\Throwable $e) {
            // already absent
        }

        if (! Schema::hasColumn('wp_fusion_arps', 'company_group_id')) {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->unsignedBigInteger('company_group_id')->nullable()->after('company_id');
            });
        }

        try {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->unique(['company_group_id', 'year'], 'arp_group_year_uq');
            });
        } catch (\Throwable $e) {
            // already exists
        }

        try {
            Schema::table('wp_fusion_arps', function (Blueprint $table) {
                $table->index('company_group_id', 'arp_group_idx');
            });
        } catch (\Throwable $e) {
            // already exists
        }
    }
};
