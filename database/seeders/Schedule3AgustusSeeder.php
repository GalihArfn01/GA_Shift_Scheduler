<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class Schedule3AgustusSeeder extends Seeder
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
        $tanggalMulai   = '2026-08-03';
        $tanggalSelesai = '2026-08-09';
        $defaultNama    = '3-9 Agustus 2026';

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
            '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06',
            '2026-08-07', '2026-08-08', '2026-08-09',
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
     * Matriks jadwal 3–9 Agustus 2026 (dari tabel export aplikasi).
     * Urutan sel: [Sen 3, Sel 4, Rab 5, Kam 6, Jum 7, Sab 8, Min 9].
     */
    protected function matrix(): array
    {
        return [
            ['Agent 01', ['E', 'G/CHAT', 'X', 'X', 'B/WEB', 'A7/CHAT', 'X']],
            ['Agent 02', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 03', ['X', 'E', 'X', 'G/CHAT', 'F/CHAT', 'X', 'A7/CHAT']],
            ['Agent 04', ['B/WEB', 'X', 'E', 'X', 'X', 'F/CHAT', 'X']],
            ['Agent 05', ['X', 'X', 'A7/CHAT', 'E', 'X', 'G/WEB', 'X']],
            ['Agent 06', ['F/CHAT', 'X', 'A/CHAT', 'X', 'E', 'X', 'X']],
            ['Agent 07', ['X', 'X', 'X', 'F/CHAT', 'G/CHAT', 'E', 'F/CHAT']],
            ['Agent 08', ['G/WEB', 'X', 'F/CHAT', 'A7/CHAT', 'X', 'X', 'G/CHAT']],
            ['Agent 09', ['X', 'A/CHAT', 'X', 'X', 'G/CHAT', 'X', 'G/CHAT']],
            ['Agent 10', ['X', 'A7/CHAT', 'X', 'G/CHAT', 'X', 'G/CHAT', 'X']],
            ['Agent 11', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 12', ['A/CHAT', 'G/CHAT', 'X', 'X', 'X', 'C/CHAT', 'X']],
            ['Agent 13', ['X', 'X', 'X', 'X', 'X', 'G/CHAT', 'G/WEB']],
            ['Agent 14', ['X', 'X', 'X', 'X', 'X', 'A/CHAT', 'D/WEB']],
            ['Agent 15', ['G/CHAT', 'F/CHAT', 'X', 'X', 'A7/CHAT', 'X', 'F/CHAT']],
            ['Agent 16', ['G/CHAT', 'X', 'X', 'A/CHAT', 'X', 'X', 'X']],
            ['Agent 17', ['A7/CHAT', 'B/WEB', 'X', 'X', 'X', 'X', 'A/CHAT']],
            ['Agent 18', ['X', 'X', 'G/CHAT', 'X', 'A/CHAT', 'D/WEB', 'X']],
            ['Agent 19', ['X', 'X', 'G/CHAT', 'B/WEB', 'X', 'F/CHAT', 'X']],
            ['Agent 20', ['C/CHAT', 'X', 'G/WEB', 'X', 'C/CHAT', 'F/WEB', 'X']],
            ['Agent 21', ['X', 'G/WEB', 'B/WEB', 'X', 'X', 'X', 'C/CHAT']],
            ['Agent 23', ['X', 'C/CHAT', 'X', 'X', 'G/WEB', 'X', 'F/WEB']],
            ['Agent 24', ['X', 'X', 'X', 'C/CHAT', 'E/WEB', 'X', 'B/WEB']],
            ['Agent 25', ['X', 'X', 'C/CHAT', 'G/WEB', 'X', 'B/WEB', 'X']],
            ['Agent 26', ['B/INFO', 'X', 'G/INFO', 'X', 'X', 'X', 'B/INFO']],
            ['Agent 27', ['X', 'X', 'B/INFO', 'X', 'B/INFO', 'G/INFO', 'X']],
            ['Agent 28', ['X', 'G/INFO', 'X', 'B/INFO', 'X', 'X', 'B/INFO']],
            ['Agent 29', ['X', 'X', 'X', 'X', 'X', 'B/INFO', 'E/INFO']],
            ['Agent 30', ['B/INFO', 'B/INFO', 'X', 'G/INFO', 'X', 'B/INFO', 'X']],
            ['Agent 31', ['G/INFO', 'X', 'B/INFO', 'X', 'B/INFO', 'E/INFO', 'X']],
            ['Agent 32', ['X', 'B/INFO', 'X', 'B/INFO', 'G/INFO', 'X', 'G/INFO']],
            ['Agent 38', ['X', 'X', 'X', 'B', 'D', 'B', 'X']],
            ['Agent 39', ['B', 'X', 'X', 'X', 'B', 'X', 'B']],
            ["Agent 40", ['X', 'B', 'X', 'G', 'X', 'X', 'E']],
            ['Agent 41', ['X', 'X', 'X', 'X', 'X', 'G', 'B']],
            ['Agent 42', ['G', 'X', 'X', 'X', 'B', 'X', 'G']],
            ['Agent 43', ['X', 'X', 'B', 'X', 'G', 'B', 'X']],
            ['Agent 44', ['B', 'B', 'G', 'X', 'X', 'X', 'X']],
            ['Agent 45', ['X', 'G', 'X', 'B', 'X', 'X', 'D']],
            ['Agent 46', ['X', 'X', 'B', 'X', 'X', 'D', 'X']],
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
