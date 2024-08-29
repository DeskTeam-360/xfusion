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
        Schema::create('schedule_executions', function (Blueprint $table) {
            $table->id();
            $table->string('link');
            $table->string('title');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('schedule_access')->nullable();
            $table->dateTime('schedule_deadline')->nullable();
            $table->integer('status');
//            'link', 'company_id', 'user_id', 'status', 'title'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_executions');
    }
};
