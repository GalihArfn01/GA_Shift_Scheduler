<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentScheduleController extends Controller
{
    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /**
     * Tampilkan histori lengkap jadwal pribadi Agent yang sedang login,
     * dikelompokkan per periode, terbaru lebih dulu. Read-only -- tidak
     * ada aksi edit/hapus sama sekali di halaman ini.
     */
    public function index()
    {
        $agent = Auth::user()->agent;

        if (!$agent) {
            return view('agent.schedule-index', [
                'agent'          => null,
                'periodAktif'    => null,
                'hariListAktif'  => [],
                'periodsData'    => collect(),
                'today'          => today()->toDateString(),
            ]);
        }

        $today = today();

        // Ambil semua ID periode yang punya schedule agent ini dan sudah final
        $periodIds = Schedule::where('agent_id', $agent->id)
            ->pluck('schedule_period_id')
            ->unique();

        // Periode yang sedang aktif (tanggal_mulai <= hari ini <= tanggal_selesai)
        // atau periode berikutnya yang paling dekat. Dicari via query terpisah
        // supaya tidak perlu memuat seluruh histori untuk menemukannya.
        $periodAktif = SchedulePeriod::whereIn('id', $periodIds)
            ->where('status', 'final')
            ->where('tanggal_mulai', '<=', $today)
            ->where('tanggal_selesai', '>=', $today)
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (!$periodAktif) {
            $periodAktif = SchedulePeriod::whereIn('id', $periodIds)
                ->where('status', 'final')
                ->where('tanggal_mulai', '>', $today)
                ->orderBy('tanggal_mulai')
                ->first();
        }

        // Periode histori (kecuali yang aktif) — di-paginate di level SQL,
        // hanya 10 periode per halaman, terbaru lebih dulu.
        $periodsPaginated = SchedulePeriod::whereIn('id', $periodIds)
            ->where('status', 'final')
            ->when($periodAktif, fn ($q) => $q->where('id', '!=', $periodAktif->id))
            ->orderByDesc('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        // Eager-load schedule semua periode di halaman ini dalam SATU query
        // (menggantikan 1 query per periode di buildHariListForAgent).
        $schedulesByPeriod = Schedule::whereIn('schedule_period_id', $periodsPaginated->pluck('id'))
            ->where('agent_id', $agent->id)
            ->with(['shift', 'section'])
            ->get()
            ->groupBy('schedule_period_id');

        $periodsData = $periodsPaginated->through(function ($period) use ($agent, $schedulesByPeriod) {
            return [
                'period'   => $period,
                'hariList' => $this->buildHariListForAgent(
                    $period,
                    $agent->id,
                    $schedulesByPeriod->get($period->id, collect())
                ),
            ];
        });

        return view('agent.schedule-index', [
            'agent'         => $agent,
            'periodAktif'   => $periodAktif,
            'hariListAktif' => $periodAktif ? $this->buildHariListForAgent($periodAktif, $agent->id) : [],
            'periodsData'   => $periodsData,
            'today'         => $today->toDateString(),
        ]);
    }

    /**
     * API endpoint — return data jadwal tim sebagai JSON untuk AJAX request.
     * Dipakai oleh view team-schedule.blade.php saat user ganti periode/search
     * tanpa full page reload.
     */
    public function teamScheduleData(Request $request)
    {
        $today = today()->toDateString();

        $allPeriods = SchedulePeriod::where('status', 'final')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $selectedPeriodId = $request->input('period_id');
        $search           = $request->input('search', '');

        $periodAktif = $allPeriods->first(function ($p) {
            $t = today();
            return Carbon::parse($p->tanggal_mulai)->lte($t)
                && Carbon::parse($p->tanggal_selesai)->gte($t);
        });

        $selectedPeriod = $selectedPeriodId
            ? $allPeriods->firstWhere('id', $selectedPeriodId)
            : ($periodAktif ?? $allPeriods->first());

        if (!$selectedPeriod) {
            return response()->json(['headers' => [], 'rows' => [], 'period' => null]);
        }

        $agents = Agent::aktif()
            ->orderBy('nama_agent')
            ->when($search, fn($q) => $q->where('nama_agent', 'like', "%{$search}%"))
            ->get();

        $tanggalList = $this->buildTanggalList($selectedPeriod);
        $headers     = $this->buildTanggalHeaders($selectedPeriod);

        $allSchedules = Schedule::where('schedule_period_id', $selectedPeriod->id)
            ->whereIn('agent_id', $agents->pluck('id'))
            ->with(['shift', 'section'])
            ->get()
            ->groupBy('agent_id');

        $myAgentId = Auth::user()->agent?->id;

        $rows = $agents->map(function ($agent) use ($tanggalList, $allSchedules, $today, $myAgentId) {
            $agentSchedules = ($allSchedules->get($agent->id) ?? collect())
                ->keyBy(fn($s) => Carbon::parse($s->tanggal)->format('Y-m-d'));

            $hariList = [];
            foreach ($tanggalList as $i => $tanggalStr) {
                $schedule   = $agentSchedules->get($tanggalStr);
                $hariList[] = [
                    'tanggal_raw' => $tanggalStr,
                    'is_kerja'    => $schedule && $schedule->status === 'kerja',
                    'kode_shift'  => $schedule?->shift?->kode_shift,
                    'jam_masuk'   => $schedule?->shift?->jam_masuk
                        ? Carbon::parse($schedule->shift->jam_masuk)->format('H:i') : null,
                    'jam_pulang'  => $schedule?->shift?->jam_pulang
                        ? Carbon::parse($schedule->shift->jam_pulang)->format('H:i') : null,
                    'section'     => $schedule?->section?->nama_section,
                ];
            }

            return [
                'id'        => $agent->id,
                'nama'      => $agent->nama_agent,
                'level'     => $agent->level,
                'inisial'   => mb_strtoupper(mb_substr($agent->nama_agent, 0, 1)),
                'is_me'     => $agent->id === $myAgentId,
                'hari_list' => $hariList,
            ];
        })->values();

        return response()->json([
            'period'  => [
                'id'            => $selectedPeriod->id,
                'nama_periode'  => $selectedPeriod->nama_periode,
                'tanggal_mulai' => Carbon::parse($selectedPeriod->tanggal_mulai)->translatedFormat('d M'),
                'tanggal_selesai' => Carbon::parse($selectedPeriod->tanggal_selesai)->translatedFormat('d M Y'),
            ],
            'headers' => $headers,
            'rows'    => $rows,
            'today'   => $today,
        ]);
    }

    /**
     * Tampilkan jadwal seluruh agent tim, dikelompokkan per periode.
     * Agent dapat memfilter periode dan mencari nama agent.
     */
    public function teamSchedule(Request $request)
    {
        $today = today();

        // Ambil semua periode yang sudah final, terbaru lebih dulu
        $allPeriods = SchedulePeriod::where('status', 'final')
            ->orderByDesc('tanggal_mulai')
            ->get();

        // Tentukan periode yang sedang aktif / terdekat sebagai default
        $periodAktif = $allPeriods->first(function ($p) use ($today) {
            return Carbon::parse($p->tanggal_mulai)->lte($today)
                && Carbon::parse($p->tanggal_selesai)->gte($today);
        });

        if (!$periodAktif) {
            $periodAktif = $allPeriods
                ->filter(fn($p) => Carbon::parse($p->tanggal_mulai)->gt($today))
                ->sortBy('tanggal_mulai')
                ->first();
        }

        // Periode yang dipilih user (default ke aktif, fallback ke pertama)
        $selectedPeriodId = $request->input('period_id');
        $selectedPeriod   = $selectedPeriodId
            ? $allPeriods->firstWhere('id', $selectedPeriodId)
            : ($periodAktif ?? $allPeriods->first());

        // Filter nama agent (search)
        $search = $request->input('search', '');

        // Ambil semua agent aktif yang punya jadwal di periode terpilih
        $agentsQuery = Agent::aktif()
            ->orderBy('nama_agent')
            ->when($search, fn($q) => $q->where('nama_agent', 'like', "%{$search}%"));

        $agents = $agentsQuery->get();

        // Bangun data jadwal per agent untuk periode terpilih
        $agentsData = [];
        if ($selectedPeriod) {
            $tanggalList = $this->buildTanggalList($selectedPeriod);

            // Eager load semua schedule periode ini sekaligus, lalu group by agent
            $allSchedules = Schedule::where('schedule_period_id', $selectedPeriod->id)
                ->whereIn('agent_id', $agents->pluck('id'))
                ->with(['shift', 'section'])
                ->get()
                ->groupBy('agent_id');

            foreach ($agents as $agent) {
                $agentSchedules = ($allSchedules->get($agent->id) ?? collect())
                    ->keyBy(fn($s) => Carbon::parse($s->tanggal)->format('Y-m-d'));

                $hariList = [];
                foreach ($tanggalList as $i => $tanggalStr) {
                    $schedule   = $agentSchedules->get($tanggalStr);
                    $tanggal    = Carbon::parse($tanggalStr);

                    $hariList[] = [
                        'nama'        => ucfirst(self::NAMA_HARI[$i % 7]),
                        'tanggal'     => $tanggal->translatedFormat('d M'),
                        'tanggal_raw' => $tanggalStr,
                        'is_kerja'    => $schedule && $schedule->status === 'kerja',
                        'kode_shift'  => $schedule?->shift?->kode_shift,
                        'jam_masuk'   => $schedule?->shift?->jam_masuk,
                        'jam_pulang'  => $schedule?->shift?->jam_pulang,
                        'section'     => $schedule?->section?->nama_section,
                    ];
                }

                $agentsData[] = [
                    'agent'    => $agent,
                    'hariList' => $hariList,
                ];
            }
        }

        return view('agent.team-schedule', [
            'allPeriods'     => $allPeriods,
            'selectedPeriod' => $selectedPeriod,
            'agentsData'     => $agentsData,
            'tanggalHeaders' => $selectedPeriod ? $this->buildTanggalHeaders($selectedPeriod) : [],
            'today'          => $today->toDateString(),
            'search'         => $search,
        ]);
    }

    /**
     * Bangun array tanggal (Y-m-d) untuk 7 hari dalam satu periode.
     */
    protected function buildTanggalList(SchedulePeriod $period): array
    {
        $start = Carbon::parse($period->tanggal_mulai);
        $list  = [];
        for ($i = 0; $i < 7; $i++) {
            $list[] = $start->copy()->addDays($i)->format('Y-m-d');
        }
        return $list;
    }

    /**
     * Bangun array header kolom (nama hari + tanggal singkat) untuk tabel.
     */
    protected function buildTanggalHeaders(SchedulePeriod $period): array
    {
        $start   = Carbon::parse($period->tanggal_mulai);
        $headers = [];
        for ($i = 0; $i < 7; $i++) {
            $tgl       = $start->copy()->addDays($i);
            $headers[] = [
                'nama'        => ucfirst(self::NAMA_HARI[$i]),
                'tanggal'     => $tgl->translatedFormat('d M'),
                'tanggal_raw' => $tgl->format('Y-m-d'),
            ];
        }
        return $headers;
    }

    /**
     * Bangun daftar 7 hari (nama, tanggal, status kerja/off, shift,
     * section) untuk satu periode, khusus milik satu agent.
     *
     * @param \Illuminate\Support\Collection|null $schedules Schedule periode
     *        yang sudah di-load dari luar (untuk menghindari N+1). Jika null,
     *        query dijalankan di sini.
     */
    protected function buildHariListForAgent(SchedulePeriod $period, int $agentId, $schedules = null): array
    {
        $tanggalMulai = Carbon::parse($period->tanggal_mulai);

        if ($schedules === null) {
            $schedules = Schedule::where('schedule_period_id', $period->id)
                ->where('agent_id', $agentId)
                ->with(['shift', 'section'])
                ->get();
        }

        $schedules = $schedules->keyBy(fn ($s) => Carbon::parse($s->tanggal)->format('Y-m-d'));

        $hariList = [];

        for ($i = 0; $i < 7; $i++) {
            $tanggal    = $tanggalMulai->copy()->addDays($i);
            $tanggalStr = $tanggal->format('Y-m-d');
            $schedule   = $schedules->get($tanggalStr);

            $hariList[] = [
                'nama'        => ucfirst(self::NAMA_HARI[$i]),
                'tanggal'     => $tanggal->translatedFormat('d M Y'),
                'tanggal_raw' => $tanggalStr,  // untuk perbandingan "hari ini" di view
                'is_kerja'    => $schedule && $schedule->status === 'kerja',
                'kode_shift'  => $schedule?->shift?->kode_shift,
                'jam_masuk'   => $schedule?->shift?->jam_masuk,
                'jam_pulang'  => $schedule?->shift?->jam_pulang,
                'section'     => $schedule?->section?->nama_section,
            ];
        }

        return $hariList;
    }
}
