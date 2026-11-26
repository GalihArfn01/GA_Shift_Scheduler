<?php

namespace App\Jobs;

use App\Services\GeneticAlgorithmJobRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunGeneticAlgorithmJob implements ShouldQueue
{
    use Queueable;

    /** GA bisa berjalan lama — tidak ada batas waktu job. */
    public int $timeout = 0;

    public int $tries = 1;

    public function __construct(
        public int $periodId,
        public int $jobId,
        public ?array $gaConfig = null,
    ) {}

    public function handle(GeneticAlgorithmJobRunner $runner): void
    {
        $runner->run($this->periodId, $this->jobId, $this->gaConfig);
    }
}
