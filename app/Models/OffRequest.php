<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'tanggal',
        'jenis',
        'status_approval',
        'kelompok_section',
        'keterangan',
        'catatan_pic',
        'approved_at',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'approved_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Scope: Sembunyikan request yang tanggalnya masuk dalam periode jadwal yang sudah difinalisasi.
     */
    public function scopeNotFinalized($query)
    {
        return $query->whereNotExists(function ($q) {
            $q->select(\Illuminate\Support\Facades\DB::raw(1))
              ->from('schedule_periods')
              ->whereColumn('off_requests.tanggal', '>=', 'schedule_periods.tanggal_mulai')
              ->whereColumn('off_requests.tanggal', '<=', 'schedule_periods.tanggal_selesai')
              ->where('schedule_periods.status', 'final');
        });
    }

    /**
     * Scope: off request Jalur 1 (Form Request, prioritas rendah,
     * self-service dengan kuota).
     */
    public function scopeFormRequest($query)
    {
        return $query->where('jenis', 'form_request');
    }

    /**
     * Scope: off request Jalur 2 (Prioritas Tinggi, butuh approval PIC).
     */
    public function scopePrioritasTinggi($query)
    {
        return $query->where('jenis', 'prioritas_tinggi');
    }

    /**
     * Scope: off request yang diinput manual oleh PIC (mewakili agent),
     * misal untuk mempertahankan constraint maksimal hari kerja/bulan.
     */
    public function scopePicManual($query)
    {
        return $query->where('jenis', 'pic_manual');
    }

    /**
     * Scope: gabungan jenis yang dianggap "prioritas tinggi" secara GA
     * (prioritas_tinggi DAN pic_manual) -- dipakai untuk tampilan tab
     * "Tinggi" di halaman PIC.
     */
    public function scopePrioritasTinggiGabungan($query)
    {
        return $query->whereIn('jenis', ['prioritas_tinggi', 'pic_manual']);
    }

    /**
     * Scope: off request yang efektif dipertimbangkan GA -- hanya yang
     * statusnya 'diterima' (form_request otomatis diterima saat lolos
     * kuota, prioritas_tinggi diterima manual oleh PIC). Yang berstatus
     * 'pending' atau 'ditolak' TIDAK pernah masuk proses GA.
     */
    public function scopeDiterima($query)
    {
        return $query->where('status_approval', 'diterima');
    }

    /**
     * Helper: bobot prioritas untuk GA -- 'tinggi' untuk jenis
     * prioritas_tinggi DAN pic_manual (keduanya diperlakukan sama di
     * fitness function/SC1), 'rendah' untuk form_request. Menggantikan
     * kolom 'prioritas' yang sudah dihapus (sekarang derivatif dari 'jenis').
     */
    public function getPrioritasAttribute(): string
    {
        return in_array($this->jenis, ['prioritas_tinggi', 'pic_manual'], true) ? 'tinggi' : 'rendah';
    }
}
