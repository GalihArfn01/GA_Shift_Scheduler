<?php

namespace App\Console\Commands;

use App\Services\GeneticAlgorithmJobRunner;
use Illuminate\Console\Command;

class RunGeneticAlgorithm extends Command
{
    /**
     * php artisan ga:run {period_id} {job_id} {--config=}
     */
    protected $signature = 'ga:run {period_id} {job_id} {--config= : JSON string konfigurasi GA}';

    protected $description = 'Jalankan Genetic Algorithm untuk satu periode jadwal (background process)';

    public function handle(GeneticAlgorithmJobRunner $runner): int
    {
        $periodId = (int) $this->argument('period_id');
        $jobId    = (int) $this->argument('job_id');

        $config = null;
        $configJson = $this->option('config');
        if ($configJson) {
            $decoded = json_decode($configJson, true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        try {
            $runner->run($periodId, $jobId, $config);
        } catch (\Throwable) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
