<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_shift',
        'jam_masuk',
        'jam_pulang',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function operationalRequirements(): HasMany
    {
        return $this->hasMany(OperationalRequirement::class);
    }

    public function operationalTemplateDetails(): HasMany
    {
        return $this->hasMany(OperationalTemplateDetail::class);
    }
}
