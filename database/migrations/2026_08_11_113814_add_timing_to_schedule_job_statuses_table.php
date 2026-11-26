<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_job_statuses', function (Blueprint $table) {
            // Waktu GA mulai berjalan (saat status berubah ke 'running')
            $table->timestamp('started_at')->nullable()->after('ga_config');
            // Waktu GA selesai (saat status berubah ke 'done' atau 'failed')
            $table->timestamp('finished_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_job_statuses', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'finished_at']);
        });
    }
};
