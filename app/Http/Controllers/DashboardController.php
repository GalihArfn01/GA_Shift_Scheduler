<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SchedulePeriodController;
use App\Models\Agent;
use App\Models\FormRequestWindow;
use App\Models\OffRequest;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard, kontennya beda total sesuai role:
     * - PIC: ringkasan operasional (jumlah agent, periode terbaru, dst)
     * - Agent: ringkasan pribadi (jadwal terdekat, status off request, dst)
     */
    public function index()
    {
        if (Auth::user()->isAgent()) {
            return $this->agentDashboard();
        }

        return $this->picDashboard();
    }

    /**
     * Dashboard untuk PIC -- sama seperti sebelumnya, tidak ada perubahan
     * logic dari versi sebelum role user ditambahkan.
     */
    protected function picDashboard()
    {
        $totalAgent = Agent::count();
        $agentAktif = Agent::where('status', 'aktif')->count();
        $agentSkorsing = Agent::where('status', 'skorsing')->count();

        $periodeTerbaru = SchedulePeriod::orderByDesc('tanggal_mulai')->first();

        $bulanIni = now()->format('Y-m');
        $inboundSop = SchedulePeriodController::getInboundSopStatus($bulanIni);

        return view('dashboard', [
            'totalAgent'      => $totalAgent,
            'agentAktif'      => $agentAktif,
            'agentSkorsing'   => $agentSkorsing,
            'periodeTerbaru'  => $periodeTerbaru,
            'inboundSop'      => $inboundSop,
        ]);
    }

    /**
     * Dashboard untuk Agent -- ringkasan pribadi: total hari kerja
     * minggu ini & bulan ini, status off request (pending/diterima),
     * dan jadwal terdekat (hari ini/besok).
     */
    protected function agentDashboard()
    {
        $agent = Auth::user()->agent;

        if (!$agent) {
            // Akun agent tanpa data Agent terkait (seharusnya tidak terjadi
            // pada kondisi normal, tapi dijaga agar tidak error fatal).
            return view('agent.dashboard', [
                'agent' => null,
            ]);
        }

        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        $hariKerjaMingguIni = Schedule::where('agent_id', $agent->id)
            ->where('status', 'kerja')
            ->whereBetween('tanggal', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->count();

        $hariKerjaBulanIni = Schedule::where('agent_id', $agent->id)
            ->where('status', 'kerja')
            ->whereBetween('tanggal', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->count();

        $offRequestPending = OffRequest::where('agent_id', $agent->id)
            ->where('status_approval', 'pending')
            ->count();

        $offRequestDiterima = OffRequest::where('agent_id', $agent->id)
            ->where('status_approval', 'diterima')
            ->count();

        $jadwalTerdekat = Schedule::where('agent_id', $agent->id)
            ->where('status', 'kerja')
            ->whereIn('tanggal', [$today->toDateString(), $today->copy()->addDay()->toDateString()])
            ->with(['shift', 'section'])
            ->orderBy('tanggal')
            ->get();

        $formRequestWindow = FormRequestWindow::where('closes_at', '>', now())
            ->orderByDesc('id')
            ->first();

        return view('agent.dashboard', [
            'agent'               => $agent,
            'hariKerjaMingguIni'  => $hariKerjaMingguIni,
            'hariKerjaBulanIni'   => $hariKerjaBulanIni,
            'offRequestPending'   => $offRequestPending,
            'offRequestDiterima'  => $offRequestDiterima,
            'jadwalTerdekat'      => $jadwalTerdekat,
            'formRequestWindow'   => $formRequestWindow,
        ]);
    }
}
