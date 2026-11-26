<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_job_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_period_id')
                ->constrained()
                ->cascadeOnDelete();

            // pending   → job di-spawn tapi belum mulai
            // running   → GA sedang berjalan
            // done      → GA selesai, hasil tersimpan
            // failed    → terjadi error saat GA berjalan
            $table->enum('status', ['pending', 'running', 'done', 'failed'])
                ->default('pending');

            $table->text('error_message')->nullable();

            // Simpan summary GA (generations_run, detail, dll) setelah selesai
            $table->json('result_summary')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_job_statuses');
    }
};
