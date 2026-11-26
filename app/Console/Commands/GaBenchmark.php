<?php

namespace App\Console\Commands;

use App\Models\SchedulePeriod;
use App\Services\GeneticAlgorithmScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GaBenchmark extends Command
{
    /**
     * php artisan ga:benchmark {period} {--runs=3} {--configs=JSON} {--out=}
     *
     * Menjalankan GA berulang dengan beberapa kombinasi parameter dan
     * mencatat metrik + DURASI REAL per run. TIDAK menyimpan jadwal apa
     * pun (hanya mengevaluasi), jadi aman dijalankan di data produksi.
     *
     * --configs: JSON array, tiap elemen { "label": "...", "params": {...} }.
     *   Contoh:
     *   [{"label":"A","params":{"population_size":100}},
     *    {"label":"B","params":{"population_size":100,"max_generations":400}}]
     *
     * Hasil ditulis satu objek JSON per baris (JSONL) ke --out
     * (default: storage/logs/ga-benchmark-{period}-{ts}.jsonl) supaya
     * aman kalau proses terputus — baris yang sudah tercatat tidak hilang.
     */
    protected $signature = 'ga:benchmark {period : ID SchedulePeriod} {--runs=3 : Jumlah run per kombinasi} {--configs= : JSON daftar kombinasi parameter} {--out= : Path file hasil (default: storage/logs/ga-benchmark-PERIOD-TIMESTAMP.jsonl)}';

    protected $description = 'Jalankan GA berulang dengan kombinasi parameter, catat metrik + durasi real (tanpa menyimpan jadwal)';

    public function handle(): int
    {
        // Proses ini bisa berjalan lama (puluhan menit - jam). Pastikan tidak
        // terpotong oleh max_execution_time maupun memory_limit PHP, apa pun
        // default php.ini server (beberapa host menetapkan nilai global yang
        // ikut berlaku untuk CLI).
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $period = SchedulePeriod::findOrFail((int) $this->argument('period'));
        $runs   = max(1, (int) $this->option('runs'));

        // Lock sederhana: mencegah dua benchmark berjalan bersamaan (misal
        // cron tiap menit memulai duplikat). Kalau lock masih ada dan berumur
        // < 6 jam, anggap benchmark sedang/baru berjalan → lewati.
        $lockFile = storage_path('logs/ga-benchmark.lock');
        if (file_exists($lockFile) && (time() - (int) @filemtime($lockFile)) < 21600) {
            $this->error('Benchmark lain sedang berjalan atau baru selesai (lock: ' . $lockFile . '). Dilewati.');
            return self::SUCCESS;
        }
        file_put_contents($lockFile, date('c'), LOCK_EX);

        try {
            $this->runBenchmark($period, $runs);
        } finally {
            @unlink($lockFile);
        }

        return self::SUCCESS;
    }

    protected function runBenchmark(SchedulePeriod $period, int $runs): void
    {
        $configs = $this->parseConfigs($this->option('configs'));
        $out     = $this->option('out')
            ?: storage_path('logs/ga-benchmark-' . $period->id . '-' . date('Ymd-His') . '.jsonl');

        $this->info('Benchmark GA · periode: ' . $period->nama_periode . ' (' . $period->id . ')');
        $this->info(count($configs) . ' kombinasi × ' . $runs . ' run = ' . (count($configs) * $runs) . ' eksekusi');
        $this->info('Output: ' . $out);

        $total = 0;
        $mulaiKeseluruhan = microtime(true);

        foreach ($configs as $cfg) {
            $label  = $cfg['label'] ?? ('kombinasi-' . ($total + 1));
            $params = is_array($cfg['params'] ?? null) ? $cfg['params'] : [];

            for ($i = 1; $i <= $runs; $i++) {
                $row = [
                    'label'            => $label,
                    'run'              => $i,
                    'params'           => $params,
                    'started_at'       => date('Y-m-d H:i:s'),
                    'duration_seconds' => null,
                    'fitness'          => null,
                    'total_penalty'    => null,
                    'unfilled_count'   => null,
                    'total_slots'      => null,
                    'generations_run'  => null,
                    'error'            => null,
                ];

                try {
                    $scheduler = new GeneticAlgorithmScheduler();
                    if ($params) {
                        $scheduler->applyConfig($params);
                    }
                    // Catat parameter LENGKAP yang benar-benar dipakai (default + override),
                    // bukan hanya delta — biar data hasil bisa dibaca tanpa menebak default.
                    $params = $scheduler->getConfig();

                    $start  = microtime(true);
                    $result = $scheduler->run($period);
                    $dur    = microtime(true) - $start;

                    $tp = (float) ($result['detail']['total_penalty'] ?? 0);
                    $row['duration_seconds'] = round($dur, 3);
                    $row['fitness']          = round((float) ($result['detail']['fitness'] ?? (1 / (1 + $tp))), 6);
                    $row['total_penalty']    = round($tp, 2);
                    $row['unfilled_count']   = (int) ($result['unfilled_count'] ?? 0);
                    $row['total_slots']      = (int) ($result['total_slots'] ?? 0);
                    $row['generations_run']  = (int) ($result['generations_run'] ?? 0);
                } catch (\Throwable $e) {
                    $row['error'] = $e->getMessage();
                }

                File::append($out, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

                if ($row['error']) {
                    $this->error(sprintf('  [%s] run %d/%d GAGAL: %s', $label, $i, $runs, $row['error']));
                } else {
                    $this->info(sprintf(
                        '  [%s] run %d/%d selesai · %.2f dtk · fitness %.4f · penalty %.2f · unfilled %d · gen %d',
                        $label,
                        $i,
                        $runs,
                        $row['duration_seconds'],
                        $row['fitness'],
                        $row['total_penalty'],
                        $row['unfilled_count'],
                        $row['generations_run']
                    ));
                }
                $total++;
            }
        }

        $totalDurasi = microtime(true) - $mulaiKeseluruhan;
        $this->info("Selesai: {$total} run tercatat di " . $out . ' (' . round($totalDurasi, 1) . ' dtk total)');
    }

    /**
     * Parse --configs (JSON). Kalau kosong/rusak, pakai kombinasi default
     * yang masuk akal untuk pengujian parameter skripsi (variasi 1
     * parameter terhadap baseline, plus satu kombinasi 2 parameter).
     */
    protected function parseConfigs(?string $raw): array
    {
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && $decoded) {
                return $decoded;
            }
            $this->warn('configs JSON tidak valid — pakai kombinasi default.');
        }

        return [
            ['label' => 'baseline',              'params' => []],
            ['label' => 'pop-100',               'params' => ['population_size' => 100]],
            ['label' => 'gen-400',               'params' => ['max_generations' => 400]],
            ['label' => 'mut-0.1',               'params' => ['mutation_rate' => 0.1]],
            ['label' => 'pop-100-gen-400',       'params' => ['population_size' => 100, 'max_generations' => 400]],
        ];
    }
}
