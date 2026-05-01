<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['wp_course_lists', 'course_lists'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'lms_topic_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('lms_topic_id')->nullable();
                });

                return;
            }
        }
    }

    public function down(): void
    {
        foreach (['wp_course_lists', 'course_lists'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'lms_topic_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('lms_topic_id');
                });

                return;
            }
        }
    }
};
