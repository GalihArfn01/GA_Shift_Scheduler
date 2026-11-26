<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Karena route 'register' dihapus (SPV tidak mendaftar sendiri),
     * akun SPV pertama dibuat lewat seeder ini. Untuk menambah SPV
     * baru di kemudian hari, gunakan php artisan tinker atau buat
     * menu "Kelola Akun" terpisah (di luar scope awal).
     *
     * Akun ini sepenuhnya dummy (nama, email, dan password).
     */
    public function run(): void
    {
        User::create([
            'name'     => 'Supervisor PT XYZ',
            'email'    => 'supervisor@ptxyz.local',
            'password' => Hash::make('password123'), // GANTI sebelum produksi
            'role'     => 'pic',
        ]);
    }
}
