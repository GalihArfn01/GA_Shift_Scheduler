<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Level "Training" tersimpan sebagai '0' -- tingkatan paling bawah
     * sebelum Level 1. Nilai numerik ini menjaga kompatibilitas kode
     * lama yang memperlakukan level secara numerik/terurut.
     */
    public const LEVEL_TRAINING = '0';

    protected $fillable = [
        'nama_agent',
        'level',
        'status',
        'jenis_kelamin',
        'user_id',
    ];

    /**
     * Label level yang ramah pengguna untuk ditampilkan di UI:
     * '0' -> Training, selain itu "Level N".
     */
    public function labelLevel(): string
    {
        return $this->isTraining() ? 'Training' : "Level {$this->level}";
    }

    /**
     * Label singkat untuk badge/tempat sempit: L1/L2/L3/TR.
     */
    public function badgeLevel(): string
    {
        return $this->isTraining() ? 'TR' : "L{$this->level}";
    }

    public function isTraining(): bool
    {
        return (string) $this->level === self::LEVEL_TRAINING;
    }

    /**
     * Apakah agent ini boleh mengisi shift malam (HC7)?
     * Shift dengan jam pulang > 22:00 hanya boleh diisi agent berjenis
     * kelamin laki-laki; agent tanpa data gender otomatis tidak eligible.
     */
    public function isLakiLaki(): bool
    {
        return $this->jenis_kelamin === 'L';
    }

    /**
     * Akun login milik agent ini (kalau sudah dibuatkan PIC).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Riwayat/hasil jadwal milik agent ini.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Daftar off request yang diajukan agent ini.
     */
    public function offRequests(): HasMany
    {
        return $this->hasMany(OffRequest::class);
    }

    /**
     * Periode jadwal di mana agent ini ditandai mendapat giliran Inbound.
     */
    public function inboundPeriods(): BelongsToMany
    {
        return $this->belongsToMany(SchedulePeriod::class, 'schedule_period_inbound_agents')
                    ->withTimestamps();
    }

    /**
     * Scope: hanya agent berstatus aktif (bukan skorsing).
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
