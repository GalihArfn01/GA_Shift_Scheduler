<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\ShiftSwap;
use App\Models\User;
use App\Notifications\PermintaanTukarJadwal;
use App\Notifications\TukarJadwalDisepakati;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AgentShiftSwapController extends Controller
{
    public function index()
    {
        $agent = Auth::user()->agent;
        if (! $agent) {
            abort(403);
        }

        // Eager-load semua relasi yang dipakai view (nama agent, jadwal, shift,
        // section) agar tidak ada N+1, dan paginate tiap tab secara terpisah
        // supaya halaman tetap ringan meski riwayat swap sudah banyak.
        $with = [
            'requester',
            'responder',
            'requesterSchedule.shift',
            'requesterSchedule.section',
            'responderSchedule.shift',
            'responderSchedule.section',
        ];

        $swapsDiajukan = ShiftSwap::with($with)
            ->where('requester_id', $agent->id)
            ->latest()
            ->paginate(10, ['*'], 'page_diajukan')
            ->withQueryString();

        $swapsMasuk = ShiftSwap::with($with)
            ->where('responder_id', $agent->id)
            ->latest()
            ->paginate(10, ['*'], 'page_masuk')
            ->withQueryString();

        // Badge di tab dihitung dari seluruh data (bukan hanya halaman aktif)
        $masukMenunggu = ShiftSwap::where('responder_id', $agent->id)
            ->where('status', 'menunggu_rekan')
            ->count();
        $diajukanAktif = ShiftSwap::where('requester_id', $agent->id)
            ->whereIn('status', ['menunggu_rekan', 'menunggu_pic'])
            ->count();

        return view('agent.shift-swaps.index', compact(
            'agent',
            'swapsDiajukan',
            'swapsMasuk',
            'masukMenunggu',
            'diajukanAktif'
        ));
    }

    public function create()
    {
        $agent = Auth::user()->agent;
        if (! $agent) {
            abort(403);
        }

        // Hanya jadwal final dari hari ini ke depan
        $mySchedules = Schedule::where('agent_id', $agent->id)
            ->whereHas('schedulePeriod', function ($q) {
                $q->where('status', 'final');
            })
            ->where('tanggal', '>=', today())
            ->where('status', 'kerja')
            ->with(['shift', 'section'])
            ->orderBy('tanggal')
            ->get();

        $allAgents = Agent::where('id', '!=', $agent->id)->get();

        return view('agent.shift-swaps.create', compact('mySchedules', 'allAgents'));
    }

    // Ajax endpoint to get list of agents that have at least one eligible schedule
    // to swap with the requester's selected schedule.
    public function getEligibleResponders(Request $request)
    {
        $agent = Auth::user()->agent;
        if (! $agent) {
            abort(403);
        }

        $request->validate([
            'my_schedule_id' => ['required', 'exists:schedules,id'],
        ]);

        // Hanya kolom yang dipakai: schedule_period_id & section_id. Shift/section
        // tidak dirender di endpoint ini, jadi tidak perlu eager-load.
        $mySchedule = Schedule::findOrFail($request->my_schedule_id);

        if ($mySchedule->agent_id !== $agent->id) {
            abort(403);
        }

        $tanggalSaya = Carbon::parse($mySchedule->tanggal)->toDateString();

        // Semua jadwal kerja requester (untuk cek bentrok tanggal)
        $dateKerjaRequester = Schedule::where('agent_id', $agent->id)
            ->where('status', 'kerja')
            ->pluck('tanggal')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->flip();

        // Ambil semua agent lain yang aktif (bukan diri sendiri)
        $candidates = Agent::where('id', '!=', $agent->id)
            ->aktif()
            ->orderBy('nama_agent')
            ->get();

        $candidateIds = $candidates->pluck('id');

        // Muat data jadwal SEMUA kandidat dalam dua query (bukan 2 query per
        // kandidat / N+1), lalu filter di memori.
        // 1) Jadwal kerja kandidat: periode sama, section sama, dari hari ini ke depan
        $respSchedulesByAgent = Schedule::whereIn('agent_id', $candidateIds)
            ->where('schedule_period_id', $mySchedule->schedule_period_id)
            ->where('tanggal', '>=', today())
            ->where('status', 'kerja')
            ->where('section_id', $mySchedule->section_id)
            ->get(['agent_id', 'tanggal'])
            ->groupBy('agent_id')
            ->map(fn ($rows) => $rows
                ->map(fn ($r) => Carbon::parse($r->tanggal)->toDateString())
                ->unique()
                ->values()
                ->all());

        // 2) Semua tanggal kerja kandidat (semua periode) untuk cek bentrok
        $dateKerjaByAgent = Schedule::whereIn('agent_id', $candidateIds)
            ->where('status', 'kerja')
            ->get(['agent_id', 'tanggal'])
            ->groupBy('agent_id')
            ->map(fn ($rows) => $rows
                ->map(fn ($r) => Carbon::parse($r->tanggal)->toDateString())
                ->flip());

        // Untuk setiap kandidat, cek apakah mereka punya minimal 1 jadwal eligible
        $eligible = $candidates->filter(function ($candidate) use ($mySchedule, $tanggalSaya, $dateKerjaRequester, $respSchedulesByAgent, $dateKerjaByAgent) {
            $respSchedules = $respSchedulesByAgent->get($candidate->id, []);

            if (empty($respSchedules)) {
                return false;
            }

            $dateKerjaResponder = $dateKerjaByAgent->get($candidate->id, collect());

            // Minimal satu tanggal kandidat yang lolos aturan bentrok
            foreach ($respSchedules as $tanggalResp) {
                if ($tanggalResp === $tanggalSaya) {
                    return true; // tukar di hari yang sama selalu ok
                }
                if (isset($dateKerjaRequester[$tanggalResp])) {
                    continue; // requester sudah kerja di tanggal itu
                }
                if (isset($dateKerjaResponder[$tanggalSaya])) {
                    continue; // responder sudah kerja di tanggal requester
                }
                return true;
            }

            return false;
        });

        return response()->json(
            $eligible->map(fn($a) => [
                'id'    => $a->id,
                'nama'  => $a->nama_agent,
                'level' => $a->level,
            ])->values()
        );
    }

    // Ajax endpoint to get responder's eligible schedules
    public function getResponderSchedules(Request $request)
    {
        $agent = Auth::user()->agent;

        if (! $agent) {
            abort(403);
        }

        $request->validate([
            'my_schedule_id' => ['required', 'exists:schedules,id'],
            'responder_id' => ['required', 'exists:agents,id'],
        ]);

        $mySchedule = Schedule::findOrFail($request->my_schedule_id);

        // Otorisasi: hanya jadwal milik sendiri yang boleh dipakai sebagai basis
        // pencarian, supaya agent tidak bisa membongkar jadwal agent lain.
        if ($mySchedule->agent_id !== $agent->id) {
            abort(403);
        }

        // Tidak bisa tukar dengan diri sendiri.
        if ((int) $request->responder_id === $agent->id) {
            return response()->json([]);
        }

        $responderSchedules = Schedule::where('agent_id', $request->responder_id)
            ->where('schedule_period_id', $mySchedule->schedule_period_id)
            ->where('tanggal', '>=', today())
            ->where('status', 'kerja')
            ->where('section_id', $mySchedule->section_id)
            ->with(['shift', 'section'])
            ->orderBy('tanggal')
            ->get();

        if ($responderSchedules->isEmpty()) {
            return response()->json([]);
        }

        // Muat sekali saja tanggal kerja requester & responder (tanpa batasan
        // periode, meniru semantik pengecekan asli) untuk menggantikan query
        // per-kandidat (N+1).
        $dateKerjaRequester = Schedule::where('agent_id', $agent->id)
            ->where('status', 'kerja')
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $dateKerjaResponder = Schedule::where('agent_id', $request->responder_id)
            ->where('status', 'kerja')
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $tanggalSaya = $mySchedule->tanggal->format('Y-m-d');

        $eligible = $responderSchedules->filter(function ($respSchedule) use ($dateKerjaRequester, $dateKerjaResponder, $tanggalSaya) {
            $tanggalResp = Carbon::parse($respSchedule->tanggal)->format('Y-m-d');

            // Tanggal sama -> tukar shift di hari yang sama, tidak mungkin bentrok.
            if ($tanggalResp === $tanggalSaya) {
                return true;
            }

            // Tanggal berbeda: pastikan requester tidak sedang kerja di tanggal
            // responder, dan responder tidak sedang kerja di tanggal requester.
            if (isset($dateKerjaRequester[$tanggalResp])) {
                return false;
            }

            if (isset($dateKerjaResponder[$tanggalSaya])) {
                return false;
            }

            return true;
        })->values();

        return response()->json($eligible);
    }

    public function store(Request $request)
    {
        $agent = Auth::user()->agent;

        $request->validate([
            'requester_schedule_id' => 'required|exists:schedules,id',
            'responder_id' => 'required|exists:agents,id',
            'responder_schedule_id' => 'required|exists:schedules,id',
            'alasan' => 'required|string|max:500',
        ]);

        $mySchedule = Schedule::findOrFail($request->requester_schedule_id);
        $responderSchedule = Schedule::findOrFail($request->responder_schedule_id);

        if ($mySchedule->agent_id !== $agent->id) {
            abort(403);
        }
        if ($responderSchedule->agent_id != $request->responder_id) {
            abort(403);
        }
        if ($mySchedule->section_id !== $responderSchedule->section_id) {
            return back()->with('error', 'Jadwal yang ditukar harus berada di section yang sama.');
        }

        // Cek konflik jika tanggal beda
        if ($mySchedule->tanggal->format('Y-m-d') !== $responderSchedule->tanggal->format('Y-m-d')) {
            $reqConflict = Schedule::where('agent_id', $agent->id)->where('tanggal', $responderSchedule->tanggal->format('Y-m-d'))->where('status', 'kerja')->exists();
            $respConflict = Schedule::where('agent_id', $responderSchedule->agent_id)->where('tanggal', $mySchedule->tanggal->format('Y-m-d'))->where('status', 'kerja')->exists();
            if ($reqConflict || $respConflict) {
                return back()->with('error', 'Terdapat tabrakan jadwal (salah satu agen sudah memiliki shift kerja di tanggal tujuan).');
            }
        }

        $swap = ShiftSwap::create([
            'schedule_period_id' => $mySchedule->schedule_period_id,
            'requester_id' => $agent->id,
            'responder_id' => $responderSchedule->agent_id,
            'requester_schedule_id' => $mySchedule->id,
            'responder_schedule_id' => $responderSchedule->id,
            'status' => 'menunggu_rekan',
            'alasan' => $request->alasan,
        ]);

        // Notify Responder
        $swap->responder->user?->notify(new PermintaanTukarJadwal($swap));

        return redirect()->route('agent.shift-swaps.index')->with('success', 'Permintaan tukar jadwal berhasil dikirim.');
    }

    public function respond(Request $request, ShiftSwap $shiftSwap)
    {
        $agent = Auth::user()->agent;
        if ($shiftSwap->responder_id !== $agent->id) {
            abort(403);
        }
        if ($shiftSwap->status !== 'menunggu_rekan') {
            abort(403);
        }

        $request->validate([
            'action' => ['required', Rule::in(['accept', 'reject'])],
        ]);

        if ($request->action === 'accept') {
            $shiftSwap->update(['status' => 'menunggu_pic']);

            // Notify all PIC
            $pics = User::where('role', 'pic')->get();
            foreach ($pics as $pic) {
                $pic->notify(new TukarJadwalDisepakati($shiftSwap));
            }

            return back()->with('success', 'Anda menyetujui permintaan tukar jadwal. Menunggu persetujuan PIC.');
        } else {
            $shiftSwap->update(['status' => 'ditolak_rekan']);

            return back()->with('success', 'Anda menolak permintaan tukar jadwal.');
        }
    }
}
