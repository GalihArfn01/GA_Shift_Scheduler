<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Soft delete untuk users -- dipakai saat Agent resign (soft delete),
     * akun login miliknya JUGA otomatis dinonaktifkan dengan cara yang
     * sama, konsisten dengan filosofi soft delete Agent. Data login
     * tetap ada di database untuk audit, tapi tidak bisa dipakai login
     * (lihat penyesuaian di AuthenticatedSessionController).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
