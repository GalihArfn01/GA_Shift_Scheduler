<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GeneticAlgorithmJobRunner
{
    /**
     * Jalankan GA untuk satu periode, update status job, dan simpan hasil jadwal.
     */
    public function run(int $periodId, int $jobId, ?array $config = null): void
    {
        DB::table('schedule_job_statuses')
            ->where('id', $jobId)
            ->update(['status' => 'running', 'started_at' => now(), 'updated_at' => now()]);

        try {
            $period = SchedulePeriod::findOrFail($periodId);

            // Pertahanan terakhir: jangan pernah menimpa jadwal yang sudah
            // final. Race paling berbahaya adalah job di-spawn saat periode
            // masih draft, lalu periode di-final-kan sebelum job selesai
            // berjalan — jadwal yang sudah difinalkan harus tetap utuh.
            // (generate() di controller juga memblokir, tapi job bisa
            // dipanggil langsung via ga:run / queue tanpa lewat controller.)
            if ($period->isFinal()) {
                DB::table('schedule_job_statuses')
                    ->where('id', $jobId)
                    ->update([
                        'status'        => 'failed',
                        'error_message' => 'Periode sudah final — jadwal tidak bisa di-generate ulang.',
                        'finished_at'   => now(),
                        'updated_at'    => now(),
                    ]);

                return;
            }

            // Ambil konfigurasi GA dari baris job di DB bila tidak dikirim lewat
            // CLI. Ini menghindari escaping JSON yang rawan rusak saat proses
            // di-spawn lewat cmd.exe / shell di Windows.
            if ($config === null) {
                $stored = DB::table('schedule_job_statuses')
                    ->where('id', $jobId)
                    ->value('ga_config');

                if ($stored) {
                    $decoded = json_decode($stored, true);
                    $config  = is_array($decoded) ? $decoded : null;
                }
            }

            $scheduler = new GeneticAlgorithmScheduler();

            if ($config) {
                $scheduler->applyConfig($config);
            }

            $result = $scheduler->run($period);

            $this->saveGeneratedSchedule($period, $result['chromosome']);

            DB::table('schedule_job_statuses')
                ->where('id', $jobId)
                ->update([
                    'status'         => 'done',
                    'result_summary' => json_encode([
                        'generations_run'        => $result['generations_run'],
                        'unfilled_count'         => $result['unfilled_count'],
                        'total_slots'            => $result['total_slots'],
                        'detail'                 => $result['detail'],
                        'off_request_violations' => $result['off_request_violations'],
                        'off_request_accepted'   => $result['off_request_accepted'],
                    ]),
                    'finished_at' => now(),
                    'updated_at'  => now(),
                ]);
        } catch (\Throwable $e) {
            DB::table('schedule_job_statuses')
                ->where('id', $jobId)
                ->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'finished_at'   => now(),
                    'updated_at'    => now(),
                ]);

            throw $e;
        }
    }

    protected function saveGeneratedSchedule(SchedulePeriod $period, array $chromosome): void
    {
        // Hapus-lalu-insert dalam satu transaksi: kalau proses GA terputus di
        // tengah (crash/DB timeout), periode tidak akan tertinggal dengan
        // jadwal separuh — hasil lama tetap utuh sampai penggantinya berhasil.
        DB::transaction(function () use ($period, $chromosome) {
            Schedule::where('schedule_period_id', $period->id)->delete();

            $tanggalMulai  = Carbon::parse($period->tanggal_mulai);
            $agentIdsAktif = Agent::aktif()->pluck('id')->all();

            $grid = [];
            foreach ($chromosome as $gen) {
                if ($gen['agent_id'] !== null) {
                    $grid[$gen['agent_id']][$gen['hari_index']] = [
                        'shift_id'   => $gen['shift_id'],
                        'section_id' => $gen['section_id'],
                    ];
                }
            }

            $rows = [];
            $now  = now();

            foreach ($agentIdsAktif as $agentId) {
                for ($hariIndex = 0; $hariIndex < 7; $hariIndex++) {
                    $tanggal    = $tanggalMulai->copy()->addDays($hariIndex)->format('Y-m-d');
                    $assignment = $grid[$agentId][$hariIndex] ?? null;

                    $rows[] = [
                        'schedule_period_id' => $period->id,
                        'agent_id'           => $agentId,
                        'tanggal'            => $tanggal,
                        'shift_id'           => $assignment['shift_id'] ?? null,
                        'section_id'         => $assignment['section_id'] ?? null,
                        'status'             => $assignment ? 'kerja' : 'off',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                Schedule::insert($chunk);
            }
        });
    }
}
