<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambah jenis ketiga 'pic_manual' ke enum off_requests.jenis --
     * untuk kasus PIC input off request manual mewakili agent (misal
     * untuk mempertahankan constraint "maksimal 20 hari kerja/bulan"),
     * terpisah dari form_request (self-service, kuota) dan
     * prioritas_tinggi (self-service, approval). Status_approval untuk
     * jenis ini otomatis 'diterima' saat PIC submit, dan diperlakukan
     * sebagai prioritas TINGGI di GA (bobot SC1, sama seperti
     * prioritas_tinggi).
     *
     * MySQL tidak punya ALTER TABLE ... MODIFY COLUMN ENUM langsung lewat
     * Schema Builder Laravel untuk menambah value enum, jadi pakai raw SQL.
     * SQLite (dipakai untuk test) tidak mendukung MODIFY COLUMN dan tidak
     * menegakkan value enum, jadi cukup di-skip di sana.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE off_requests MODIFY COLUMN jenis ENUM('form_request', 'prioritas_tinggi', 'pic_manual') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE off_requests MODIFY COLUMN jenis ENUM('form_request', 'prioritas_tinggi') NOT NULL");
    }
};
