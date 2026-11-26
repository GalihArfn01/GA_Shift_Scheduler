<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class Schedule17AgustusSeeder extends Seeder
{
    /**
     * Data dummy jadwal untuk satu periode (Agent 01 ... Agent 46).
     *
     * Format sel mengikuti ScheduleExcelExporter::formatAssignmentText():
     *   'X'                  = off
     *   'KODE/LABEL'         = kerja dengan section CHAT / WEB / INFO
     *   'KODE' (tanpa label) = kerja dengan section Inbound
     *
     * Jika periode belum ada, dibuat baru dengan status final. Jadwal lama
     * pada periode tersebut dihapus lalu diisi ulang dari matriks dummy ini.
     */
    public function run(): void
    {
        $tanggalMulai   = '2026-08-17';
        $tanggalSelesai = '2026-08-23';
        $defaultNama    = '17-23 Agustus 2026';

        $periode = SchedulePeriod::where('tanggal_mulai', $tanggalMulai)
            ->where('tanggal_selesai', $tanggalSelesai)
            ->first();

        if ($periode) {
            $this->command->info(
                "Periode #{$periode->id} \"{$periode->nama_periode}\" ditemukan — "
                . 'jadwal akan di-seed ke periode ini.'
            );
        } else {
            $periode = SchedulePeriod::create([
                'nama_periode'   => $defaultNama,
                'tanggal_mulai'  => $tanggalMulai,
                'tanggal_selesai'=> $tanggalSelesai,
                'status'         => 'final',
            ]);
            $this->command->info("Periode baru \"{$defaultNama}\" dibuat (final).");
        }

        // Ganti total: hapus jadwal lama periode ini, lalu isi dari matriks.
        $deleted = Schedule::where('schedule_period_id', $periode->id)->delete();
        if ($deleted > 0) {
            $this->command->info("Menghapus {$deleted} jadwal lama periode ini…");
        }

        $shiftMap   = Shift::pluck('id', 'kode_shift');
        $sectionMap = Section::pluck('id', 'nama_section');

        $dates = [
            '2026-08-17', '2026-08-18', '2026-08-19', '2026-08-20',
            '2026-08-21', '2026-08-22', '2026-08-23',
        ];

        $data = $this->matrix();

        $count   = 0;
        $skipped = [];

        foreach ($data as [$namaAgent, $days]) {
            $agentId = $this->agentId($namaAgent);

            if ($agentId === null) {
                $skipped[] = $namaAgent;
                continue;
            }

            foreach ($dates as $i => $tanggal) {
                [$status, $shiftId, $sectionId] = $this->parseSlot($days[$i], $shiftMap, $sectionMap, $namaAgent);

                Schedule::create([
                    'schedule_period_id' => $periode->id,
                    'agent_id'           => $agentId,
                    'tanggal'            => $tanggal,
                    'shift_id'           => $shiftId,
                    'section_id'         => $sectionId,
                    'status'             => $status,
                ]);
                $count++;
            }
        }

        $kerja = Schedule::where('schedule_period_id', $periode->id)->where('status', 'kerja')->count();
        $off   = Schedule::where('schedule_period_id', $periode->id)->where('status', 'off')->count();

        $this->command->info(
            "Seeded {$count} baris jadwal ke \"{$periode->nama_periode}\" "
            . "({$tanggalMulai} s/d {$tanggalSelesai}): kerja={$kerja}, off={$off}."
        );

        if ($skipped) {
            $this->command->warn('Agent tidak ditemukan (dilewati): ' . implode(', ', $skipped));
        }
    }

    /**
     * Matriks jadwal 17–23 Agustus 2026 (dari tabel export aplikasi).
     * Urutan sel: [Sen 17, Sel 18, Rab 19, Kam 20, Jum 21, Sab 22, Min 23].
     */
    protected function matrix(): array
    {
        return [
            ['Agent 01', ['A7/CHAT', 'F/CHAT', 'X', 'X', 'E/WEB', 'X', 'X']],
            ['Agent 02', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 03', ['X', 'X', 'A7/CHAT', 'A/CHAT', 'F/CHAT', 'G/WEB', 'X']],
            ['Agent 04', ['X', 'A7/CHAT', 'F/CHAT', 'G/WEB', 'X', 'G/CHAT', 'X']],
            ['Agent 05', ['G/CHAT', 'F/CHAT', 'X', 'X', 'G', 'A7/CHAT', 'X']],
            ['Agent 06', ['X', 'A7/CHAT', 'G/CHAT', 'X', 'A7/CHAT', 'X', 'F/CHAT']],
            ['Agent 07', ['G/CHAT', 'X', 'X', 'X', 'G/CHAT', 'X', 'X']],
            ['Agent 08', ['G/CHAT', 'G/CHAT', 'X', 'A7/CHAT', 'X', 'X', 'C/CHAT']],
            ['Agent 09', ['F/CHAT', 'G/WEB', 'A/CHAT', 'X', 'G/CHAT', 'D/WEB', 'X']],
            ['Agent 10', ['F/CHAT', 'A7/CHAT', 'X', 'X', 'X', 'G/CHAT', 'A7/CHAT']],
            ['Agent 13', ['F/CHAT', 'X', 'X', 'X', 'X', 'F/CHAT', 'G/CHAT']],
            ['Agent 14', ['F', 'X', 'X', 'X', 'X', 'F/CHAT', 'A/CHAT']],
            ['Agent 15', ['X', 'A7/CHAT', 'G/WEB', 'X', 'A/CHAT', 'X', 'G/CHAT']],
            ['Agent 16', ['A7/CHAT', 'F/CHAT', 'X', 'G/CHAT', 'X', 'A/CHAT', 'X']],
            ['Agent 17', ['A/CHAT', 'E', 'G/CHAT', 'X', 'X', 'X', 'F/CHAT']],
            ['Agent 18', ['X', 'G/CHAT', 'X', 'B/WEB', 'B/WEB', 'B/INFO', 'X']],
            ['Agent 19', ['X', 'G', 'X', 'G/CHAT', 'X', 'C/CHAT', 'B/WEB']],
            ['Agent 20', ['E/WEB', 'X', 'B/WEB', 'F/CHAT', 'X', 'G', 'X']],
            ['Agent 21', ['G/WEB', 'D/WEB', 'C/CHAT', 'X', 'G/WEB', 'X', 'F/WEB']],
            ['Agent 23', ['G/WEB', 'X', 'X', 'X', 'C/CHAT', 'B/WEB', 'D/WEB']],
            ['Agent 24', ['E/CHAT', 'B/WEB', 'X', 'E', 'X', 'F/WEB', 'X']],
            ['Agent 25', ['B/WEB', 'X', 'E', 'C/CHAT', 'X', 'X', 'G/WEB']],
            ['Agent 26', ['E/INFO', 'B/INFO', 'B/INFO', 'X', 'X', 'X', 'G/INFO']],
            ['Agent 27', ['G', 'X', 'B/INFO', 'B/INFO', 'X', 'G/INFO', 'X']],
            ['Agent 28', ['F', 'B/INFO', 'X', 'G/INFO', 'G/INFO', 'X', 'B/INFO']],
            ['Agent 29', ['B/INFO', 'X', 'X', 'X', 'X', 'E', 'E/INFO']],
            ['Agent 30', ['X', 'B', 'G/INFO', 'B/INFO', 'X', 'E/INFO', 'X']],
            ['Agent 31', ['G/INFO', 'F', 'X', 'X', 'B/INFO', 'B/INFO', 'X']],
            ['Agent 32', ['B/INFO', 'G/INFO', 'X', 'X', 'B/INFO', 'X', 'B/INFO']],
            ['Agent 38', ['B', 'X', 'B', 'X', 'X', 'X', 'G']],
            ['Agent 39', ['X', 'G', 'B', 'X', 'B', 'X', 'X']],
            ["Agent 40", ['G', 'X', 'X', 'G', 'X', 'B', 'X']],
            ['Agent 41', ['X', 'F', 'X', 'X', 'B', 'D', 'X']],
            ['Agent 42', ['G', 'X', 'X', 'X', 'D', 'X', 'B']],
            ['Agent 43', ['F', 'B', 'X', 'X', 'X', 'B', 'X']],
            ['Agent 44', ['B', 'X', 'G', 'X', 'X', 'X', 'D']],
            ['Agent 45', ['B', 'X', 'X', 'B', 'E', 'X', 'E']],
            ['Agent 46', ['E', 'X', 'X', 'B', 'X', 'X', 'B']],
        ];
    }


    protected function agentId(string $nama): ?int
    {
        static $byName = null;
        static $byNorm = null;

        if ($byName === null) {
            $agents = Agent::all();
            $byName = $agents->pluck('id', 'nama_agent');
            $byNorm = [];
            foreach ($agents as $a) {
                $byNorm[$this->normalize($a->nama_agent)] = $a->id;
            }
        }

        if (isset($byName[$nama])) {
            return $byName[$nama];
        }

        return $byNorm[$this->normalize($nama)] ?? null;
    }

    protected function normalize(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
    }

    /** Label section di export → nama section di database. */
    protected const LABEL_TO_SECTION = [
        'CHAT' => 'Chat',
        'WEB'  => 'Website',
        'INFO' => 'Informasi',
    ];

    /**
     * @return array{0: string, 1: ?int, 2: ?int} [status, shift_id, section_id]
     */
    protected function parseSlot(string $slot, $shiftMap, $sectionMap, string $namaAgent): array
    {
        if (strtoupper($slot) === 'X') {
            return ['off', null, null];
        }

        $kode   = $slot;
        $section = 'Inbound'; // kode polos = Inbound (lihat ScheduleExcelExporter)

        if (str_contains($slot, '/')) {
            [$kode, $label] = explode('/', $slot, 2);
            $section = self::LABEL_TO_SECTION[strtoupper($label)] ?? $section;
        }

        if (!isset($shiftMap[$kode])) {
            $this->command->warn("Shift \"{$kode}\" tidak dikenal (agent {$namaAgent}) — dilewati sebagai off.");
            return ['off', null, null];
        }

        return ['kerja', $shiftMap[$kode], $sectionMap[$section] ?? null];
    }
}
