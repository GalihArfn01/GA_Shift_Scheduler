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
        Schema::create('off_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('tanggal');

            $table->enum('prioritas', [
                'tinggi',
                'rendah'
            ]);

            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('off_requests');
    }
};