<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pulihkan tabel pivot company ↔ user jika belum ada:
 * - kadang migrasi rename wp_* belum dijalankan atau DB dibuat manual tanpa company_employees.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wp_company_employees')) {
            return;
        }

        if (Schema::hasTable('company_employees')) {
            foreach (['user_id', 'company_id'] as $column) {
                try {
                    Schema::table('company_employees', function (Blueprint $table) use ($column) {
                        $table->dropForeign([$column]);
                    });
                } catch (\Throwable) {
                    // constraint tidak ada atau nama berbeda
                }
            }
            Schema::rename('company_employees', 'wp_company_employees');
        } else {
            Schema::create('wp_company_employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wp_company_employees')) {
            return;
        }

        if (! Schema::hasTable('wp_users') || ! Schema::hasTable('wp_companies')) {
            return;
        }

        try {
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
        } catch (\Throwable) {
            // FK sudah ada atau nama bentrok — tabel tetap dipakai aplikasi
        }
    }

    public function down(): void
    {
        // tidak menghapus tabel agar tidak menghilangkan data produksi
    }
};
