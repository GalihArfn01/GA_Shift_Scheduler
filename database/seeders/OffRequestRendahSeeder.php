<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\OffRequest;
use Illuminate\Database\Seeder;

class OffRequestRendahSeeder extends Seeder
{
    /**
     * Off request RENDAH (form_request, status=diterima) dummy untuk
     * periode 17–23 Agustus 2026 — periode yang sama dengan
     * OffRequestTinggiSeeder, TANPA tabrakan:
     *
     * - agent yang dipilih TIDAK punya off request apa pun (tinggi/manual)
     *   di tanggal yang sama pada periode itu;
     * - kuota form_request dijaga: maksimal 3 per kelompok_section per
     *   tanggal (sama seperti aturan aplikasi);
     * - kelompok_section ditentukan dari level agent (level 3 →
     *   website_chat, selain itu → inbound_informasi).
     *
     * Aman dijalankan berulang:
     * - guard 1: lewati kalau agent sudah punya off request di tanggal itu
     *   (jenis apa pun);
     * - guard 2: lewati kalau kuota kelompok/tanggal sudah penuh;
     * - updateOrCreate per (agent_id, tanggal, jenis) mencegah duplikat.
     */
    public function run(): void
    {
        $data = [
            // Kelompok inbound_informasi (level 1 & 2)
            ['agent_id' => 38, 'tanggal' => ['17', '21']], // Agent 38
            ['agent_id' => 39, 'tanggal' => ['17', '21']], // Agent 39
            ['agent_id' => 40, 'tanggal' => ['17', '22']], // Agent 40
            ['agent_id' => 42, 'tanggal' => ['18', '22']], // Agent 42
            ['agent_id' => 43, 'tanggal' => ['18', '23']], // Agent 43
            ['agent_id' => 45, 'tanggal' => ['18', '23']], // Agent 45
            ['agent_id' => 26, 'tanggal' => ['19']],       // Agent 26
            ['agent_id' => 27, 'tanggal' => ['19']],       // Agent 27
            ['agent_id' => 28, 'tanggal' => ['19']],       // Agent 28
            ['agent_id' => 31, 'tanggal' => ['20']],       // Agent 31
            ['agent_id' => 32, 'tanggal' => ['20']],       // Agent 32

            // Kelompok website_chat (level 3)
            ['agent_id' => 1,  'tanggal' => ['17', '21']], // Agent 01
            ['agent_id' => 5,  'tanggal' => ['17', '22']], // Agent 05
            ['agent_id' => 15, 'tanggal' => ['17', '22']], // Agent 15
            ['agent_id' => 16, 'tanggal' => ['18', '23']], // Agent 16
            ['agent_id' => 17, 'tanggal' => ['18', '23']], // Agent 17
            ['agent_id' => 18, 'tanggal' => ['18']],       // Agent 18
            ['agent_id' => 19, 'tanggal' => ['19']],       // Agent 19
            ['agent_id' => 20, 'tanggal' => ['19']],       // Agent 20
            ['agent_id' => 21, 'tanggal' => ['19']],       // Agent 21
            ['agent_id' => 23, 'tanggal' => ['20']],       // Agent 23
            ['agent_id' => 24, 'tanggal' => ['20']],       // Agent 24
            ['agent_id' => 25, 'tanggal' => ['21']],       // Agent 25
        ];

        $added   = 0;
        $skipped = 0;

        foreach ($data as $entry) {
            $agent    = Agent::find($entry['agent_id']);
            $kelompok = $agent && $agent->level == 3 ? 'website_chat' : 'inbound_informasi';

            foreach ($entry['tanggal'] as $day) {
                $tanggal = '2026-08-' . $day;

                // Guard 1: agent sudah punya off request (jenis apa pun) di tanggal itu
                $sudahAda = OffRequest::where('agent_id', $entry['agent_id'])
                    ->where('tanggal', $tanggal)
                    ->exists();
                if ($sudahAda) {
                    $skipped++;
                    continue;
                }

                // Guard 2: kuota form_request 3/kelompok/tanggal
                $terisi = OffRequest::where('tanggal', $tanggal)
                    ->where('jenis', 'form_request')
                    ->where('kelompok_section', $kelompok)
                    ->where('status_approval', 'diterima')
                    ->count();
                if ($terisi >= 3) {
                    $skipped++;
                    continue;
                }

                OffRequest::updateOrCreate(
                    [
                        'agent_id' => $entry['agent_id'],
                        'tanggal'  => $tanggal,
                        'jenis'    => 'form_request',
                    ],
                    [
                        'status_approval'  => 'diterima',
                        'kelompok_section' => $kelompok,
                        'catatan_pic'      => null,
                        'approved_at'      => null,
                        'keterangan'       => null,
                    ]
                );
                $added++;
            }
        }

        $this->command->info("Seeded {$added} off request rendah (form_request) 17–23 Agustus 2026." . ($skipped ? " Dilewati {$skipped} (tabrakan/kuota)." : ''));
    }
}
