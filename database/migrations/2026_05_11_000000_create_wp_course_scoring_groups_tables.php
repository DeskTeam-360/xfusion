<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_course_scoring_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('wp_course_scoring_group_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_scoring_group_id')
                ->constrained('wp_course_scoring_groups')
                ->cascadeOnDelete();
            $table->unsignedInteger('form_id');
            $table->unsignedInteger('field_id');
            $table->timestamps();

            $table->unique(
                ['course_scoring_group_id', 'form_id', 'field_id'],
                'csg_unique_group_form_field'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wp_course_scoring_group_details');
        Schema::dropIfExists('wp_course_scoring_groups');
    }
};
