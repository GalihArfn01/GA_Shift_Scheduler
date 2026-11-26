<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormRequestWindow extends Model
{
    protected $fillable = [
        'schedule_period_id',
        'tanggal_mulai',
        'hari_aktif',
        'opened_at',
        'closes_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'hari_aktif'    => 'array',
        'opened_at'     => 'datetime',
        'closes_at'     => 'datetime',
    ];

    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    public function schedulePeriod(): BelongsTo
    {
        return $this->belongsTo(SchedulePeriod::class, 'schedule_period_id');
    }

    /**
     * Apakah window ini masih dalam rentang waktu aktif (belum lewat
     * closes_at)? Tidak mengecek hari tertentu, hanya status waktu.
     */
    public function isOpen(): bool
    {
        return now()->lt($this->closes_at);
    }

    /**
     * Apakah tanggal tertentu termasuk hari yang diaktifkan PIC, DAN
     * window-nya masih terbuka (belum lewat closes_at)? Ini cek utama
     * yang dipakai untuk menentukan apakah Agent boleh mengajukan Form
     * Request untuk tanggal tersebut.
     */
    public function isDateActive(Carbon $tanggal): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $hariIndex = $this->tanggal_mulai->diffInDays($tanggal);

        if ($hariIndex < 0 || $hariIndex > 6) {
            return false; // di luar rentang 7 hari window ini
        }

        $namaHari = self::NAMA_HARI[$hariIndex] ?? null;

        return $namaHari && in_array($namaHari, $this->hari_aktif, true);
    }

    /**
     * Cari window yang sedang aktif (belum lewat closes_at) untuk
     * tanggal tertentu, kalau ada. Dipakai untuk menentukan window mana
     * yang relevan saat Agent membuka halaman Form Request.
     */
    public static function findActiveForDate(Carbon $tanggal): ?self
    {
        return self::where('closes_at', '>', now())
            ->get()
            ->first(fn (self $window) => $window->isDateActive($tanggal));
    }
}
