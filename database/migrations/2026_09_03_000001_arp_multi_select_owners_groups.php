<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ARP Step 3/4: Executive Owner becomes multi-select (was a single
 * wp_users.ID), and Step 4's Related Group(s) becomes a real multi-select
 * sourced from this ARP's company groups (was a hardcoded pseudo-scope
 * slug like "all_leaders" - never a real wp_company_groups row).
 *
 * See also database/sql/wp_fusion_arp_multi_select_owners_groups.sql for
 * servers where `php artisan migrate` isn't run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wp_fusion_arp_readiness_priorities')) {
            Schema::table('wp_fusion_arp_readiness_priorities', function (Blueprint $table) {
                if (Schema::hasColumn('wp_fusion_arp_readiness_priorities', 'executive_owner_user_id')) {
                    $table->dropColumn('executive_owner_user_id');
                }
                if (! Schema::hasColumn('wp_fusion_arp_readiness_priorities', 'executive_owner_user_ids')) {
                    $table->text('executive_owner_user_ids')->nullable()->after('business_rationale');
                }
            });
        }

        if (Schema::hasTable('wp_fusion_arp_strategic_priorities')) {
            Schema::table('wp_fusion_arp_strategic_priorities', function (Blueprint $table) {
                if (Schema::hasColumn('wp_fusion_arp_strategic_priorities', 'owner_user_id')) {
                    $table->dropColumn('owner_user_id');
                }
                if (! Schema::hasColumn('wp_fusion_arp_strategic_priorities', 'owner_user_ids')) {
                    $table->text('owner_user_ids')->nullable()->after('description');
                }
            });

            // doctrine/dbal (required by Schema::table()->change()) isn't
            // installed in this project - raw SQL instead, matching the
            // convention already used elsewhere for column type changes.
            DB::statement('ALTER TABLE `wp_fusion_arp_strategic_priorities` MODIFY COLUMN `related_groups` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wp_fusion_arp_readiness_priorities')) {
            Schema::table('wp_fusion_arp_readiness_priorities', function (Blueprint $table) {
                if (Schema::hasColumn('wp_fusion_arp_readiness_priorities', 'executive_owner_user_ids')) {
                    $table->dropColumn('executive_owner_user_ids');
                }
                if (! Schema::hasColumn('wp_fusion_arp_readiness_priorities', 'executive_owner_user_id')) {
                    $table->unsignedBigInteger('executive_owner_user_id')->nullable()->after('business_rationale');
                }
            });
        }

        if (Schema::hasTable('wp_fusion_arp_strategic_priorities')) {
            Schema::table('wp_fusion_arp_strategic_priorities', function (Blueprint $table) {
                if (Schema::hasColumn('wp_fusion_arp_strategic_priorities', 'owner_user_ids')) {
                    $table->dropColumn('owner_user_ids');
                }
                if (! Schema::hasColumn('wp_fusion_arp_strategic_priorities', 'owner_user_id')) {
                    $table->unsignedBigInteger('owner_user_id')->nullable()->after('description');
                }
            });

            DB::statement('ALTER TABLE `wp_fusion_arp_strategic_priorities` MODIFY COLUMN `related_groups` VARCHAR(80) NULL');
        }
    }
};
