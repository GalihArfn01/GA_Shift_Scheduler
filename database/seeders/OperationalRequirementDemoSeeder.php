<?php

namespace Database\Seeders;

use App\Models\SchedulePeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Kebutuhan operasional dummy untuk satu periode contoh.
 *
 * Meniru pola template "17 Agustus" (16 kombinasi shift × section per hari)
 * lalu diulang untuk tujuh hari dalam periode 17–23 Agustus 2026.
 * Dipakai supaya algoritma genetika punya slot nyata saat dijalankan/dibenchmark.
 *
 * Aman dijalankan berulang: kebutuhan lama periode ini dihapus lalu diisi ulang.
 */
class OperationalRequirementDemoSeeder extends Seeder
{
    protected const PERIODE_ID = 4;

    protected const HARI = [
        'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu',
    ];

    public function run(): void
    {
        if (!SchedulePeriod::find(self::PERIODE_ID)) {
            SchedulePeriod::create([
                'id'             => self::PERIODE_ID,
                'nama_periode'   => '17-23 Agustus 2026',
                'tanggal_mulai'  => '2026-08-17',
                'tanggal_selesai'=> '2026-08-23',
                'status'         => 'final',
            ]);
        }

        $details = DB::table('operational_template_details')
            ->join('operational_templates', 'operational_templates.id', '=', 'operational_template_details.operational_template_id')
            ->where('operational_templates.nama_template', '17 Agustus')
            ->get(['operational_template_details.shift_id', 'operational_template_details.section_id', 'operational_template_details.jumlah_kebutuhan']);

        if ($details->isEmpty()) {
            $this->command->warn('Template "17 Agustus" belum ada — jalankan OperationalTemplate17AgustusSeeder lebih dulu.');

            return;
        }

        $now = now();
        $count = 0;

        DB::transaction(function () use ($details, $now, &$count) {
            DB::table('operational_requirements')->where('schedule_period_id', self::PERIODE_ID)->delete();

            foreach (self::HARI as $hari) {
                foreach ($details as $detail) {
                    DB::table('operational_requirements')->insert([
                        'schedule_period_id' => self::PERIODE_ID,
                        'hari_ke'            => $hari,
                        'shift_id'           => $detail->shift_id,
                        'section_id'         => $detail->section_id,
                        'jumlah_kebutuhan'   => $detail->jumlah_kebutuhan,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                    $count++;
                }
            }
        });

        $this->command->info("Seeded {$count} baris kebutuhan operasional dummy untuk periode #" . self::PERIODE_ID . '.');
    }
}
