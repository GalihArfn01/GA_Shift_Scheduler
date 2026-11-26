<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan soft delete ke tabel agents, supaya agent yang resign
     * bisa "dihapus" dari tampilan tanpa benar-benar menghapus baris di
     * database -- riwayat di tabel schedules dan off_requests (yang
     * berelasi via foreign key ke agents.id) tetap aman dan tidak pernah
     * berisiko konflik constraint atau hilang akibat cascade delete.
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
