<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Durasi kerja tiap shift = 8 jam (sesuai dokumen perencanaan).
     * jam_pulang dihitung otomatis: jam_masuk + 8 jam.
     */
    public function run(): void
    {
        $shifts = [
            ['kode_shift' => 'A7', 'jam_masuk' => '07:00:00', 'jam_pulang' => '15:00:00'],
            ['kode_shift' => 'A',  'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00'],
            ['kode_shift' => 'B',  'jam_masuk' => '09:00:00', 'jam_pulang' => '17:00:00'],
            ['kode_shift' => 'C',  'jam_masuk' => '10:00:00', 'jam_pulang' => '18:00:00'],
            ['kode_shift' => 'D',  'jam_masuk' => '11:00:00', 'jam_pulang' => '19:00:00'],
            ['kode_shift' => 'E',  'jam_masuk' => '12:00:00', 'jam_pulang' => '20:00:00'],
            ['kode_shift' => 'F',  'jam_masuk' => '13:00:00', 'jam_pulang' => '21:00:00'],
            ['kode_shift' => 'G',  'jam_masuk' => '14:00:00', 'jam_pulang' => '22:00:00'],
        ];

        foreach ($shifts as $shift) {
            DB::table('shifts')->insert([
                'kode_shift'  => $shift['kode_shift'],
                'jam_masuk'   => $shift['jam_masuk'],
                'jam_pulang'  => $shift['jam_pulang'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
