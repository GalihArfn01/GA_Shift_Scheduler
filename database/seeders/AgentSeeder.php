<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Data dummy: 46 agent dengan kode "Agent NN" (tanpa identitas asli).
     *
     * Level mengikuti pembagian section aplikasi:
     *   01-25 -> level 3 (website/chat)
     *   26-29 -> level 2
     *   30-46 -> level 1
     *
     * Agent 11 sengaja berstatus "skorsing" sebagai contoh kondisi dummy.
     */
    public function run(): void
    {
        foreach (range(1, 46) as $i) {
            Agent::create([
                'nama_agent' => sprintf('Agent %02d', $i),
                'level'      => match (true) {
                    $i <= 25 => 3,
                    $i <= 29 => 2,
                    default  => 1,
                },
                'status'     => $i === 11 ? 'skorsing' : 'aktif',
                // HC7: shift malam hanya boleh diisi agent laki-laki.
                'jenis_kelamin' => $i % 2 === 1 ? 'L' : 'P',
            ]);
        }
    }
}
