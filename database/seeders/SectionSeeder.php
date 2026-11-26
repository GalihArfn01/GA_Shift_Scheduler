<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = ['Inbound', 'Informasi', 'Website', 'Chat'];

        foreach ($sections as $namaSection) {
            DB::table('sections')->insert([
                'nama_section' => $namaSection,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
