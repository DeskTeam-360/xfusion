<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * canEditReview()/canAccessReview() (ARP, QBR, IRR, 1-on-1 controllers) all
 * filter wp_company_group_details by user_id first, then status - e.g.
 * ->where('user_id', $userId)->where('status', 'leader'). The only index on
 * this table is the composite unique (company_group_id, user_id), which
 * MySQL cannot use for a user_id-led lookup (leftmost-prefix rule), so every
 * one of those access checks does a full table scan. Adds the index that
 * actually matches the query pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wp_company_group_details')) {
            return;
        }

        Schema::table('wp_company_group_details', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'cgd_user_status_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wp_company_group_details')) {
            return;
        }

        Schema::table('wp_company_group_details', function (Blueprint $table) {
            $table->dropIndex('cgd_user_status_idx');
        });
    }
};
