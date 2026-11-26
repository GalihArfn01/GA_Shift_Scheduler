<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Jenis kelamin agent: L = laki-laki, P = perempuan.
            // Nullable karena data lama belum punya nilai ini dan akan
            // dilengkapi manual lewat form edit Agent oleh PIC.
            $table->enum('jenis_kelamin', ['L', 'P'])
                ->nullable()
                ->after('status');
        });

        // Level baru "Training" disimpan sebagai '0' (bukan string panjang)
        // supaya seluruh kode existing yang memperlakukan level secara
        // numerik & terurut tetap valid: (int) cast, level >= 2 untuk toggle
        // Inbound, orderByDesc('level') di exporter, dst. Training memang
        // tingkatan paling bawah sebelum Level 1.
        // ENUM didefinisikan dengan '0' di urutan pertama agar ORDER BY
        // kolom enum menghasilkan hierarki benar: 0 < 1 < 2 < 3.
        Schema::table('agents', function (Blueprint $table) {
            $table->enum('level', ['0', '1', '2', '3'])->change();
        });

        // Section baru khusus agent level Training (idempotent).
        DB::table('sections')->insertOrIgnore([
            'nama_section' => 'Training',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('jenis_kelamin');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->enum('level', ['1', '2', '3'])->change();
        });

        DB::table('sections')
            ->where('nama_section', 'Training')
            ->delete();
    }
};
