<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_job_statuses', function (Blueprint $table) {
            // Simpan konfigurasi GA kustom yang dipakai saat generate (nullable = pakai default)
            $table->json('ga_config')->nullable()->after('schedule_period_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_job_statuses', function (Blueprint $table) {
            $table->dropColumn('ga_config');
        });
    }
};
