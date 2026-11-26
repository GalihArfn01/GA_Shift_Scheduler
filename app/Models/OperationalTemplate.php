<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_template',
        'keterangan',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(OperationalTemplateDetail::class);
    }
}
