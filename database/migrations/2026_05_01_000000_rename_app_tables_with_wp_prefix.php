<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename Laravel app tables to use wp_ prefix (aligned with WordPress DB conventions).
 *
 * campaign_log → wp_campaign_logs (plural, sesuai penamaan yang diminta).
 */
return new class extends Migration
{
    /** @var array<string, string> from => to */
    private array $renameMap = [
        'tags'                  => 'wp_tags',
        'campaigns'             => 'wp_campaigns',
        'campaign_log'          => 'wp_campaign_logs',
        'companies'             => 'wp_companies',
        'company_employees'     => 'wp_company_employees',
        'course_groups'         => 'wp_course_groups',
        'course_group_details'  => 'wp_course_group_details',
        'course_lists'          => 'wp_course_lists',
        'user_roles'            => 'wp_user_roles',
    ];

    public function up(): void
    {
        if (Schema::hasTable('company_employees')) {
            Schema::table('company_employees', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['company_id']);
            });
        }

        foreach ($this->renameMap as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        if (Schema::hasTable('wp_company_employees')) {
            Schema::table('wp_company_employees', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('wp_users')
                    ->onDelete('no action')
                    ->onUpdate('no action');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('wp_companies')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wp_company_employees')) {
            Schema::table('wp_company_employees', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['company_id']);
            });
        }

        $reverse = array_flip($this->renameMap);

        foreach ($reverse as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        if (Schema::hasTable('company_employees')) {
            Schema::table('company_employees', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('wp_users')
                    ->onDelete('no action')
                    ->onUpdate('no action');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('no action')
                    ->onUpdate('no action');
            });
        }
    }
};
