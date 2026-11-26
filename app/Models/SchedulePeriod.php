<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Kebutuhan operasional yang berlaku untuk periode ini.
     */
    public function operationalRequirements(): HasMany
    {
        return $this->hasMany(OperationalRequirement::class);
    }

    /**
     * Hasil jadwal (draft maupun final) untuk periode ini.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Agent yang ditandai mendapat giliran Inbound di periode ini.
     */
    public function inboundAgents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'schedule_period_inbound_agents')
                    ->withTimestamps();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }
}
