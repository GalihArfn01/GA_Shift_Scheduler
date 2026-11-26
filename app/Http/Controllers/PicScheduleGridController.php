<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\OffRequest;
use App\Models\OperationalRequirement;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use App\Notifications\JadwalDiubahPic;
use App\Services\SwapConstraintChecker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PicScheduleGridController extends Controller
{
    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /**
     * Tampilkan halaman grid jadwal mingguan untuk satu periode.
     * Grid bisa diakses untuk periode draft maupun final.
     */
    public function grid(SchedulePeriod $period)
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        if (! $hasResult) {
            return redirect()
                ->route('schedules.history')
                ->with('error', 'Periode ini belum memiliki hasil jadwal. Generate jadwal terlebih dahulu.');
        }

        $viewData = $this->buildGridData($period);
        $viewData['gridJson'] = $this->formatGridForJson($viewData['grid']);
        $viewData['agentsJson'] = $viewData['agents']->map(fn ($a) => [
            'id'    => $a->id,
            'nama'  => $a->nama_agent,
            'level' => $a->labelLevel(),
        ])->values()->all();

        return view('pic.schedule-grid.edit', array_merge([
            'period' => $period,
        ], $viewData));
    }

    /**
     * AJAX: Ambil data grid terbaru (untuk re-fetch setelah swap).
     */
    public function gridData(SchedulePeriod $period): JsonResponse
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        if (! $hasResult) {
            return response()->json(['error' => 'Periode ini belum memiliki hasil jadwal.'], 404);
        }

        $data = $this->buildGridData($period);

        $gridJson = [];
        foreach ($data['grid'] as $agentId => $hariMap) {
            foreach ($hariMap as $hariIndex => $schedule) {
                $gridJson[$agentId][$hariIndex] = [
                    'id'              => $schedule->id,
                    'shift_kode'      => $schedule->shift->kode_shift ?? '-',
                    'section_name'    => $schedule->section->nama_section ?? '-',
                    'status'          => $schedule->status,
                    'is_off_request'  => $schedule->is_off_request ?? false,
                ];
            }
        }

        return response()->json([
            'agents'   => $data['agents']->map(fn ($a) => [
                'id'    => $a->id,
                'nama'  => $a->nama_agent,
                'level' => $a->labelLevel(),
            ]),
            'hariList' => $data['hariList'],
            'grid'     => $gridJson,
        ]);
    }

    /**
     * AJAX: Eksekusi pertukaran/pemindahan dua cell jadwal.
     * Mendukung 3 kombinasi:
     * - kerja ↔ kerja: tukar agent_id (swap)
     * - kerja ↔ off: pindahkan shift dari satu agent ke agent lain (move)
     * - off ↔ off: ditolak
     */
    public function swap(Request $request, SchedulePeriod $period): JsonResponse
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        if (! $hasResult) {
            return response()->json(['error' => 'Periode ini belum memiliki hasil jadwal.'], 404);
        }

        $validated = $request->validate([
            'source_schedule_id' => ['required', 'exists:schedules,id'],
            'target_schedule_id' => ['required', 'exists:schedules,id'],
        ]);

        $source = Schedule::with(['agent', 'shift', 'section'])->findOrFail($validated['source_schedule_id']);
        $target = Schedule::with(['agent', 'shift', 'section'])->findOrFail($validated['target_schedule_id']);

        if ($source->schedule_period_id !== $period->id || $target->schedule_period_id !== $period->id) {
            return response()->json(['error' => 'Jadwal tidak termasuk dalam periode ini.'], 422);
        }

        if ($source->id === $target->id) {
            return response()->json(['error' => 'Tidak bisa menukar jadwal dengan diri sendiri.'], 422);
        }

        $sourceIsKerja = $source->status === 'kerja';
        $targetIsKerja = $target->status === 'kerja';

        // off ↔ off: tidak ada yang bisa ditukar
        if (! $sourceIsKerja && ! $targetIsKerja) {
            return response()->json(['error' => 'Kedua cell berstatus OFF, tidak ada jadwal yang bisa ditukar.'], 422);
        }

        // --- Validasi HC7 + HC8 ---
        if ($sourceIsKerja && $targetIsKerja) {
            // kerja ↔ kerja: swap agent — validate kedua arah
            $pelanggaran = SwapConstraintChecker::cekPertukaran(
                $source, $target->agent,
                $target, $source->agent
            );
        } elseif ($sourceIsKerja && ! $targetIsKerja) {
            // kerja → off: agent_id TIDAK berubah (lihat catatan di eksekusi
            // di bawah) -- yang benar-benar akan bekerja adalah agent
            // PEMILIK TARGET (sudah kerja di sana), memakai shift & section
            // milik SOURCE. Jadi yang divalidasi: agent target vs shift
            // source (bukan sebaliknya -- $target->shift masih null di titik
            // ini karena cell-nya masih OFF, sehingga validasi lama selalu
            // lolos tanpa benar-benar mengecek apa pun).
            $pelanggaran = SwapConstraintChecker::cekPenugasan($target->agent, $source);
        } else {
            // off → kerja: sebaliknya -- agent PEMILIK SOURCE yang akan
            // bekerja, memakai shift & section milik TARGET (yang sudah
            // terisi/non-null).
            $pelanggaran = SwapConstraintChecker::cekPenugasan($source->agent, $target);
        }

        if ($pelanggaran !== null) {
            return response()->json(['error' => $pelanggaran], 422);
        }

        // --- Cek kapasitas slot (HC1 terbalik): jangan sampai jumlah agent
        // kerja pada satu (tanggal, shift, section) melebihi jumlah_kebutuhan
        // yang didefinisikan PIC di Kebutuhan Operasional. Cuma relevan untuk
        // kombinasi kerja<->off, karena itu satu-satunya kombinasi yang
        // menambah jumlah slot terisi di satu sisi. kerja<->kerja tidak
        // mengubah jumlah terisi sama sekali (cuma tukar agent, jumlah slot
        // yang kepakai di kedua tanggal tetap sama).
        if ($sourceIsKerja && ! $targetIsKerja) {
            // kerja → off: target akan terisi dengan shift & section dari source
            $slotPenuh = $this->slotPenuh($period, $target->tanggal->format('Y-m-d'), $source->shift_id, $source->section_id, $target->id);
            if ($slotPenuh !== null) {
                return response()->json(['error' => $slotPenuh], 422);
            }
        } elseif (! $sourceIsKerja && $targetIsKerja) {
            // off → kerja: source akan terisi dengan shift & section dari target
            $slotPenuh = $this->slotPenuh($period, $source->tanggal->format('Y-m-d'), $target->shift_id, $target->section_id, $source->id);
            if ($slotPenuh !== null) {
                return response()->json(['error' => $slotPenuh], 422);
            }
        }

        // --- Cek double-booking jika tanggal berbeda ---
        // PENTING: pengecekan ini HANYA relevan untuk kombinasi kerja<->kerja,
        // karena cuma kombinasi itu yang benar-benar memindahkan agent_id ke
        // tanggal lain (source & target saling tukar pemilik). Untuk kombinasi
        // kerja<->off, agent_id tiap baris TIDAK PERNAH berubah -- yang berubah
        // cuma isi (shift_id/status) baris yang sudah ada, jadi tidak mungkin
        // terjadi tabrakan jadwal sama sekali (satu agent memang selalu cuma
        // punya satu baris per tanggal). Sebelumnya kode ini salah mengecek
        // agent yang tidak relevan untuk kombinasi kerja<->off, menyebabkan
        // penolakan palsu ("tabrakan jadwal") saat agent tsb kebetulan sudah
        // punya shift lain yang sama sekali tidak terkait dengan swap ini.
        $tanggalSource = $source->tanggal->format('Y-m-d');
        $tanggalTarget = $target->tanggal->format('Y-m-d');

        if ($tanggalSource !== $tanggalTarget && $sourceIsKerja && $targetIsKerja) {
            // kerja ↔ kerja: kedua agent pindah ke tanggal lawan
            $sourceAgentConflict = Schedule::where('agent_id', $source->agent_id)
                ->where('schedule_period_id', $period->id)
                ->where('tanggal', $tanggalTarget)
                ->where('id', '!=', $target->id)
                ->where('status', 'kerja')
                ->exists();

            $targetAgentConflict = Schedule::where('agent_id', $target->agent_id)
                ->where('schedule_period_id', $period->id)
                ->where('tanggal', $tanggalSource)
                ->where('id', '!=', $source->id)
                ->where('status', 'kerja')
                ->exists();

            if ($sourceAgentConflict || $targetAgentConflict) {
                return response()->json([
                    'error' => 'Terdapat tabrakan jadwal setelah penukaran. Salah satu agent sudah memiliki shift kerja di tanggal tujuan.',
                ], 422);
            }
        }

        // --- Eksekusi perubahan secara atomik ---
        DB::transaction(function () use ($source, $target, $sourceIsKerja, $targetIsKerja) {
            if ($sourceIsKerja && $targetIsKerja) {
                // Hapus record OFF yang akan bentrok (satu agent + satu tanggal
                // harus punya tepi satu schedule). Tanpa pembersihan ini, sesudah
                // tukar agent_id, satu agent bisa punya 2 record pada tanggal yang
                // sama (kerja dari hasil tukar + OFF asli), lalu buildGridData
                // menimpa yang kerja dengan yang OFF sehingga jadwal "menghilang".
                Schedule::where('agent_id', $target->agent_id)
                    ->where('schedule_period_id', $source->schedule_period_id)
                    ->whereDate('tanggal', $source->tanggal)
                    ->where('id', '!=', $source->id)
                    ->where('status', 'off')
                    ->delete();

                Schedule::where('agent_id', $source->agent_id)
                    ->where('schedule_period_id', $target->schedule_period_id)
                    ->whereDate('tanggal', $target->tanggal)
                    ->where('id', '!=', $target->id)
                    ->where('status', 'off')
                    ->delete();

                // kerja ↔ kerja: tukar agent_id
                $tempAgentId = $source->agent_id;
                $source->update(['agent_id' => $target->agent_id]);
                $target->update(['agent_id' => $tempAgentId]);
            } elseif ($sourceIsKerja && ! $targetIsKerja) {
                // kerja → off: pindahkan shift ke target, source jadi off
                $target->update([
                    'shift_id'   => $source->shift_id,
                    'section_id' => $source->section_id,
                    'status'     => 'kerja',
                ]);
                $source->update([
                    'shift_id'   => null,
                    'section_id' => null,
                    'status'     => 'off',
                ]);
            } else {
                // off → kerja: pindahkan shift ke source, target jadi off
                $source->update([
                    'shift_id'   => $target->shift_id,
                    'section_id' => $target->section_id,
                    'status'     => 'kerja',
                ]);
                $target->update([
                    'shift_id'   => null,
                    'section_id' => null,
                    'status'     => 'off',
                ]);
            }
        });

        // Kirim notifikasi email + database ke agent yang terdampak, HANYA
        // kalau periode ini sudah final -- agent sudah pernah melihat jadwal
        // finalnya, jadi perubahan manual ini perlu diberitahukan. Edit di
        // periode draft tidak perlu notif (belum final, belum pernah dilihat
        // agent). $source->agent dan $target->agent tetap merujuk ke agent
        // ASLI pemilik masing-masing cell sebelum transaksi (relasi sudah
        // di-eager-load di awal method, tidak berubah walau agent_id di
        // baris berubah lewat ->update() pada kasus kerja<->kerja).
        if ($period->isFinal()) {
            $agentIdsNotified = [];
            foreach ([$source->agent, $target->agent] as $agent) {
                if ($agent === null || in_array($agent->id, $agentIdsNotified, true)) {
                    continue;
                }
                $agentIdsNotified[] = $agent->id;
                $agent->user?->notify(new JadwalDiubahPic($period));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil ditukar.',
        ]);
    }

    /**
     * Cek apakah menempatkan satu agent kerja tambahan pada
     * (tanggal, shift, section) tertentu akan melebihi jumlah_kebutuhan
     * yang didefinisikan PIC untuk hari itu. Return pesan error kalau
     * penuh, atau null kalau masih ada slot tersisa.
     */
    protected function slotPenuh(SchedulePeriod $period, string $tanggal, ?int $shiftId, ?int $sectionId, int $excludeScheduleId): ?string
    {
        if ($shiftId === null || $sectionId === null) {
            return null;
        }

        $hariKe = $this->hariKeUntukTanggal($period, $tanggal);

        $jumlahKebutuhan = (int) OperationalRequirement::where('schedule_period_id', $period->id)
            ->where('hari_ke', $hariKe)
            ->where('shift_id', $shiftId)
            ->where('section_id', $sectionId)
            ->value('jumlah_kebutuhan');

        $jumlahTerisi = Schedule::where('schedule_period_id', $period->id)
            ->whereDate('tanggal', $tanggal)
            ->where('shift_id', $shiftId)
            ->where('section_id', $sectionId)
            ->where('status', 'kerja')
            ->where('id', '!=', $excludeScheduleId)
            ->count();

        if (($jumlahTerisi + 1) > $jumlahKebutuhan) {
            $shift = Shift::find($shiftId);
            $section = Section::find($sectionId);

            return sprintf(
                'Slot %s/%s penuh (%d/%d).',
                $section->nama_section ?? '-',
                $shift->kode_shift ?? '-',
                $jumlahTerisi,
                $jumlahKebutuhan
            );
        }

        return null;
    }

    /**
     * Terjemahkan tanggal ke nama hari (senin..minggu) relatif terhadap
     * tanggal_mulai periode -- sama persis dengan urutan NAMA_HARI yang
     * dipakai buildGridData(), supaya konsisten dengan hari_ke di
     * operational_requirements.
     */
    protected function hariKeUntukTanggal(SchedulePeriod $period, string $tanggal): string
    {
        $offset = Carbon::parse($period->tanggal_mulai)->startOfDay()
            ->diffInDays(Carbon::parse($tanggal)->startOfDay());

        return self::NAMA_HARI[$offset] ?? self::NAMA_HARI[0];
    }

    /**
     * Bangun data grid mingguan dari periode.
     */
    protected function buildGridData(SchedulePeriod $period): array
    {
        $tanggalMulai = Carbon::parse($period->tanggal_mulai);

        $hariList = [];
        for ($i = 0; $i < 7; $i++) {
            $tanggal = $tanggalMulai->copy()->addDays($i);
            $hariList[] = [
                'index'   => $i,
                'nama'    => self::NAMA_HARI[$i],
                'tanggal' => $tanggal->format('Y-m-d'),
                'label'   => $tanggal->translatedFormat('j F'),
            ];
        }

        $schedules = Schedule::where('schedule_period_id', $period->id)
            ->with(['agent', 'shift', 'section'])
            ->get();

        // Ambil off request yang diterima dalam rentang tanggal periode
        // untuk menandai cell OFF yang berasal dari off request.
        $offRequestKeys = OffRequest::where('status_approval', 'diterima')
            ->whereBetween('tanggal', [$period->tanggal_mulai, $period->tanggal_selesai])
            ->get()
            ->mapWithKeys(fn ($or) => [
                $or->agent_id . '|' . Carbon::parse($or->tanggal)->format('Y-m-d') => true,
            ]);

        $agentIdsWithSchedules = $schedules->pluck('agent_id')->unique();
        $agents = Agent::withTrashed()
            ->where(function ($query) use ($agentIdsWithSchedules) {
                $query->whereNull('deleted_at');
                if ($agentIdsWithSchedules->isNotEmpty()) {
                    $query->orWhereIn('id', $agentIdsWithSchedules);
                }
            })
            ->orderBy('level')
            ->orderBy('nama_agent')
            ->get();

        // Normalisasi: pastikan setiap agent × tanggal punya record OFF
        // sehingga semua cell OFF bisa di-drag (konsisten).
        $existingKeys = $schedules->map(fn ($s) => $s->agent_id . '|' . Carbon::parse($s->tanggal)->format('Y-m-d'))
            ->flip();

        $missingRows = [];
        $now = now();
        foreach ($agents as $agent) {
            foreach ($hariList as $hari) {
                $key = $agent->id . '|' . $hari['tanggal'];
                if (! $existingKeys->has($key)) {
                    $missingRows[] = [
                        'schedule_period_id' => $period->id,
                        'agent_id'           => $agent->id,
                        'tanggal'            => $hari['tanggal'],
                        'shift_id'           => null,
                        'section_id'         => null,
                        'status'             => 'off',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }
        }

        if ($missingRows !== []) {
            foreach (array_chunk($missingRows, 200) as $chunk) {
                Schedule::insert($chunk);
            }
            // Reload schedules setelah insert
            $schedules = Schedule::where('schedule_period_id', $period->id)
                ->with(['agent', 'shift', 'section'])
                ->get();
            // Re-tag off request
            foreach ($schedules as $s) {
                $tanggalStr = Carbon::parse($s->tanggal)->format('Y-m-d');
                $s->is_off_request = ($s->status === 'off')
                    && $offRequestKeys->has($s->agent_id . '|' . $tanggalStr);
            }
        }

        $hariIndexByTanggal = [];
        foreach ($hariList as $hari) {
            $hariIndexByTanggal[$hari['tanggal']] = $hari['index'];
        }

        $grid = [];
        foreach ($schedules as $s) {
            $tanggalStr = Carbon::parse($s->tanggal)->format('Y-m-d');
            $hariIndex = $hariIndexByTanggal[$tanggalStr] ?? null;
            if ($hariIndex !== null) {
                $s->is_off_request = ($s->status === 'off')
                    && $offRequestKeys->has($s->agent_id . '|' . $tanggalStr);
                $grid[$s->agent_id][$hariIndex] = $s;
            }
        }

        return [
            'hariList' => $hariList,
            'agents'   => $agents,
            'grid'     => $grid,
        ];
    }

    /**
     * Format grid data menjadi array plain untuk di-encode JSON
     * di view (menghindari closure di @json yang sulit di-parse Blade).
     */
    protected function formatGridForJson(array $grid): array
    {
        $result = [];
        foreach ($grid as $agentId => $hariMap) {
            $result[$agentId] = [];
            foreach ($hariMap as $hariIndex => $schedule) {
                $result[$agentId][$hariIndex] = [
                    'id'              => $schedule->id,
                    'shift_kode'      => $schedule->shift->kode_shift ?? '-',
                    'section_name'    => $schedule->section->nama_section ?? '-',
                    'status'          => $schedule->status,
                    'is_off_request'  => $schedule->is_off_request ?? false,
                ];
            }
        }
        return $result;
    }
}
