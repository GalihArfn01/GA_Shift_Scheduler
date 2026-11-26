<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class Schedule1AgustusSeeder extends Seeder
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
        $tanggalMulai   = '2026-07-27';
        $tanggalSelesai = '2026-08-02';
        $defaultNama    = '27 Juli - 2 Agustus 2026';

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
            '2026-07-27', '2026-07-28', '2026-07-29', '2026-07-30', '2026-07-31',
            '2026-08-01', '2026-08-02',
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
     * Matriks jadwal 27 Juli – 2 Agustus 2026 (dari tabel export aplikasi).
     * Urutan sel: [Sen 27, Sel 28, Rab 29, Kam 30, Jum 31, Sab 1, Min 2].
     * Hari 27–31 Juli sengaja 'X' semua (off), sesuai instruksi; hari 1–2
     * Agustus berisi jadwal kerja dari tabel sumber.
     */
    protected function matrix(): array
    {
        return [
            ['Agent 01', ['X', 'X', 'X', 'X', 'X', 'X', 'F/CHAT']],
            ['Agent 02', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 03', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 04', ['X', 'X', 'X', 'X', 'X', 'F/CHAT', 'X']],
            ['Agent 05', ['X', 'X', 'X', 'X', 'X', 'F/CHAT', 'A/CHAT']],
            ['Agent 06', ['X', 'X', 'X', 'X', 'X', 'A7/CHAT', 'F/CHAT']],
            ['Agent 07', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 08', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 09', ['X', 'X', 'X', 'X', 'X', 'X', 'A7/CHAT']],
            ['Agent 10', ['X', 'X', 'X', 'X', 'X', 'X', 'G/CHAT']],
            ['Agent 11', ['X', 'X', 'X', 'X', 'X', 'G/CHAT', 'X']],
            ['Agent 12', ['X', 'X', 'X', 'X', 'X', 'X', 'F/WEB']],
            ['Agent 13', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 14', ['X', 'X', 'X', 'X', 'X', 'G/CHAT', 'X']],
            ['Agent 15', ['X', 'X', 'X', 'X', 'X', 'G/WEB', 'X']],
            ['Agent 16', ['X', 'X', 'X', 'X', 'X', 'A/CHAT', 'G/CHAT']],
            ['Agent 17', ['X', 'X', 'X', 'X', 'X', 'C/CHAT', 'X']],
            ['Agent 18', ['X', 'X', 'X', 'X', 'X', 'X', 'B/WEB']],
            ['Agent 19', ['X', 'X', 'X', 'X', 'X', 'X', 'C/CHAT']],
            ['Agent 20', ['X', 'X', 'X', 'X', 'X', 'B/WEB', 'X']],
            ['Agent 21', ['X', 'X', 'X', 'X', 'X', 'X', 'G/WEB']],
            ['Agent 22', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 23', ['X', 'X', 'X', 'X', 'X', 'F/WEB', 'X']],
            ['Agent 24', ['X', 'X', 'X', 'X', 'X', 'D/WEB', 'X']],
            ['Agent 25', ['X', 'X', 'X', 'X', 'X', 'X', 'D/WEB']],
            ['Agent 26', ['X', 'X', 'X', 'X', 'X', 'X', 'B/INFO']],
            ['Agent 27', ['X', 'X', 'X', 'X', 'X', 'B/INFO', 'X']],
            ['Agent 28', ['X', 'X', 'X', 'X', 'X', 'B/INFO', 'X']],
            ['Agent 29', ['X', 'X', 'X', 'X', 'X', 'G/INFO', 'B/INFO']],
            ['Agent 30', ['X', 'X', 'X', 'X', 'X', 'E/INFO', 'X']],
            ['Agent 31', ['X', 'X', 'X', 'X', 'X', 'X', 'B/INFO']],
            ['Agent 32', ['X', 'X', 'X', 'X', 'X', 'X', 'G/INFO']],
            ['Agent 38', ['X', 'X', 'X', 'X', 'X', 'X', 'X']],
            ['Agent 39', ['X', 'X', 'X', 'X', 'X', 'G', 'X']],
            ["Agent 40", ['X', 'X', 'X', 'X', 'X', 'X', 'G']],
            ['Agent 41', ['X', 'X', 'X', 'X', 'X', 'B', 'E']],
            ['Agent 42', ['X', 'X', 'X', 'X', 'X', 'B', 'X']],
            ['Agent 43', ['X', 'X', 'X', 'X', 'X', 'X', 'D']],
            ['Agent 44', ['X', 'X', 'X', 'X', 'X', 'X', 'B']],
            ['Agent 45', ['X', 'X', 'X', 'X', 'X', 'D', 'X']],
            ['Agent 46', ['X', 'X', 'X', 'X', 'X', 'E', 'B']],
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
