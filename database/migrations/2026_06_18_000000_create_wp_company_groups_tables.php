<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_company_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('wp_companies')
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('wp_company_group_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_group_id')
                ->constrained('wp_company_groups')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 20)->default('member');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('wp_users')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->unique(['company_group_id', 'user_id'], 'cg_unique_group_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_company_group_details');
        Schema::dropIfExists('wp_company_groups');
    }
};
