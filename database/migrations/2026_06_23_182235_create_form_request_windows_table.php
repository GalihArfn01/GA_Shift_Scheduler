<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menyimpan lifecycle Form Request: kapan diaktifkan (Step 4 Wizard),
     * untuk rentang tanggal apa, hari mana saja yang aktif, dan kapan
     * otomatis tertutup (+3 hari dari opened_at).
     *
     * schedule_period_id merujuk ke PERIODE SUMBER (periode yang baru
     * di-final-kan oleh PIC), BUKAN periode target Form Request -- karena
     * periode target belum tentu ada/terdaftar di schedule_periods saat
     * Form Request ini dibuka (lihat dokumen rangkuman desain).
     */
    public function up(): void
    {
        Schema::create('form_request_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_period_id')->constrained('schedule_periods')->onDelete('cascade');
            $table->date('tanggal_mulai'); // tanggal_selesai periode sumber + 1 hari
            $table->json('hari_aktif'); // contoh: ["senin","selasa","rabu"]
            $table->dateTime('opened_at');
            $table->dateTime('closes_at'); // opened_at + 3 hari
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_request_windows');
    }
};
