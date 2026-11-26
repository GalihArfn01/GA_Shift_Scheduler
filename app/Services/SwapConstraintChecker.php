<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Schedule;

/**
 * Validator hard constraint untuk pertukaran jadwal (shift swap).
 *
 * Penukaran jadwal memindahkan pemilik dua slot kerja, sehingga hasilnya
 * harus tetap mematuhi aturan domain yang sama dengan GA:
 * - HC7: shift dengan jam pulang SETELAH 22:00 hanya boleh dipegang agent
 *   laki-laki. Agent tanpa data jenis kelamin diperlakukan bukan-laki-laki.
 * - HC8: section Training eksklusif dua arah dengan agent level Training.
 *
 * Dipakai di dua titik: respond() rekan (fail-fast) dan approval PIC
 * (gerbang otoritatif sebelum penukaran dieksekusi).
 */
class SwapConstraintChecker
{
    /**
     * Validasi hasil penukaran dua jadwal.
     *
     * @param Schedule $jadwalA   Slot milik pengaju; setelah swap dipegang pemilik B
     * @param Agent     $pemilikB  Rekan yang akan menerima slot A
     * @param Schedule $jadwalB   Slot milik rekan; setelah swap dipegang pemilik A
     * @param Agent     $pemilikA  Pengaju yang akan menerima slot B
     * @return string|null Pesan pelanggaran, atau null jika aman.
     */
    public static function cekPertukaran(
        Schedule $jadwalA,
        Agent $pemilikB,
        Schedule $jadwalB,
        Agent $pemilikA
    ): ?string {
        return self::cekPenugasan($pemilikB, $jadwalA)
            ?? self::cekPenugasan($pemilikA, $jadwalB);
    }

    /**
     * Validasi satu pasangan (agent, slot) hasil penukaran.
     */
    public static function cekPenugasan(Agent $agent, Schedule $schedule): ?string
    {
        if (!$schedule->shift) {
            return null;
        }

        $nama = $agent->nama_agent;

        // HC8: eksklusivitas Training dua arah.
        $slotTraining = strtolower($schedule->section->nama_section ?? '') === 'training';
        if ($slotTraining !== $agent->isTraining()) {
            if ($slotTraining) {
                return "Tukar jadwal dibatalkan: slot section Training hanya boleh dipegang agent level Training, sedangkan {$nama} bukan agent Training.";
            }

            return "Tukar jadwal dibatalkan: {$nama} adalah agent level Training dan hanya boleh mendapat jadwal section Training.";
        }

        // HC7: shift malam hanya laki-laki.
        if (self::isShiftMalam($schedule) && !$agent->isLakiLaki()) {
            $jamPulang = substr((string) $schedule->shift->jam_pulang, 0, 5);

            return "Tukar jadwal dibatalkan: shift {$schedule->shift->kode_shift} (pulang {$jamPulang}) melewati batas 22:00 sehingga hanya boleh dipegang agent laki-laki, sedangkan {$nama} bukan laki-laki atau datanya belum diisi.";
        }

        return null;
    }

    /**
     * Shift malam = pulang setelah 22:00, ATAU melewati tengah malam
     * (jam_pulang <= jam_masuk) -- shift overnight pasti berakhir setelah
     * pukul 22:00 pada hari masuknya.
     */
    protected static function isShiftMalam(?Schedule $schedule): bool
    {
        if (!$schedule || !$schedule->shift) {
            return false;
        }

        $masuk  = substr((string) $schedule->shift->jam_masuk, 0, 5);
        $pulang = substr((string) $schedule->shift->jam_pulang, 0, 5);

        if ($pulang === '') {
            return false;
        }

        return strcmp($pulang, '22:00') > 0
            || ($masuk !== '' && strcmp($pulang, $masuk) <= 0);
    }
}
