<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalTemplateDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'operational_template_id',
        'shift_id',
        'section_id',
        'jumlah_kebutuhan',
    ];

    public function operationalTemplate(): BelongsTo
    {
        return $this->belongsTo(OperationalTemplate::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
