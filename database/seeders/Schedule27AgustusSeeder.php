<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class Schedule27AgustusSeeder extends Seeder
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
        $tanggalMulai   = '2026-08-27';
        $tanggalSelesai = '2026-08-31';
        $defaultNama    = '27-31 Agustus 2026';

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
            '2026-08-27', '2026-08-28', '2026-08-29', '2026-08-30', '2026-08-31',
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
     * Matriks jadwal 27–31 Agustus 2026 (dari tabel export aplikasi).
     * Urutan sel: [Kam 27, Jum 28, Sab 29, Min 30, Sen 31].
     * Kolom 1–2 September (tanggal split) sengaja tidak disertakan.
     */
    protected function matrix(): array
    {
        return [
            ['Agent 01', ['X', 'G/CHAT', 'A7/CHAT', 'X', 'X']],
            ['Agent 02', ['X', 'X', 'X', 'X', 'X']],
            ['Agent 03', ['X', 'X', 'X', 'X', 'X']],
            ['Agent 04', ['X', 'G/WEB', 'C/CHAT', 'X', 'X']],
            ['Agent 05', ['A7/CHAT', 'X', 'J30', 'G/CHAT', 'X']],
            ['Agent 06', ['G/WEB', 'X', 'X', 'X', 'G/CHAT']],
            ['Agent 07', ['X', 'B/WEB', 'X', 'X', 'X']],
            ['Agent 08', ['X', 'A/CHAT', 'G/CHAT', 'I', 'F/CHAT']],
            ['Agent 09', ['G/CHAT', 'C/CHAT', 'I', 'X', 'G/CHAT']],
            ['Agent 10', ['X', 'A7/CHAT', 'X', 'J30', 'X']],
            ['Agent 11', ['X', 'X', 'X', 'A/CHAT', 'X']],
            ['Agent 12', ['A/CHAT', 'X', 'X', 'B/WEB', 'X']],
            ['Agent 13', ['X', 'X', 'X', 'X', 'X']],
            ['Agent 14', ['X', 'E', 'X', 'X', 'X']],
            ['Agent 15', ['X', 'X', 'X', 'X', 'A7/CHAT']],
            ['Agent 16', ['X', 'X', 'A/CHAT', 'E/WEB', 'X']],
            ['Agent 17', ['X', 'G/CHAT', 'X', 'A7/CHAT', 'X']],
            ['Agent 18', ['G/CHAT', 'X', 'E', 'F/CHAT', 'A/CHAT']],
            ['Agent 19', ['X', 'X', 'E/WEB', 'G/CHAT', 'G/WEB']],
            ['Agent 20', ['X', 'X', 'G/CHAT', 'X', 'X']],
            ['Agent 21', ['X', 'X', 'G/WEB', 'X', 'C/CHAT']],
            ['Agent 22', ['B/WEB', 'X', 'X', 'C/CHAT', 'E/WEB']],
            ['Agent 23', ['C/CHAT', 'F/CHAT', 'X', 'G/WEB', 'X']],
            ['Agent 24', ['F/CHAT', 'X', 'B/WEB', 'X', 'X']],
            ['Agent 25', ['X', 'X', 'F/CHAT', 'X', 'B/WEB']],
            ['Agent 26', ['G/INFO', 'X', 'B/INFO', 'X', 'B/INFO']],
            ['Agent 27', ['X', 'X', 'X', 'J30/INFO', 'G/INFO']],
            ['Agent 28', ['B/INFO', 'G', 'X', 'G/INFO', 'B/INFO']],
            ['Agent 29', ['X', 'X', 'X', 'X', 'X']],
            ['Agent 30', ['X', 'G/INFO', 'B/INFO', 'B/INFO', 'X']],
            ['Agent 31', ['B/INFO', 'B/INFO', 'J30/INFO', 'X', 'X']],
            ['Agent 32', ['X', 'B/INFO', 'G/INFO', 'B/INFO', 'X']],
            ['Agent 38', ['X', 'X', 'G', 'B', 'E']],
            ['Agent 39', ['X', 'X', 'X', 'B', 'B']],
            ["Agent 40", ['B', 'X', 'X', 'B', 'B']],
            ['Agent 41', ['X', 'X', 'X', 'X', 'G']],
            ['Agent 42', ['B', 'X', 'B', 'E', 'X']],
            ['Agent 43', ['X', 'B', 'B', 'G', 'X']],
            ['Agent 44', ['E', 'X', 'X', 'X', 'D']],
            ['Agent 45', ['G', 'B', 'B', 'X', 'X']],
            ['Agent 46', ['X', 'X', 'X', 'X', 'X']],
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
