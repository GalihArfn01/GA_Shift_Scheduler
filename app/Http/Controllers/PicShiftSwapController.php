<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ShiftSwap;
use App\Notifications\TukarJadwalDiprosesPic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PicShiftSwapController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));

        $query = ShiftSwap::with(['requester', 'responder', 'requesterSchedule', 'responderSchedule'])->latest();

        if ($status === 'menunggu') {
            $query->where('status', 'menunggu_pic');
        } elseif ($status === 'disetujui') {
            $query->where('status', 'disetujui_pic');
        } elseif ($status === 'ditolak') {
            $query->whereIn('status', ['ditolak_rekan', 'ditolak_pic']);
        }

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->whereHas('requester', fn ($r) => $r->where('nama_agent', 'like', "%{$q}%"))
                    ->orWhereHas('responder', fn ($r) => $r->where('nama_agent', 'like', "%{$q}%"));
            });
        }

        $swaps = $query->paginate(15)->withQueryString();

        // Ringkasan status untuk card di atas halaman
        $counts = [
            'total'     => ShiftSwap::count(),
            'menunggu'  => ShiftSwap::where('status', 'menunggu_pic')->count(),
            'disetujui' => ShiftSwap::where('status', 'disetujui_pic')->count(),
            'ditolak'   => ShiftSwap::whereIn('status', ['ditolak_rekan', 'ditolak_pic'])->count(),
        ];

        // Detail pelanggaran SC5 untuk peringatan (jika status menunggu_pic)
        foreach ($swaps as $swap) {
            $swap->sc5_details = [];
            if ($swap->status === 'menunggu_pic') {
                $swap->sc5_details = $this->sc5Details($swap);
            }
        }

        return view('pic.shift-swaps.index', compact('swaps', 'counts', 'status', 'q'));
    }

    public function updateApproval(Request $request, ShiftSwap $shiftSwap)
    {
        if ($shiftSwap->status !== 'menunggu_pic') {
            abort(403);
        }

        $request->validate([
            'status' => ['required', Rule::in(['disetujui_pic', 'ditolak_pic'])],
            'catatan_pic' => 'nullable|string|max:500',
        ]);

        $diterima = $request->status === 'disetujui_pic';

        if ($diterima) {
            $reqSched = $shiftSwap->requesterSchedule;
            $respSched = $shiftSwap->responderSchedule;

            // Null-safe: jadwal terkait sudah dihapus -> tidak bisa diproses.
            if (! $reqSched || ! $respSched) {
                return back()->with('error', 'Salah satu jadwal terkait sudah dihapus, permintaan tidak dapat disetujui.');
            }

            if ($reqSched->status !== 'kerja' || $respSched->status !== 'kerja') {
                return back()->with('error', 'Salah satu jadwal sudah tidak berstatus kerja, permintaan tidak dapat disetujui.');
            }

            if ($reqSched->section_id !== $respSched->section_id) {
                return back()->with('error', 'Section kedua jadwal tidak sama, permintaan tidak dapat disetujui.');
            }

            // Re-validasi konflik saat approval -- jadwal bisa berubah sejak
            // pengajuan, jadi tidak cukup mengandalkan cek di waktu request.
            $tanggalReq = $reqSched->tanggal->format('Y-m-d');
            $tanggalResp = $respSched->tanggal->format('Y-m-d');

            if ($tanggalReq !== $tanggalResp) {
                $requesterConflict = Schedule::where('agent_id', $reqSched->agent_id)
                    ->whereDate('tanggal', $tanggalResp)
                    ->where('status', 'kerja')
                    ->exists();

                $responderConflict = Schedule::where('agent_id', $respSched->agent_id)
                    ->whereDate('tanggal', $tanggalReq)
                    ->where('status', 'kerja')
                    ->exists();

                if ($requesterConflict || $responderConflict) {
                    return back()->with('error', 'Terdapat tabrakan jadwal setelah penukaran (salah satu agen sudah memiliki shift kerja di tanggal tujuan).');
                }
            }

            // Lakukan penukaran shift pada schedules secara atomik.
            DB::transaction(function () use ($reqSched, $respSched) {
                $tempAgentId = $reqSched->agent_id;
                $reqSched->update(['agent_id' => $respSched->agent_id]);
                $respSched->update(['agent_id' => $tempAgentId]);
            });
        }

        $shiftSwap->update([
            'status' => $request->status,
            'catatan_pic' => $request->catatan_pic,
        ]);

        // Notify both agents
        $shiftSwap->requester->user?->notify(new TukarJadwalDiprosesPic($shiftSwap));
        $shiftSwap->responder->user?->notify(new TukarJadwalDiprosesPic($shiftSwap));

        return back()->with('success', 'Permintaan tukar jadwal berhasil diproses.');
    }

    /**
     * Detail pelanggaran SC5: siapa yang terdampak dan berapa jam istirahat aktualnya.
     *
     * @return array<int, array{nama:string, jam:int, arah:string}>
     */
    private function sc5Details(ShiftSwap $swap): array
    {
        $details = [];

        $d1 = $this->sc5ViolationDetail($swap->requester, $swap->responderSchedule);
        if ($d1 !== null) {
            $details[] = $d1;
        }

        $d2 = $this->sc5ViolationDetail($swap->responder, $swap->requesterSchedule);
        if ($d2 !== null) {
            $details[] = $d2;
        }

        return $details;
    }

    private function sc5ViolationDetail($agent, ?Schedule $newSchedule): ?array
    {
        if (! $newSchedule || ! $newSchedule->shift) {
            return null;
        }

        $date = Carbon::parse($newSchedule->tanggal);

        $prevDay = Schedule::where('agent_id', $agent->id)
            ->where('tanggal', $date->copy()->subDay()->toDateString())
            ->where('status', 'kerja')
            ->with('shift')
            ->first();

        $nextDay = Schedule::where('agent_id', $agent->id)
            ->where('tanggal', $date->copy()->addDay()->toDateString())
            ->where('status', 'kerja')
            ->with('shift')
            ->first();

        // Cek istirahat dengan hari sebelumnya
        if ($prevDay && $prevDay->shift) {
            $pulangPrev = Carbon::parse($prevDay->tanggal->format('Y-m-d').' '.$prevDay->shift->jam_pulang);
            // jika jam pulang < jam masuk, asumsikan shift malam (lewat tengah malam)
            if ($prevDay->shift->jam_pulang < $prevDay->shift->jam_masuk) {
                $pulangPrev->addDay();
            }
            $masukNew = Carbon::parse($newSchedule->tanggal->format('Y-m-d').' '.$newSchedule->shift->jam_masuk);
            $hours = $masukNew->diffInHours($pulangPrev, true);
            if ($hours < 11) {
                return [
                    'nama' => $agent->nama_agent,
                    'jam'  => (int) $hours,
                    'arah' => 'sebelumnya',
                ];
            }
        }

        // Cek istirahat dengan hari berikutnya
        if ($nextDay && $nextDay->shift) {
            $pulangNew = Carbon::parse($newSchedule->tanggal->format('Y-m-d').' '.$newSchedule->shift->jam_pulang);
            if ($newSchedule->shift->jam_pulang < $newSchedule->shift->jam_masuk) {
                $pulangNew->addDay();
            }
            $masukNext = Carbon::parse($nextDay->tanggal->format('Y-m-d').' '.$nextDay->shift->jam_masuk);
            $hours = $masukNext->diffInHours($pulangNew, true);
            if ($hours < 11) {
                return [
                    'nama' => $agent->nama_agent,
                    'jam'  => (int) $hours,
                    'arah' => 'berikutnya',
                ];
            }
        }

        return null;
    }
}
