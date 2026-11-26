<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalTemplate17AgustusSeeder extends Seeder
{
    /**
     * Template kebutuhan operasional khusus 17 Agustus (hari libur nasional).
     *
     * Struktur detail meniru data lokal yang sudah ada: 16 baris kombinasi
     * shift × section dengan jumlah kebutuhan masing-masing.
     *
     * Idempotent — aman dijalankan berulang:
     * - template dicari berdasarkan nama_template (update kalau ada,
     *   insert kalau belum);
     * - detail dihapus lalu di-insert ulang di dalam DB::transaction
     *   (pola yang sama dengan penyimpanan template di aplikasi), jadi
     *   tidak pernah ada duplikat maupun detail setengah jadi.
     */
    public function run(): void
    {
        $details = [
            ['shift_id' => 3, 'section_id' => 1, 'jumlah' => 2], // B  · Inbound
            ['shift_id' => 5, 'section_id' => 1, 'jumlah' => 1], // D  · Inbound
            ['shift_id' => 6, 'section_id' => 1, 'jumlah' => 1], // E  · Inbound
            ['shift_id' => 7, 'section_id' => 1, 'jumlah' => 2], // F  · Inbound
            ['shift_id' => 8, 'section_id' => 1, 'jumlah' => 2], // G  · Inbound
            ['shift_id' => 3, 'section_id' => 2, 'jumlah' => 2], // B  · Informasi
            ['shift_id' => 6, 'section_id' => 2, 'jumlah' => 1], // E  · Informasi
            ['shift_id' => 8, 'section_id' => 2, 'jumlah' => 1], // G  · Informasi
            ['shift_id' => 3, 'section_id' => 3, 'jumlah' => 1], // B  · Website
            ['shift_id' => 5, 'section_id' => 3, 'jumlah' => 1], // D  · Website
            ['shift_id' => 8, 'section_id' => 3, 'jumlah' => 2], // G  · Website
            ['shift_id' => 1, 'section_id' => 4, 'jumlah' => 2], // A7 · Chat
            ['shift_id' => 4, 'section_id' => 4, 'jumlah' => 1], // C  · Chat
            ['shift_id' => 6, 'section_id' => 4, 'jumlah' => 1], // E  · Chat
            ['shift_id' => 7, 'section_id' => 4, 'jumlah' => 2], // F  · Chat
            ['shift_id' => 8, 'section_id' => 4, 'jumlah' => 2], // G  · Chat
        ];

        DB::transaction(function () use ($details) {
            $now = now();

            // Cari template berdasarkan nama — update kalau ada, insert kalau belum
            $template = DB::table('operational_templates')
                ->where('nama_template', '17 Agustus')
                ->first();

            if ($template) {
                $id = $template->id;
                DB::table('operational_templates')
                    ->where('id', $id)
                    ->update(['updated_at' => $now]);
            } else {
                $id = DB::table('operational_templates')->insertGetId([
                    'nama_template' => '17 Agustus',
                    'keterangan'    => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            // Hapus-lalu-insert-ulang detail di dalam transaction
            DB::table('operational_template_details')
                ->where('operational_template_id', $id)
                ->delete();

            foreach ($details as $d) {
                DB::table('operational_template_details')->insert([
                    'operational_template_id' => $id,
                    'shift_id'                => $d['shift_id'],
                    'section_id'              => $d['section_id'],
                    'jumlah_kebutuhan'        => $d['jumlah'],
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ]);
            }
        });

        $this->command->info('Seeded template operasional "17 Agustus" dengan ' . count($details) . ' detail kebutuhan.');
    }
}
