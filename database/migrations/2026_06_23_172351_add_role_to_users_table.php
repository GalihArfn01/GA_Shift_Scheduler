<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambah kolom role untuk membedakan PIC (Supervisor, akses penuh)
     * dan Agent (akses terbatas: lihat jadwal sendiri, ajukan off request).
     * Default 'pic' supaya akun yang sudah ada (Supervisor) otomatis tetap
     * berfungsi seperti semula tanpa perlu migrasi data manual.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['pic', 'agent'])->default('pic')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
