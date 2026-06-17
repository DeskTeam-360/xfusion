<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_xfusion_result_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('created_at');
            $table->dateTime('evaluated_at');
            $table->unsignedBigInteger('company_information')->default(0);
            $table->unsignedBigInteger('scoring_group_id');
            $table->string('scoring_group_title', 255)->default('');
            $table->unsignedTinyInteger('score')->default(0);
            $table->longText('evaluation_input');
            $table->longText('evaluation');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->string('status', 20)->default('published');
            $table->string('insight_model', 64)->default('');
            $table->string('prompt_version_id', 64)->default('');
            $table->string('prompt_version_label', 255)->default('');
            $table->decimal('cost_usd', 12, 6)->default(0);
            $table->dateTime('inserted_at');

            $table->index(['user_id', 'scoring_group_id'], 'xfre_user_group_idx');
            $table->index('evaluated_at', 'xfre_evaluated_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_xfusion_result_evaluations');
    }
};
