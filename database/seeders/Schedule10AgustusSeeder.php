<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class Schedule10AgustusSeeder extends Seeder
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
        $tanggalMulai   = '2026-08-10';
        $tanggalSelesai = '2026-08-16';
        $defaultNama    = '10-16 Agustus 2026';

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
            '2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13',
            '2026-08-14', '2026-08-15', '2026-08-16',
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
     * Matriks jadwal 10–16 Agustus 2026 (dari tabel export aplikasi).
     * Urutan sel: [Sen 10, Sel 11, Rab 12, Kam 13, Jum 14, Sab 15, Min 16].
     */
    protected function matrix(): array
    {
        return [
            ['Agent 01', ['B/INFO', 'X', 'A/CHAT', 'G/CHAT', 'X', 'X', 'A7/CHAT']],
            ['Agent 02', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 03', ['F/CHAT', 'B/WEB', 'X', 'A7/CHAT', 'G/CHAT', 'X', 'X']],
            ['Agent 04', ['G/CHAT', 'A/CHAT', 'B/WEB', 'X', 'X', 'X', 'X']],
            ['Agent 05', ['X', 'G/CHAT', 'G/CHAT', 'A/CHAT', 'X', 'X', 'F/CHAT']],
            ['Agent 06', ['G/CHAT', 'X', 'A7/CHAT', 'X', 'X', 'B/CHAT', 'D/WEB']],
            ['Agent 07', ['X', 'X', 'X', 'X', 'G/CHAT', 'A7/CHAT', 'B/INFO']],
            ['Agent 08', ['X', 'A7/CHAT', 'E', 'F/CHAT', 'X', 'X', 'G/CHAT']],
            ['Agent 09', ['X', 'X', 'X', 'E', 'F/CHAT', 'G/CHAT', 'X']],
            ['Agent 10', ['X', 'X', 'X', 'X', 'X', 'G/CHAT', 'B/WEB']],
            ['Agent 13', ['X', 'X', 'X', 'X', 'X', 'F/CHAT', 'G/CHAT']],
            ['Agent 14', ['X', 'X', 'X', 'X', 'X', 'E/CHAT', 'B/CHAT']],
            ['Agent 15', ['E', 'X', 'F/CHAT', 'X', 'X', 'A7/CHAT', 'G/WEB']],
            ['Agent 16', ['B/WEB', 'X', 'G/CHAT', 'X', 'A/CHAT', 'X', 'F/CHAT']],
            ['Agent 17', ['A7/CHAT', 'G/CHAT', 'X', 'X', 'A7/CHAT', 'F/CHAT', 'X']],
            ['Agent 18', ['A/CHAT', 'F/CHAT', 'X', 'X', 'E', 'X', 'A7/CHAT']],
            ['Agent 19', ['X', 'X', 'G/WEB', 'G/CHAT', 'X', 'X', 'E/CHAT']],
            ['Agent 20', ['X', 'X', 'X', 'C/CHAT', 'E/WEB', 'X', 'G/WEB']],
            ['Agent 21', ['X', 'C/CHAT', 'X', 'B/WEB', 'X', 'G/WEB', 'X']],
            ['Agent 23', ['X', 'G/WEB', 'C/CHAT', 'X', 'G/WEB', 'D/WEB', 'X']],
            ['Agent 24', ['G/WEB', 'X', 'X', 'G/WEB', 'C/CHAT', 'B/WEB', 'X']],
            ['Agent 25', ['C/CHAT', 'X', 'X', 'X', 'B/WEB', 'G/WEB', 'X']],
            ['Agent 26', ['X', 'X', 'X', 'B/INFO', 'D', 'B/INFO', 'X']],
            ['Agent 27', ['X', 'B/INFO', 'G/INFO', 'X', 'X', 'X', 'G/INFO']],
            ['Agent 28', ['G/INFO', 'X', 'X', 'B/INFO', 'B/INFO', 'E/INFO', 'X']],
            ['Agent 29', ['X', 'X', 'X', 'X', 'X', 'G/INFO', 'E/INFO']],
            ['Agent 30', ['X', 'G/INFO', 'B/INFO', 'X', 'B/INFO', 'X', 'X']],
            ['Agent 31', ['X', 'B/INFO', 'B/INFO', 'G/INFO', 'X', 'X', 'B/INFO']],
            ['Agent 32', ['B/INFO', 'E', 'X', 'X', 'G/INFO', 'B/INFO', 'X']],
            ['Agent 38', ['G', 'X', 'X', 'B', 'X', 'G', 'D']],
            ['Agent 39', ['B', 'X', 'G', 'X', 'X', 'E', 'B']],
            ["Agent 40", ['X', 'B', 'X', 'B', 'X', 'F', 'B']],
            ['Agent 41', ['X', 'X', 'X', 'X', 'B', 'B', 'G']],
            ['Agent 42', ['X', 'X', 'X', 'G', 'X', 'D', 'F']],
            ['Agent 43', ['X', 'X', 'B', 'X', 'G', 'X', 'G']],
            ['Agent 44', ['X', 'B', 'X', 'X', 'X', 'F', 'E']],
            ['Agent 45', ['X', 'X', 'B', 'X', 'B', 'G', 'X']],
            ['Agent 46', ['B', 'G', 'X', 'X', 'X', 'B', 'F']],
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
