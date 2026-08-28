<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The 1-on-1 wizard now requires each step to be populated before advancing
 * and resumes at the furthest step reached on reopen. `last_step` persists
 * that furthest STEPS[].key (e.g. 'commitments') per conversation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wp_fusion_one_on_one_conversations')) {
            return;
        }

        if (Schema::hasColumn('wp_fusion_one_on_one_conversations', 'last_step')) {
            return;
        }

        Schema::table('wp_fusion_one_on_one_conversations', function (Blueprint $table) {
            $table->string('last_step', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wp_fusion_one_on_one_conversations')) {
            return;
        }

        if (! Schema::hasColumn('wp_fusion_one_on_one_conversations', 'last_step')) {
            return;
        }

        Schema::table('wp_fusion_one_on_one_conversations', function (Blueprint $table) {
            $table->dropColumn('last_step');
        });
    }
};
