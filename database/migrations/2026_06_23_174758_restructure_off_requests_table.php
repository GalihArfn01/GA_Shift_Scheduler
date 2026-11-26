<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restrukturisasi off_requests untuk mendukung sistem 2 jalur pengajuan
     * off yang menggantikan mekanisme lama (1 form, langsung tersimpan
     * tanpa kuota/approval):
     *
     * - Jalur 1 "form_request" (prioritas rendah): self-service Agent,
     *   dibatasi kuota 3 slot/hari per kelompok_section, otomatis
     *   status_approval='diterima' begitu lolos kuota (tanpa approval PIC).
     * - Jalur 2 "prioritas_tinggi": diajukan Agent (atau diinput PIC
     *   mewakili agent), wajib alasan, status_approval mulai 'pending'
     *   sampai PIC memutuskan diterima/ditolak.
     *
     * Kolom 'prioritas' DIHAPUS karena redundan -- prioritas sekarang
     * selalu mengikuti 'jenis' (form_request selalu rendah, prioritas_tinggi
     * selalu tinggi), tidak perlu disimpan terpisah.
     *
     * Tidak perlu migrasi data lama karena off_requests yang ada sekarang
     * masih data dummy/percobaan (keputusan eksplisit, lihat dokumen
     * rangkuman desain).
     */
    public function up(): void
    {
        Schema::table('off_requests', function (Blueprint $table) {
            $table->dropColumn('prioritas');

            $table->enum('jenis', ['form_request', 'prioritas_tinggi'])
                ->after('tanggal');

            $table->enum('status_approval', ['pending', 'diterima', 'ditolak'])
                ->default('pending')
                ->after('jenis');

            $table->enum('kelompok_section', ['inbound_informasi', 'website_chat'])
                ->nullable()
                ->after('status_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('off_requests', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'status_approval', 'kelompok_section']);
            $table->enum('prioritas', ['tinggi', 'rendah'])->after('tanggal');
        });
    }
};
