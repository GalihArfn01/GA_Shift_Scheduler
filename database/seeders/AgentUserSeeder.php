<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Membuat akun login dummy (role 'agent') untuk setiap agent hasil
 * AgentSeeder, lalu menautkannya lewat kolom agents.user_id.
 *
 * Dijalankan SETELAH AgentSeeder. Email memakai format
 * "agent.NN@ptxyz.local" dan password default "password123".
 * Semua nilai di sini adalah DATA DUMMY untuk development/testing.
 */
class AgentUserSeeder extends Seeder
{
    public function run(): void
    {
        $agents = Agent::withTrashed()->whereNull('user_id')->get();

        foreach ($agents as $agent) {
            $slug = Str::slug($agent->nama_agent, '.');
            $email = "{$slug}@ptxyz.local";

            $user = User::create([
                'name'     => $agent->nama_agent,
                'email'    => $email,
                'password' => 'password123',
                'role'     => 'agent',
            ]);

            $agent->update(['user_id' => $user->id]);
        }

        $this->command->info(count($agents) . ' akun agent berhasil dibuat (password default: password123).');
    }
}
