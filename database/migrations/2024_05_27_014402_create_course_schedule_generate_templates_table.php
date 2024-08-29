<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_schedule_generate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('header');
            $table->string('title');
            $table->string('sub_title');
            $table->integer('week');
            $table->string('url');
            $table->string('parent_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_schedule_generate_templates');
    }
};
