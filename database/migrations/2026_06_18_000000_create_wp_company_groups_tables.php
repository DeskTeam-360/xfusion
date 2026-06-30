<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wp_company_groups')) {
            Schema::create('wp_company_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wp_company_group_details')) {
            Schema::create('wp_company_group_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_group_id');
                $table->unsignedBigInteger('user_id');
                $table->string('status', 20)->default('member');
                $table->timestamps();

                $table->unique(['company_group_id', 'user_id'], 'cg_unique_group_user');
            });
        }

        if (Schema::hasTable('wp_companies')) {
            try {
                Schema::table('wp_company_groups', function (Blueprint $table) {
                    $table->foreign('company_id')
                        ->references('id')
                        ->on('wp_companies')
                        ->onDelete('cascade')
                        ->onUpdate('no action');
                });
            } catch (\Throwable) {
                // FK tidak bisa dibentuk (engine/collation berbeda) — tabel tetap dipakai aplikasi
            }
        }

        try {
            Schema::table('wp_company_group_details', function (Blueprint $table) {
                $table->foreign('company_group_id')
                    ->references('id')
                    ->on('wp_company_groups')
                    ->onDelete('cascade')
                    ->onUpdate('no action');
            });
        } catch (\Throwable) {
            // FK tidak bisa dibentuk — tabel tetap dipakai aplikasi
        }

        if (Schema::hasTable('wp_users')) {
            try {
                Schema::table('wp_company_group_details', function (Blueprint $table) {
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('wp_users')
                        ->onDelete('no action')
                        ->onUpdate('no action');
                });
            } catch (\Throwable) {
                // FK tidak bisa dibentuk — tabel tetap dipakai aplikasi
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_company_group_details');
        Schema::dropIfExists('wp_company_groups');
    }
};
