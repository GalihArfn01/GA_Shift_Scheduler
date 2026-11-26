<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambah kolom untuk transparansi keputusan PIC terhadap off request
     * prioritas tinggi (baik dari Agent maupun pic_manual): catatan
     * opsional (alasan setuju/tolak), dan timestamp kapan keputusan
     * dibuat. Ditampilkan ke Agent di riwayat pengajuannya, supaya jelas
     * kapan dan (kalau diisi PIC) mengapa permintaannya disetujui/ditolak.
     */
    public function up(): void
    {
        Schema::table('off_requests', function (Blueprint $table) {
            $table->text('catatan_pic')->nullable()->after('status_approval');
            $table->timestamp('approved_at')->nullable()->after('catatan_pic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('off_requests', function (Blueprint $table) {
            $table->dropColumn(['catatan_pic', 'approved_at']);
        });
    }
};
