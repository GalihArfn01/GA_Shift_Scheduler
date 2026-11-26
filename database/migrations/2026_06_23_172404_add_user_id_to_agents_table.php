<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Relasi opsional ke tabel users -- setiap agent BISA punya akun
     * login (role 'agent'), tapi tidak wajib di level database (nullable),
     * supaya proses seeding/testing tetap fleksibel. Di level aplikasi,
     * kebijakan saat ini mewajibkan semua agent punya akun, namun kolom
     * ini dibuat nullable untuk keamanan/fleksibilitas teknis.
     *
     * ON DELETE SET NULL: jika akun User dihapus, data Agent (riwayat
     * jadwal, off request) TETAP ada, hanya relasi ke akun login yang
     * terputus -- konsisten dengan filosofi soft delete yang sudah
     * diterapkan pada Agent sebelumnya.
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
