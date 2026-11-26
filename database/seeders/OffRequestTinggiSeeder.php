<?php

namespace Database\Seeders;

use App\Models\OffRequest;
use Illuminate\Database\Seeder;

class OffRequestTinggiSeeder extends Seeder
{
    /**
     * Off request prioritas tinggi (jenis=prioritas_tinggi, status=diterima).
     * Tanggal mengikuti periode 17–23 Agustus 2026.
     *
     * Aman dijalankan berulang: updateOrCreate per (agent_id, tanggal, jenis),
     * jadi baris lama yang masih 'pending' akan di-update menjadi 'diterima'
     * tanpa membuat duplikat.
     */
    public function run(): void
    {
        $data = [
            ['agent_id' => 2,  'tanggal' => ['17', '18', '19', '20', '21', '22', '23']], // Agent 02
            ['agent_id' => 3,  'tanggal' => ['17', '18']],                                // Agent 03
            ['agent_id' => 4,  'tanggal' => ['23']],                                      // Agent 04
            ['agent_id' => 6,  'tanggal' => ['17', '20']],                                // Agent 06
            ['agent_id' => 7,  'tanggal' => ['17', '18', '19']],                          // Agent 07
            ['agent_id' => 8,  'tanggal' => ['22']],                                      // Agent 08
            ['agent_id' => 9,  'tanggal' => ['23']],                                      // Agent 09
            ['agent_id' => 10, 'tanggal' => ['17', '19', '21']],                          // Agent 10
            ['agent_id' => 13, 'tanggal' => ['18', '19', '20', '21']],                    // Agent 13
            ['agent_id' => 14, 'tanggal' => ['18', '19', '20', '21']],                    // Agent 14
            ['agent_id' => 29, 'tanggal' => ['18', '19', '20', '21']],                    // Agent 29
            ['agent_id' => 30, 'tanggal' => ['17', '23']],                                // Agent 30
            ['agent_id' => 41, 'tanggal' => ['18', '19', '20']],                          // Agent 41
            ['agent_id' => 44, 'tanggal' => ['20']],                                      // Agent 44
            ['agent_id' => 46, 'tanggal' => ['18']],                                      // Agent 46
        ];

        $now   = now();
        $count = 0;

        foreach ($data as $entry) {
            foreach ($entry['tanggal'] as $day) {
                OffRequest::updateOrCreate(
                    [
                        'agent_id' => $entry['agent_id'],
                        'tanggal'  => '2026-08-' . $day,
                        'jenis'    => 'prioritas_tinggi',
                    ],
                    [
                        'status_approval'  => 'diterima',
                        'catatan_pic'      => null,
                        'approved_at'      => $now,
                        'kelompok_section' => null,
                        'keterangan'       => null,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Seeded {$count} off request prioritas tinggi, status diterima (17–23 Agustus 2026).");
    }
}
