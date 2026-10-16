<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wp_companies', 'slug')) {
            Schema::table('wp_companies', function (Blueprint $table) {
                $table->string('slug', 120)->nullable()->unique('companies_slug_uq')->after('title');
            });
        }
    }

    public function down(): void
    {
        Schema::table('wp_companies', function (Blueprint $table) {
            $table->dropUnique('companies_slug_uq');
            $table->dropColumn('slug');
        });
    }
};
