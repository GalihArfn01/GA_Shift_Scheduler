<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shift_swaps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_period_id')->constrained()->cascadeOnDelete();
            
            $table->foreignId('requester_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('responder_id')->constrained('agents')->cascadeOnDelete();
            
            $table->foreignId('requester_schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('responder_schedule_id')->constrained('schedules')->cascadeOnDelete();
            
            $table->enum('status', [
                'menunggu_rekan', 
                'ditolak_rekan', 
                'menunggu_pic', 
                'disetujui_pic', 
                'ditolak_pic'
            ])->default('menunggu_rekan');
            
            $table->string('alasan', 500)->nullable();
            $table->string('catatan_pic', 500)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_swaps');
    }
};
