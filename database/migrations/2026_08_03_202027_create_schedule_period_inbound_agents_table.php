<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_period_inbound_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_period_id')
                  ->constrained('schedule_periods')
                  ->onDelete('cascade');
            $table->foreignId('agent_id')
                  ->constrained('agents')
                  ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['schedule_period_id', 'agent_id'], 'spia_period_agent_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_period_inbound_agents');
    }
};
