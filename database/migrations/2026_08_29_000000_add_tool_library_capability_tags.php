<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tool Library catalog (admin-curated grid of Tools/Activities grouped by
 * category, e.g. "Leadership Development", each tagged with which of the 5
 * COR Organizational Capabilities it addresses).
 *
 * Reuses existing tables rather than inventing a parallel schema:
 * - wp_course_groups   = the category card ("Leadership Development")
 * - wp_course_lists    = each tool/activity row
 * - wp_course_scoring_groups already holds the 5 canonical COR capability
 *   rows (Alignment/Accountability/Communication/Leadership/Execution) used
 *   system-wide for scoring - referenced here by FK instead of redefining
 *   the 5 capability values in a second place.
 *
 * The new pivot table is purely a display tag (which capabilities a tool
 * addresses) - it never touches wp_course_scoring_group_details, which
 * stays exclusively for form/field score-weighting.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wp_course_groups') && ! Schema::hasColumn('wp_course_groups', 'icon')) {
            Schema::table('wp_course_groups', function (Blueprint $table) {
                $table->string('icon', 255)->nullable()->after('title');
            });
        }

        if (Schema::hasTable('wp_course_lists') && ! Schema::hasColumn('wp_course_lists', 'icon')) {
            Schema::table('wp_course_lists', function (Blueprint $table) {
                $table->string('icon', 255)->nullable()->after('course_title');
            });
        }

        if (! Schema::hasTable('wp_fusion_tool_scoring_group_tags')) {
            Schema::create('wp_fusion_tool_scoring_group_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_list_id');
                $table->unsignedBigInteger('course_scoring_group_id');
                $table->timestamps();

                $table->unique(['course_list_id', 'course_scoring_group_id'], 'tool_capability_uq');
                $table->index('course_scoring_group_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_fusion_tool_scoring_group_tags');

        if (Schema::hasTable('wp_course_lists') && Schema::hasColumn('wp_course_lists', 'icon')) {
            Schema::table('wp_course_lists', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }

        if (Schema::hasTable('wp_course_groups') && Schema::hasColumn('wp_course_groups', 'icon')) {
            Schema::table('wp_course_groups', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
