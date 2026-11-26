<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_section',
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

    /**
     * Warna identitas per section untuk badge/header -- satu sumber
     * kebenaran yang dipakai di halaman template, wizard Step 2, dst.
     *
     * @return array{bg:string, text:string, accent:string}
     */
    public static function warna(string $namaSection): array
    {
        return match ($namaSection) {
            'Chat'      => ['bg' => '#FFF1F0', 'text' => '#C01A12', 'accent' => '#E2231A'],
            'Website'   => ['bg' => '#FFFBEB', 'text' => '#D97706', 'accent' => '#D97706'],
            'Informasi' => ['bg' => '#EFF6FF', 'text' => '#1D4ED8', 'accent' => '#1D4ED8'],
            'Inbound'   => ['bg' => '#EEF2FF', 'text' => '#4F46E5', 'accent' => '#4F46E5'],
            'Training'  => ['bg' => '#ECFDF5', 'text' => '#047857', 'accent' => '#10B981'],
            default     => ['bg' => '#F6F6F7', 'text' => '#62656F', 'accent' => '#82858E'],
        };
    }
}
