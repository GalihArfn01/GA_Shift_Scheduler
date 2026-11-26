<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\FormRequestWindow;
use App\Models\OperationalRequirement;
use App\Models\OperationalTemplate;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\User;
use App\Notifications\JadwalDifinalisasi;
use App\Models\Section;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchedulePeriodController extends Controller
{
    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /**
     * Tampilkan wizard "Generate Jadwal".
     * Step ditentukan oleh keberadaan schedule_period_id di query string:
     * - Tanpa period_id -> Step 1 (buat periode baru)
     * - Dengan period_id -> Step 2 (isi kebutuhan operasional)
     */
    public function wizard(Request $request)
    {
        $periodId = $request->query('period_id');
        $period = $periodId ? SchedulePeriod::findOrFail($periodId) : null;

        $templates = OperationalTemplate::with('details')->orderBy('nama_template')->get();
        $shifts = Shift::orderBy('jam_masuk')->get();
        $sectionOrder = ['Chat', 'Website', 'Informasi', 'Inbound'];
        $sections = Section::all()->sortBy(function($s) use ($sectionOrder) {
            $pos = array_search($s->nama_section, $sectionOrder);
            return $pos === false ? 99 : $pos;
        })->values();

        $inboundSection = $sections->firstWhere('nama_section', 'Inbound');

        // Urutan baris kebutuhan operasional: Section (Chat > Website > Informasi > Inbound),
        // lalu Shift (berdasarkan jam masuk). Dipakai saat load wizard, dan sebagai acuan
        // sorting otomatis di frontend (baris baru / simpan hari) -- lihat wizard.blade.php.
        $sectionNames = $sections->pluck('nama_section', 'id');
        $sectionRank  = array_flip($sectionOrder);
        $shiftRank    = $shifts->pluck('id')->flip();

        $sortRequirementRows = function (array $rows) use ($sectionNames, $sectionRank, $shiftRank): array {
            return collect($rows)
                ->sortBy(function (array $row) use ($sectionNames, $sectionRank, $shiftRank): int {
                    $secIdx   = $sectionRank[$sectionNames[$row['section_id']] ?? ''] ?? 99;
                    $shiftIdx = $shiftRank[$row['shift_id']] ?? 999;
                    return $secIdx * 1000 + $shiftIdx;
                })
                ->values()
                ->all();
        };

        $templatesData = $templates->map(function ($template) {
            return [
                'id'      => $template->id,
                'details' => $template->details->map(function ($detail) {
                    return [
                        'shift_id'         => (string) $detail->shift_id,
                        'section_id'       => (string) $detail->section_id,
                        'jumlah_kebutuhan' => $detail->jumlah_kebutuhan,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $namaHari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $hariList = [];
        $requirementsByHari = [];

        if ($period) {
            $tanggalMulai = Carbon::parse($period->tanggal_mulai);
            for ($i = 0; $i < 7; $i++) {
                $tanggal = $tanggalMulai->copy()->addDays($i);
                $hariList[] = [
                    'index'   => $i,
                    'nama'    => $namaHari[$i],
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'label'   => $tanggal->translatedFormat('l, d M Y'),
                ];
            }

            $existing = OperationalRequirement::where('schedule_period_id', $period->id)
                ->get()
                ->groupBy('hari_ke');

            foreach ($hariList as $hari) {
                $rowsForDay = $sortRequirementRows(
                    $existing->get($hari['nama'], collect())
                        ->map(fn ($r) => [
                            'shift_id'         => (string) $r->shift_id,
                            'section_id'       => (string) $r->section_id,
                            'jumlah_kebutuhan' => $r->jumlah_kebutuhan,
                        ])
                        ->values()
                        ->all()
                );

                if (empty($rowsForDay)) {
                    $rowsForDay = [
                        ['shift_id' => '', 'section_id' => '', 'jumlah_kebutuhan' => 1],
                    ];
                }

                $requirementsByHari[$hari['index']] = $rowsForDay;
            }
        }

        return view('schedules.wizard', [
            'period'              => $period,
            'templates'           => $templates,
            'templatesData'       => $templatesData,
            'shifts'              => $shifts,
            'sections'            => $sections,
            'hariList'            => $hariList,
            'requirementsByHari'  => $requirementsByHari,
            'filledDays'          => $period ? $existing->keys()->values()->all() : [],
            'inboundDataUrl'      => $period ? route('schedules.inbound.data', $period) : null,
            'inboundSaveUrl'      => $period ? route('schedules.inbound.save', $period) : null,
            'inboundEstimateUrl'  => $period ? route('schedules.inbound.estimate', $period) : null,
            'inboundSectionId'    => $inboundSection ? (string) $inboundSection->id : null,
        ]);
    }

    /**
     * Step 1: simpan periode baru, lanjut ke Step 2.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_periode' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
        ]);

        $tanggalMulai = Carbon::parse($validated['tanggal_mulai']);
        $tanggalSelesai = $tanggalMulai->copy()->addDays(6);

        // Guard Step 1: rentang periode baru tidak boleh beririsan dengan
        // periode yang SUDAH final — jadwal final tidak boleh "ditumpuk"
        // oleh periode baru yang mencakup tanggal yang sama.
        $overlap = $this->finalPeriodOverlapping($tanggalMulai, $tanggalSelesai);
        if ($overlap) {
            return back()
                ->withErrors([
                    'tanggal_mulai' => 'Rentang tanggal ini beririsan dengan periode final "'
                        . $overlap->nama_periode . '" ('
                        . Carbon::parse($overlap->tanggal_mulai)->translatedFormat('d M Y')
                        . ' &ndash; '
                        . Carbon::parse($overlap->tanggal_selesai)->translatedFormat('d M Y')
                        . '). Pilih rentang lain yang tidak bertabrakan.',
                ])
                ->withInput();
        }

        $period = SchedulePeriod::create([
            'nama_periode'    => $validated['nama_periode'],
            'tanggal_mulai'   => $tanggalMulai->format('Y-m-d'),
            'tanggal_selesai' => $tanggalSelesai->format('Y-m-d'),
            'status'          => 'draft',
        ]);

        return redirect()->route('schedules.wizard', ['period_id' => $period->id]);
    }

    /**
     * Cari periode FINAL yang rentang tanggalnya beririsan dengan rentang
     * yang sedang dipilih (mulai s/d selesai). Dipakai store() dan update()
     * untuk mencegah periode baru "menumpuk" di atas jadwal yang sudah
     * difinalkan. $excludeId dipakai saat edit agar periode itu sendiri
     * tidak dianggap konflik.
     */
    protected function finalPeriodOverlapping(Carbon $mulai, Carbon $selesai, ?int $excludeId = null): ?SchedulePeriod
    {
        $mulaiStr   = $mulai->toDateString();
        $selesaiStr = $selesai->toDateString();

        return SchedulePeriod::where('status', 'final')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($mulaiStr, $selesaiStr) {
                $q->whereBetween('tanggal_mulai', [$mulaiStr, $selesaiStr])
                    ->orWhereBetween('tanggal_selesai', [$mulaiStr, $selesaiStr])
                    ->orWhere(function ($q2) use ($mulaiStr, $selesaiStr) {
                        $q2->where('tanggal_mulai', '<=', $mulaiStr)
                            ->where('tanggal_selesai', '>=', $selesaiStr);
                    });
            })
            ->orderByDesc('tanggal_mulai')
            ->first();
    }

    /**
     * Step 2: simpan kebutuhan operasional untuk satu hari tertentu
     * dalam periode ini. Dipanggil lewat AJAX/fetch dari halaman wizard
     * setiap kali SPV apply template atau ubah manual untuk satu hari.
     *
     * Strategi: hapus dulu requirement lama untuk (period, hari) ini,
     * lalu insert ulang sesuai data yang dikirim.
     */
    public function saveRequirement(Request $request, SchedulePeriod $period)
    {
        if ($period->isFinal()) {
            return response()->json(['error' => 'Periode ini sudah final dan tidak bisa diubah.'], 403);
        }

        $validated = $request->validate([
            'hari_ke'                    => ['required', 'string', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'details'                    => ['required', 'array', 'min:1'],
            'details.*.shift_id'         => ['required', 'exists:shifts,id'],
            'details.*.section_id'       => ['required', 'exists:sections,id'],
            'details.*.jumlah_kebutuhan' => ['required', 'integer', 'min:1'],
        ]);

        // Hapus-lalu-insert dalam satu transaksi: kalau proses terputus di
        // tengah (crash/koneksi putus), hari ini tidak akan tertinggal tanpa
        // kebutuhan operasional sama sekali.
        DB::transaction(function () use ($period, $validated) {
            OperationalRequirement::where('schedule_period_id', $period->id)
                ->where('hari_ke', $validated['hari_ke'])
                ->delete();

            foreach ($validated['details'] as $detail) {
                OperationalRequirement::create([
                    'schedule_period_id' => $period->id,
                    'hari_ke'            => $validated['hari_ke'],
                    'shift_id'           => $detail['shift_id'],
                    'section_id'         => $detail['section_id'],
                    'jumlah_kebutuhan'   => $detail['jumlah_kebutuhan'],
                ]);
            }
        });

        return response()->json(['success' => true]);
    }    /**
     * Penanda "Step 2 selesai": kebutuhan operasional untuk ke-7 hari
     * (senin..minggu) sudah tersimpan untuk periode ini.
     *
     * Satu sumber kebenaran yang dipakai bersama oleh history(),
     * showRunPage(), generate(), dan checkComplete() supaya logika
     * "lanjutkan step terakhir" konsisten di semua entry point.
     */
    protected function requirementsComplete(SchedulePeriod $period): bool
    {
        return OperationalRequirement::where('schedule_period_id', $period->id)
            ->distinct()
            ->pluck('hari_ke')
            ->count() === 7;
    }

    /**
     * Cek apakah ketujuh hari sudah terisi kebutuhan operasional.
     * Dipanggil dari frontend sebelum mengizinkan tombol "Generate".
     */
    public function checkComplete(SchedulePeriod $period)
    {
        $hariTerisi = OperationalRequirement::where('schedule_period_id', $period->id)
            ->distinct()
            ->pluck('hari_ke')
            ->all();

        return response()->json([
            'lengkap'      => $this->requirementsComplete($period),
            'hari_terisi'  => $hariTerisi,
        ]);
    }

    // =========================================================
    // INBOUND ASSIGNMENT (Toggle per periode)
    // =========================================================

    /**
     * Simpan daftar agent yang mendapat giliran Inbound periode ini.
     * Level 1 selalu eligible (tidak perlu disimpan di pivot).
     * Level 2 & 3 dikontrol PIC lewat toggle — hanya yang toggle ON
     * yang masuk tabel ini.
     */
    public function saveInboundAssignments(Request $request, SchedulePeriod $period)
    {
        if ($period->isFinal()) {
            return response()->json(['error' => 'Periode ini sudah final dan tidak bisa diubah.'], 403);
        }

        $validated = $request->validate([
            'agent_ids'   => ['present', 'array'],
            'agent_ids.*' => ['integer', 'exists:agents,id'],
        ]);

        $period->inboundAgents()->sync($validated['agent_ids']);

        return response()->json(['success' => true]);
    }

    /**
     * Kembalikan data lengkap untuk panel toggle Inbound:
     * - Daftar agent Level 2 & 3 aktif beserta status toggle (sudah dapat
     *   Inbound bulan ini atau belum) dan assignment yang sudah tersimpan.
     * - Estimasi slot Inbound yang tersedia dari kebutuhan operasional.
     * - Informasi apakah ini periode terakhir bulan ini.
     */
    public function getInboundData(SchedulePeriod $period)
    {
        $tanggalMulai   = Carbon::parse($period->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($period->tanggal_selesai);

        // Tentukan bulan yang relevan untuk SOP:
        // Jika periode span dua bulan (misal 28 Jul - 3 Agt),
        // pakai bulan dengan lebih banyak hari dalam periode ini.
        $hariDiBulanMulai   = $tanggalMulai->daysInMonth - $tanggalMulai->day + 1;
        $hariDiBulanSelesai = $tanggalSelesai->day;

        if ($tanggalMulai->format('Y-m') === $tanggalSelesai->format('Y-m')) {
            // Satu bulan penuh — pakai bulan itu
            $bulanTahun = $tanggalMulai->format('Y-m');
        } elseif ($hariDiBulanMulai >= $hariDiBulanSelesai) {
            // Lebih banyak hari di bulan mulai
            $bulanTahun = $tanggalMulai->format('Y-m');
        } else {
            // Lebih banyak hari di bulan selesai
            $bulanTahun = $tanggalSelesai->format('Y-m');
        }

        [$tahun, $bulan] = explode('-', $bulanTahun);
        $bulanStart = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $bulanEnd   = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();

        // Agent Level 2 & 3 aktif
        // PENTING: level dibandingkan sebagai STRING ('2', '3'), bukan integer.
        // Kolom `level` adalah ENUM -- kalau dibandingkan dengan angka, MySQL
        // mencocokkannya berdasarkan INDEX posisi enum (bukan nilai stringnya).
        // Sejak enum diubah jadi ('0','1','2','3') untuk level Training, index
        // '2' dan '3' sudah tidak sama lagi dengan string '2'/'3' -- makanya
        // harus eksplisit string supaya MySQL membandingkan nilainya, bukan index.
        $agents = Agent::aktif()
            ->whereIn('level', ['2', '3'])
            ->orderBy('level')
            ->orderBy('nama_agent')
            ->get(['id', 'nama_agent', 'level']);

        // Agent yang sudah tersimpan di pivot untuk periode ini
        $assignedIds = $period->inboundAgents()->pluck('agent_id')->all();

        // Agent yang sudah dapat Inbound berdasarkan tanggal aktual jadwal
        // di bulan yang relevan ($bulanStart - $bulanEnd), dari periode final lain
        $inboundSection = Section::where('nama_section', 'Inbound')->first();
        $sudahInboundIds = [];
        $sudahInboundInfo = [];

        if ($inboundSection) {
            $periodeFinal = SchedulePeriod::where('status', 'final')
                ->where('id', '!=', $period->id)
                ->get()
                ->filter(function ($p) use ($bulanStart, $bulanEnd) {
                    $mulai   = Carbon::parse($p->tanggal_mulai)->toDateString();
                    $selesai = Carbon::parse($p->tanggal_selesai)->toDateString();
                    return $mulai <= $bulanEnd && $selesai >= $bulanStart;
                });

            if ($periodeFinal->isNotEmpty()) {
                $rows = Schedule::whereIn('schedule_period_id', $periodeFinal->pluck('id'))
                    ->where('section_id', $inboundSection->id)
                    ->where('status', 'kerja')
                    ->whereBetween('tanggal', [$bulanStart, $bulanEnd])
                    ->with('schedulePeriod:id,nama_periode')
                    ->get(['agent_id', 'schedule_period_id', 'tanggal']);

                foreach ($rows as $row) {
                    $sudahInboundIds[] = $row->agent_id;
                    if (!isset($sudahInboundInfo[$row->agent_id])) {
                        $sudahInboundInfo[$row->agent_id] = $row->schedulePeriod->nama_periode ?? '-';
                    }
                }
                $sudahInboundIds = array_unique($sudahInboundIds);
            }
        }

        // Total slot Inbound tersedia dari kebutuhan operasional periode ini
        $totalSlotInbound = 0;
        if ($inboundSection) {
            $totalSlotInbound = OperationalRequirement::where('schedule_period_id', $period->id)
                ->where('section_id', $inboundSection->id)
                ->sum('jumlah_kebutuhan');
        }

        // Jumlah agent Level 1 aktif (selalu masuk pool Inbound, tidak perlu toggle)
        // String, bukan integer -- lihat catatan di getInboundData() soal
        // perbandingan ENUM level dengan angka yang salah cocok ke index.
        $jumlahLevel1 = Agent::aktif()->where('level', '1')->count();

        // Apakah masih ada periode lain setelah ini yang mencakup bulan yang sama?
        $adaPeriodeBerikutnya = SchedulePeriod::where('id', '!=', $period->id)
            ->get()
            ->filter(function ($p) use ($bulanStart, $bulanEnd, $tanggalMulai) {
                $pMulai   = Carbon::parse($p->tanggal_mulai)->toDateString();
                $pSelesai = Carbon::parse($p->tanggal_selesai)->toDateString();
                return Carbon::parse($p->tanggal_mulai)->gt($tanggalMulai)
                    && $pMulai <= $bulanEnd
                    && $pSelesai >= $bulanStart;
            })
            ->isNotEmpty();

        $agentData = $agents->map(function ($agent) use ($assignedIds, $sudahInboundIds, $sudahInboundInfo) {
            $sudahDapat = in_array($agent->id, $sudahInboundIds);
            return [
                'id'              => $agent->id,
                'nama_agent'      => $agent->nama_agent,
                'level'           => $agent->level,
                // Default ON jika belum dapat inbound bulan ini, kecuali sudah
                // tersimpan eksplisit di pivot untuk periode ini (toggle ON).
                'toggle_on'       => in_array($agent->id, $assignedIds) || !$sudahDapat,
                'sudah_dapat'     => $sudahDapat,
                'nama_periode_dapat' => $sudahInboundInfo[$agent->id] ?? null,
            ];
        });

        return response()->json([
            'agents'                 => $agentData->values(),
            'total_slot_inbound'     => (int) $totalSlotInbound,
            'jumlah_level1'          => $jumlahLevel1,
            'ada_periode_berikutnya' => $adaPeriodeBerikutnya,
            'bulan_label'            => Carbon::createFromFormat('Y-m', $bulanTahun)->translatedFormat('F Y'),
        ]);
    }

    /**
     * Hitung estimasi sisa slot Inbound untuk Level 1 berdasarkan
     * agent_ids yang sudah di-toggle ON (Level 2+3).
     * Dipakai untuk update real-time di panel toggle.
     */
    public function getInboundEstimate(Request $request, SchedulePeriod $period)
    {
        $toggleOnCount = (int) $request->query('toggle_on_count', 0);

        $inboundSection = Section::where('nama_section', 'Inbound')->first();
        $totalSlot = $inboundSection
            ? (int) OperationalRequirement::where('schedule_period_id', $period->id)
                ->where('section_id', $inboundSection->id)
                ->sum('jumlah_kebutuhan')
            : 0;

        // String, bukan integer -- lihat catatan di getInboundData() soal
        // perbandingan ENUM level dengan angka yang salah cocok ke index.
        $jumlahLevel1 = Agent::aktif()->where('level', '1')->count();
        $slotUntukLevel1 = max(0, $totalSlot - $toggleOnCount);
        $rataRataLevel1  = $jumlahLevel1 > 0
            ? round($slotUntukLevel1 / $jumlahLevel1, 1)
            : 0;

        return response()->json([
            'total_slot'          => $totalSlot,
            'toggle_on_count'     => $toggleOnCount,
            'slot_untuk_level1'   => $slotUntukLevel1,
            'jumlah_level1'       => $jumlahLevel1,
            'rata_rata_level1'    => $rataRataLevel1,
        ]);
    }

    /**
     * Hitung status SOP Inbound bulan berjalan — dipakai di dashboard PIC.
     * Mengembalikan daftar agent aktif yang belum dapat Inbound di bulan ini,
     * beserta apakah masih ada periode aktif (draft) untuk menyelesaikannya.
     */
    public static function getInboundSopStatus(string $bulanTahun): array
    {
        $inboundSection = Section::where('nama_section', 'Inbound')->first();
        if (!$inboundSection) {
            return ['sudah' => 0, 'belum' => 0, 'total' => 0, 'belum_agents' => [], 'ada_periode_aktif' => false];
        }

        [$tahun, $bulan] = explode('-', $bulanTahun);

        // Tentukan rentang tanggal bulan ini
        $bulanStart = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $bulanEnd   = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();

        // Cari periode final yang punya hari dalam bulan ini (bukan hanya yang mulai di bulan ini)
        $periodeFinalBulanIni = SchedulePeriod::where('status', 'final')
            ->get()
            ->filter(function ($p) use ($bulanStart, $bulanEnd) {
                $mulai   = Carbon::parse($p->tanggal_mulai)->toDateString();
                $selesai = Carbon::parse($p->tanggal_selesai)->toDateString();
                return $mulai <= $bulanEnd && $selesai >= $bulanStart;
            })
            ->pluck('id');

        // Cek jadwal Inbound yang tanggalnya jatuh di bulan ini
        $sudahInboundIds = Schedule::whereIn('schedule_period_id', $periodeFinalBulanIni)
            ->where('section_id', $inboundSection->id)
            ->where('status', 'kerja')
            ->whereBetween('tanggal', [$bulanStart, $bulanEnd])
            ->pluck('agent_id')
            ->unique()
            ->all();

        $semuaAgentAktif = Agent::aktif()->get(['id', 'nama_agent', 'level']);

        $belumAgents = $semuaAgentAktif
            ->filter(fn ($a) => !in_array($a->id, $sudahInboundIds))
            ->values()
            ->map(fn ($a) => ['id' => $a->id, 'nama_agent' => $a->nama_agent, 'level' => $a->level])
            ->all();

        // Ada periode aktif (draft) yang punya hari di bulan ini
        $adaPeriodeAktif = SchedulePeriod::where('status', 'draft')
            ->get()
            ->filter(function ($p) use ($bulanStart, $bulanEnd) {
                $mulai   = Carbon::parse($p->tanggal_mulai)->toDateString();
                $selesai = Carbon::parse($p->tanggal_selesai)->toDateString();
                return $mulai <= $bulanEnd && $selesai >= $bulanStart;
            })
            ->isNotEmpty();

        return [
            'sudah'             => count($sudahInboundIds),
            'belum'             => count($belumAgents),
            'total'             => $semuaAgentAktif->count(),
            'belum_agents'      => $belumAgents,
            'ada_periode_aktif' => $adaPeriodeAktif,
        ];
    }

    /**
     * Step 3: tampilkan halaman Generate & Review.
     * - Jika belum ada hasil (schedules kosong untuk periode ini): tampilkan
     *   tombol "Generate Jadwal" saja.
     * - Jika sudah ada hasil draft: tampilkan grid + detail per hari + tombol
     *   "Simpan sebagai Final".
     */
    public function showRunPage(SchedulePeriod $period)
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        // Belum ada hasil dan kebutuhan operasional belum lengkap (7 hari) ->
        // lanjutkan dari Step 2 (wizard) alih-alih menampilkan halaman run kosong.
        if (!$hasResult && !$period->isFinal() && !$this->requirementsComplete($period)) {
            return redirect()
                ->route('schedules.wizard', ['period_id' => $period->id])
                ->with('info', 'Lengkapi kebutuhan operasional (7 hari) periode ini sebelum generate.');
        }

        // Cek apakah ada job yang sedang berjalan (pending/running) untuk periode ini.
        // Jika ada, tampilkan processing card terlepas dari apakah sudah ada hasil lama.
        $activeJob = DB::table('schedule_job_statuses')
            ->where('schedule_period_id', $period->id)
            ->whereIn('status', ['pending', 'running'])
            ->orderByDesc('id')
            ->first();

        $viewData = [
            'period'      => $period,
            'hasResult'   => $hasResult,
            'isProcessing' => $activeJob !== null,
            'activeJobId'  => $activeJob?->id,
        ];

        if ($hasResult && !$activeJob) {
            $viewData = array_merge($viewData, $this->buildReviewData($period));

            // Ambil summary dari job done terakhir jika tidak ada session flash
            if (!session('ga_summary')) {
                $lastJob = DB::table('schedule_job_statuses')
                    ->where('schedule_period_id', $period->id)
                    ->where('status', 'done')
                    ->orderByDesc('id')
                    ->first();

                if ($lastJob && $lastJob->result_summary) {
                    $viewData['ga_summary_from_db'] = json_decode($lastJob->result_summary, true);
                    // Sertakan config yang dipakai saat generate tersebut
                    if (!empty($lastJob->ga_config)) {
                        $viewData['ga_config_used'] = json_decode($lastJob->ga_config, true);
                    }
                    $viewData['ga_started_at']  = $lastJob->started_at ?? null;
                    $viewData['ga_finished_at'] = $lastJob->finished_at ?? null;
                }
            }
        }

        return view('schedules.run', $viewData);
    }

    /**
     * Spawn proses GA di background via PHP CLI, langsung return ke browser.
     * Status ditrack di tabel schedule_job_statuses, browser polling via
     * endpoint pollGaStatus() setiap beberapa detik.
     */
    public function generate(SchedulePeriod $period)
    {
        if ($period->isFinal()) {
            return redirect()
                ->route('schedules.generate.run', $period)
                ->with('error', 'Periode ini sudah final dan tidak bisa di-generate ulang.');
        }

        // Step 2 belum selesai (kebutuhan operasional belum lengkap) ->
        // jangan jalankan GA; arahkan kembali ke wizard untuk melanjutkan.
        if (!$this->requirementsComplete($period)) {
            return redirect()
                ->route('schedules.wizard', ['period_id' => $period->id])
                ->with('info', 'Lengkapi kebutuhan operasional (7 hari) periode ini sebelum generate.');
        }

        // Ambil konfigurasi GA dari request (optional — jika tidak ada pakai default)
        $gaConfig = [];
        $configFields = [
            'population_size', 'max_generations', 'mutation_rate',
            'elite_rate', 'tournament_size', 'stagnation_limit',
            'weight_sc1', 'weight_sc2', 'weight_sc3', 'weight_sc4',
            'weight_sc5', 'weight_sc6', 'weight_sc7', 'weight_sc8',
        ];
        foreach ($configFields as $field) {
            if (request()->filled($field)) {
                $gaConfig[$field] = request()->input($field);
            }
        }

        // Batalkan job lama yang masih pending/running untuk periode ini
        // (misalnya user klik Generate dua kali)
        \DB::table('schedule_job_statuses')
            ->where('schedule_period_id', $period->id)
            ->whereIn('status', ['pending', 'running'])
            ->update(['status' => 'failed', 'error_message' => 'Dibatalkan karena ada generate baru.', 'updated_at' => now()]);

        // Buat record job baru dengan status pending
        $jobId = \DB::table('schedule_job_statuses')->insertGetId([
            'schedule_period_id' => $period->id,
            'status'             => 'pending',
            'ga_config'          => !empty($gaConfig) ? json_encode($gaConfig) : null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $configPayload = !empty($gaConfig) ? $gaConfig : null;

        // Spawn proses CLI terpisah agar request HTTP tidak timeout.
        // Tidak bergantung pada queue worker (php artisan queue:work).
        $this->spawnBackgroundGaProcess($period->id, $jobId, $configPayload);

        return redirect()
            ->route('schedules.generate.run', $period)
            ->with('ga_job_id', $jobId)
            ->with('info', 'Generate jadwal sedang berjalan di background. Halaman akan otomatis memuat hasil setelah selesai.');
    }

    /**
     * Endpoint polling — dipanggil browser setiap beberapa detik.
     * Kembalikan status job dan summary jika sudah done.
     */
    public function pollGaStatus(SchedulePeriod $period)
    {
        $job = \DB::table('schedule_job_statuses')
            ->where('schedule_period_id', $period->id)
            ->orderByDesc('id')
            ->first();

        if (!$job) {
            return response()->json(['status' => 'none']);
        }

        // Safety net: job yang tidak pernah mulai (pending) lebih dari batas waktu
        // berarti proses background gagal di-spawn/detach (misal masalah di Windows).
        // Tandai failed supaya UI menampilkan pesan error, bukan polling tanpa batas.
        if ($job->status === 'pending' && $job->created_at && Carbon::parse($job->created_at)->lt(now()->subMinutes(5))) {
            \DB::table('schedule_job_statuses')
                ->where('id', $job->id)
                ->update([
                    'status'        => 'failed',
                    'error_message' => 'Proses background tidak pernah dimulai. Kemungkinan proses gagal di-spawn; silakan coba generate ulang.',
                    'finished_at'   => now(),
                    'updated_at'    => now(),
                ]);

            $job->status        = 'failed';
            $job->error_message = 'Proses background tidak pernah dimulai. Kemungkinan proses gagal di-spawn; silakan coba generate ulang.';
            $job->finished_at   = now();
        }

        // Safety net: job 'running' yang macet (proses mati setelah status
        // di-update ke running, misal dibunuh saat request selesai / server
        // restart / spawn gagal sebagian). Tidak pernah selesai sendiri —
        // tandai failed setelah batas waktu supaya UI menampilkan error,
        // bukan "Sedang Berjalan..." tanpa batas.
        if ($job->status === 'running' && $job->started_at
            && Carbon::parse($job->started_at)->lt(now()->subMinutes(30))) {
            \DB::table('schedule_job_statuses')
                ->where('id', $job->id)
                ->update([
                    'status'        => 'failed',
                    'error_message' => 'Proses GA tampaknya terhenti (berjalan lebih dari 30 menit tanpa hasil). Silakan coba generate ulang.',
                    'finished_at'   => now(),
                    'updated_at'    => now(),
                ]);

            $job->status        = 'failed';
            $job->error_message = 'Proses GA tampaknya terhenti (berjalan lebih dari 30 menit tanpa hasil). Silakan coba generate ulang.';
            $job->finished_at   = now();
        }

        $payload = [
            'status' => $job->status,
            'job_id' => $job->id,
        ];

        if ($job->started_at) {
            $started = Carbon::parse($job->started_at);
            $ended   = $job->finished_at ? Carbon::parse($job->finished_at) : now();

            $payload['started_at']       = $job->started_at;
            $payload['elapsed_seconds']  = $started->diffInSeconds($ended, true);
        }

        if ($job->status === 'done' && $job->result_summary) {
            $payload['summary']     = json_decode($job->result_summary, true);
            $payload['ga_config']     = $job->ga_config ? json_decode($job->ga_config, true) : null;
            $payload['finished_at']   = $job->finished_at;

            if ($job->started_at && $job->finished_at) {
                $payload['duration_seconds'] = Carbon::parse($job->started_at)
                    ->diffInSeconds(Carbon::parse($job->finished_at), true);
            }
        }

        if ($job->status === 'failed') {
            $payload['error']       = $job->error_message;
            $payload['finished_at'] = $job->finished_at;
        }

        return response()->json($payload);
    }

    /**
     * Spawn proses artisan ga:run di background dengan detach sejati:
     * - Windows: cmd /C start /B (melepas dari lifecycle request);
     * - Linux: nohup ... & (proses tidak dibunuh saat request selesai).
     * Tidak bergantung pada queue worker (php artisan queue:work).
     */
    protected function spawnBackgroundGaProcess(int $periodId, int $jobId, ?array $gaConfig): void
    {
        $command = [
            $this->findPhpBinary(),
            base_path('artisan'),
            'ga:run',
            (string) $periodId,
            (string) $jobId,
        ];

        // Konfigurasi GA tidak dilempar lewat CLI (rawan rusak saat di-escape
        // cmd.exe); runner mengambilnya dari kolom ga_config di tabel
        // schedule_job_statuses.
        $logFile = storage_path('logs/ga-' . $periodId . '-' . $jobId . '.log');

        // Di Windows, proses yang di-spawn langsung dari dalam request web
        // (Apache/php-cgi) ikut dimatikan begitu request selesai, sehingga job
        // terjebak selamanya di status pending ("Menunggu proses dimulai...").
        // `cmd /C start /B` melepas proses dari lifecycle request dan membuatnya
        // tetap berjalan di background. Argumen "" pertama adalah judul jendela
        // (wajib) agar path di belakangnya tidak dianggap judul oleh `start`.
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'cmd /C start /B "" "' . $command[0] . '"';
            for ($i = 1; $i < count($command); $i++) {
                $cmd .= ' "' . str_replace('"', '""', $command[$i]) . '"';
            }
            $cmd .= ' >> "' . str_replace('"', '""', $logFile) . '" 2>&1';
            pclose(popen($cmd, 'r'));
            return;
        }

        // Linux/shared hosting: lepas proses dari lifecycle request.
        // Symfony Process (start + destruct) MEMBUNUH proses anak begitu
        // request selesai — job GA mati di tengah dan status macet di
        // 'pending'/'running' selamanya (gejala: halaman "Algoritma Sedang
        // Berjalan..." tidak pernah selesai). `nohup ... &` melepaskan
        // proses dari shell request sehingga tetap berjalan di background.
        $cmd = 'nohup ' . implode(' ', array_map('escapeshellarg', $command))
            . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';

        if (function_exists('popen')) {
            pclose(popen($cmd, 'r'));
        } elseif (function_exists('exec')) {
            exec($cmd);
        } else {
            abort(500, 'Server tidak mendukung eksekusi proses background (popen/exec dinonaktifkan).');
        }
    }

    /**
     * Cari path PHP CLI yang tersedia di server.
     */
    protected function findPhpBinary(): string
    {
        $candidates = [
            PHP_BINARY,            // binary yang menjalankan PHP sekarang (paling akurat)
            '/usr/bin/php',
            '/usr/local/bin/php',
        ];

        foreach ($candidates as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return 'php'; // fallback ke PATH
    }

    /**
     * Tandai periode ini sebagai final. Setelah ini, jadwal tidak bisa
     * di-generate ulang (lihat pengecekan di method generate()).
     */
    public function finalize(SchedulePeriod $period)
    {
        if ($period->isFinal()) {
            return redirect()
                ->route('schedules.history')
                ->with('error', 'Periode ini sudah bersifat final.');
        }

        if (!Schedule::where('schedule_period_id', $period->id)->exists()) {
            return redirect()
                ->route('schedules.generate.run', $period)
                ->with('error', 'Belum ada hasil jadwal untuk difinalkan. Generate jadwal terlebih dahulu.');
        }

        $period->update(['status' => 'final']);

        // Kirim notifikasi ke semua Agent
        User::where('role', 'agent')->each(function ($agent) use ($period) {
            $agent->notify(new JadwalDifinalisasi($period));
        });

        return redirect()
            ->route('schedules.form-request-window.create', $period)
            ->with('success', 'Jadwal berhasil disimpan sebagai final.');
    }

    /**
     * STEP 4 WIZARD: tampilkan form checklist 7 hari untuk membuka Form
     * Request periode berikutnya. Hanya bisa diakses untuk periode yang
     * SUDAH final (Step 3 selesai). Rentang tanggal target dihitung
     * otomatis dari tanggal_selesai periode ini + 1 hari, sampai 7 hari
     * ke depan -- TIDAK perlu SchedulePeriod periode berikutnya sudah
     * dibuat/terdaftar di database.
     */
    public function showFormRequestWindowPage(SchedulePeriod $period)
    {
        if ($period->status !== 'final') {
            return redirect()
                ->route('schedules.generate.run', $period)
                ->with('error', 'Step ini hanya tersedia setelah jadwal di-final-kan.');
        }

        $tanggalMulaiTarget = Carbon::parse($period->tanggal_selesai)->addDay();

        $hariList = [];
        for ($i = 0; $i < 7; $i++) {
            $tanggal = $tanggalMulaiTarget->copy()->addDays($i);
            $hariList[] = [
                'key'     => self::NAMA_HARI[$i],
                'label'   => ucfirst(self::NAMA_HARI[$i]),
                'tanggal' => $tanggal->translatedFormat('d M Y'),
            ];
        }

        // Kalau window untuk periode ini SUDAH pernah dibuat sebelumnya
        // (misal PIC reload halaman ini), tampilkan state itu, jangan
        // buat baru lagi.
        $existingWindow = FormRequestWindow::where('schedule_period_id', $period->id)->first();

        return view('schedules.form-request-window', [
            'period'             => $period,
            'tanggalMulaiTarget' => $tanggalMulaiTarget,
            'hariList'           => $hariList,
            'existingWindow'     => $existingWindow,
        ]);
    }

    /**
     * Simpan pilihan PIC (hari mana yang diaktifkan), atau lewati tanpa
     * memilih apa pun (= tidak membuka Form Request sama sekali untuk
     * periode berikutnya). Wajib melalui step ini, tapi isinya boleh
     * kosong.
     */
    public function storeFormRequestWindow(Request $request, SchedulePeriod $period)
    {
        if ($period->status !== 'final') {
            abort(403);
        }

        $validated = $request->validate([
            'hari_aktif'   => ['nullable', 'array'],
            'hari_aktif.*' => ['string', Rule::in(self::NAMA_HARI)],
        ]);

        $hariAktif = $validated['hari_aktif'] ?? [];

        // Hapus-lalu-insert dalam satu transaksi supaya window lama tidak
        // hilang tanpa penggantinya bila proses terputus di tengah.
        DB::transaction(function () use ($period, $hariAktif) {
            FormRequestWindow::where('schedule_period_id', $period->id)->delete();

            if (!empty($hariAktif)) {
                FormRequestWindow::create([
                    'schedule_period_id' => $period->id,
                    'tanggal_mulai'      => Carbon::parse($period->tanggal_selesai)->addDay(),
                    'hari_aktif'         => $hariAktif,
                    'opened_at'          => now(),
                    'closes_at'          => now()->addDays(3),
                ]);
            }
        });

        return redirect()
            ->route('schedules.history')
            ->with('success', empty($hariAktif)
                ? 'Jadwal sudah final. Form Request untuk periode berikutnya tidak dibuka.'
                : 'Jadwal sudah final. Form Request berhasil dibuka untuk ' . count($hariAktif) . ' hari yang dipilih.');
    }

    /**
     * Simpan hasil kromosom terbaik GA ke tabel schedules.
     * Setiap agent AKTIF mendapat tepat 1 baris per hari (7 hari):
     * status 'kerja' (shift_id & section_id terisi) jika ditugaskan,
     * atau status 'off' (keduanya null) jika tidak.
     *
     * Strategi: hapus dulu semua baris schedules lama untuk periode ini,
     * lalu insert ulang dari kromosom terbaru (replace, bukan merge).
     */
    protected function saveGeneratedSchedule(SchedulePeriod $period, array $chromosome): void
    {
        // Catatan: implementasi aktif ada di GeneticAlgorithmJobRunner; method
        // ini dipertahankan hanya agar pola hapus-lalu-insert ikut terlindungi
        // transaksi bila suatu saat dipanggil dari tempat lain.
        DB::transaction(function () use ($period, $chromosome) {
            Schedule::where('schedule_period_id', $period->id)->delete();

            $tanggalMulai = Carbon::parse($period->tanggal_mulai);
            $agentIdsAktif = Agent::aktif()->pluck('id')->all();

            // grid[agent_id][hari_index] = ['shift_id' => int, 'section_id' => int]
            $grid = [];
            foreach ($chromosome as $gen) {
                if ($gen['agent_id'] !== null) {
                    $grid[$gen['agent_id']][$gen['hari_index']] = [
                        'shift_id'   => $gen['shift_id'],
                        'section_id' => $gen['section_id'],
                    ];
                }
            }

            $rows = [];
            $now = now();

            foreach ($agentIdsAktif as $agentId) {
                for ($hariIndex = 0; $hariIndex < 7; $hariIndex++) {
                    $tanggal = $tanggalMulai->copy()->addDays($hariIndex)->format('Y-m-d');
                    $assignment = $grid[$agentId][$hariIndex] ?? null;

                    $rows[] = [
                        'schedule_period_id' => $period->id,
                        'agent_id'           => $agentId,
                        'tanggal'            => $tanggal,
                        'shift_id'           => $assignment['shift_id'] ?? null,
                        'section_id'         => $assignment['section_id'] ?? null,
                        'status'             => $assignment ? 'kerja' : 'off',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                Schedule::insert($chunk);
            }
        });
    }

    /**
     * Bangun data untuk halaman review: grid mingguan (agent x hari)
     * dan breakdown per hari (siapa kerja shift apa di section apa).
     */
    protected function buildReviewData(SchedulePeriod $period): array
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

        $agentIdsWithSchedules = $schedules->pluck('agent_id')->unique();
        $agents = Agent::withTrashed()
            ->where(function ($query) use ($agentIdsWithSchedules) {
                // Tampilkan semua agen yang belum dihapus (termasuk skorsing)
                $query->whereNull('deleted_at');
                if ($agentIdsWithSchedules->isNotEmpty()) {
                    $query->orWhereIn('id', $agentIdsWithSchedules);
                }
            })
            ->orderBy('level')
            ->orderBy('nama_agent')
            ->get();

        // map tanggal (Y-m-d) -> hari_index, dibangun sekali dari hariList
        $hariIndexByTanggal = [];
        foreach ($hariList as $hari) {
            $hariIndexByTanggal[$hari['tanggal']] = $hari['index'];
        }

        // grid[agent_id][hari_index] = Schedule
        $grid = [];
        foreach ($schedules as $s) {
            $tanggalStr = Carbon::parse($s->tanggal)->format('Y-m-d');
            $hariIndex = $hariIndexByTanggal[$tanggalStr] ?? null;
            if ($hariIndex !== null) {
                $grid[$s->agent_id][$hariIndex] = $s;
            }
        }

        // perHari[hari_index] = collection of Schedule berstatus kerja, diurutkan section+shift
        $perHari = [];
        foreach ($hariList as $hari) {
            $perHari[$hari['index']] = $schedules
                ->filter(function ($s) use ($hariIndexByTanggal, $hari) {
                    $tanggalStr = Carbon::parse($s->tanggal)->format('Y-m-d');
                    return ($hariIndexByTanggal[$tanggalStr] ?? null) === $hari['index'] && $s->status === 'kerja';
                })
                ->sortBy(fn ($s) => ($s->section->nama_section ?? '') . '-' . ($s->shift->kode_shift ?? ''))
                ->values();
        }

        $kebutuhan = OperationalRequirement::where('schedule_period_id', $period->id)->get();
        $totalKebutuhan = (int) $kebutuhan->sum('jumlah_kebutuhan');

        $totalTerisi = $schedules->where('status', 'kerja')->count();

        $unfilledCount = max(0, $totalKebutuhan - $totalTerisi);

        // Check unfulfilled status per day
        $unfulfilledDays = [];
        foreach ($hariList as $hari) {
            $idx = $hari['index'];
            $reqPerDay = $kebutuhan->where('hari_ke', self::NAMA_HARI[$idx])->sum('jumlah_kebutuhan');
            $filledPerDay = count($perHari[$idx]);
            $unfulfilledDays[$idx] = $filledPerDay < $reqPerDay;
        }

        // Summary Grid
        $sections = \App\Models\Section::all();
        $shifts = \App\Models\Shift::orderBy('jam_masuk')->get();

        $sectionOrder = ['Chat', 'Website', 'Informasi', 'Inbound'];
        $sections = $sections->sortBy(function($s) use ($sectionOrder) {
            $pos = array_search($s->nama_section, $sectionOrder);
            return $pos === false ? 99 : $pos;
        })->values();

        $summaryGrid = [];
        foreach ($sections as $sec) {
            $summaryGrid[$sec->nama_section] = [];
            foreach ($shifts as $shf) {
                $hasData = false;
                $row = [];
                foreach ($hariList as $hari) {
                    $idx = $hari['index'];
                    $keb = $kebutuhan->where('hari_ke', self::NAMA_HARI[$idx])
                                    ->where('section_id', $sec->id)
                                    ->where('shift_id', $shf->id)
                                    ->sum('jumlah_kebutuhan');
                    
                    $ter = $schedules->where('status', 'kerja')
                                     ->where('section_id', $sec->id)
                                     ->where('shift_id', $shf->id)
                                     ->filter(function($s) use ($hariIndexByTanggal, $idx) {
                                         $tStr = \Carbon\Carbon::parse($s->tanggal)->format('Y-m-d');
                                         return ($hariIndexByTanggal[$tStr] ?? null) === $idx;
                                     })->count();
                    
                    if ($keb > 0 || $ter > 0) $hasData = true;
                    
                    $row[$idx] = [
                        'kebutuhan' => $keb,
                        'terisi' => $ter,
                        'shift_detail' => $shf->kode_shift . ' [' . substr($shf->jam_masuk, 0, 5) . '-' . substr($shf->jam_pulang, 0, 5) . ']',
                    ];
                }
                
                // Show row if it has any requirement or filled slot (or if we want to show all, remove $hasData check. Let's just include all shifts to match user image where some rows are all 0).
                // But to be clean, let's include all shifts. 
                $summaryGrid[$sec->nama_section][$shf->kode_shift] = $row;
            }
        }

        return [
            'hariList'        => $hariList,
            'agents'          => $agents,
            'grid'            => $grid,
            'perHari'         => $perHari,
            'unfilledCount'   => $unfilledCount,
            'totalKebutuhan'  => $totalKebutuhan,
            'unfulfilledDays' => $unfulfilledDays,
            'summaryGrid'     => $summaryGrid,
            'shifts'          => $shifts,
            'sections'        => $sections,
        ];
    }

    /**
     * Halaman History: daftar semua periode jadwal yang pernah dibuat,
     * terbaru lebih dulu. Tampilan siap untuk data banyak:
     * - 4 periode terbaru sebagai card dalam satu baris.
     * - Sisanya sebagai list ringkas, 10 item per halaman.
     * - Filter: cari nama periode, status, dan tahun.
     *
     * Bagian card+list dimuat ulang via AJAX (hanya data, bukan seluruh
     * halaman) -- lihat resources/views/schedules/history.blade.php.
     */
    public function history(Request $request)
    {
        $query = SchedulePeriod::query();

        if ($request->filled('search')) {
            $query->where('nama_periode', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'final'          => $query->where('status', 'final'),
                'butuh_review'   => $query->where('status', 'draft')->whereHas('schedules'),
                'belum_generate' => $query->where('status', 'draft')->whereDoesntHave('schedules'),
                default          => null,
            };
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_mulai', $request->tahun);
        }

        // 4 periode terbaru tampil sebagai card; sisanya (mulai index ke-4)
        // tampil sebagai list yang di-paginate per 10. Keduanya dihitung dari
        // query yang sama, jadi total & halaman didapat lewat count SQL + limit
        // -- TIDAK lagi memuat semua periode ke memori.
        $totalPeriode = (clone $query)->count();
        $totalAgentAktif = Agent::aktif()->count();

        // Agregat per periode (slot kerja, ada-hasil, jumlah hari kebutuhan)
        // dihitung lewat subquery SQL, bukan 2-3 query terpisah per periode.
        $withAggregates = function ($q) {
            return $q
                ->withCount(['schedules as total_slot_kerja' => fn ($sub) => $sub->where('status', 'kerja')])
                ->withExists(['schedules as has_result']);
        };

        $cardPeriods = $withAggregates(clone $query)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $perPage   = 10;
        $totalList = max(0, $totalPeriode - 4);
        $lastPage  = max(1, (int) ceil($totalList / $perPage));
        $page      = min(max(1, $request->integer('page', 1)), $lastPage);

        $listPeriods = $withAggregates(clone $query)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->offset(4 + ($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Kelengkapan kebutuhan operasional (7 hari = Step 2 selesai) dihitung
        // SEKALI untuk semua periode yang tampil di halaman ini (card + list),
        // bukan query per periode. Data yang dimuat hanya pasangan (periode,
        // hari) yang unik.
        $periodIds = $cardPeriods->pluck('id')->merge($listPeriods->pluck('id'))->unique();
        $hariTerisiByPeriod = OperationalRequirement::whereIn('schedule_period_id', $periodIds)
            ->distinct()
            ->get(['schedule_period_id', 'hari_ke'])
            ->groupBy('schedule_period_id')
            ->map->count();

        $mapPeriod = function ($period) use ($totalAgentAktif, $hariTerisiByPeriod) {
            return [
                'period'              => $period,
                'hasResult'           => (bool) $period->has_result,
                'totalSlotKerja'      => (int) $period->total_slot_kerja,
                'totalAgentAktif'     => $totalAgentAktif,
                'requirementsLengkap' => ($hariTerisiByPeriod[$period->id] ?? 0) === 7,
            ];
        };

        $cardPeriodsData = $cardPeriods->map($mapPeriod)->values();

        $listItems = $listPeriods->map($mapPeriod)->values();

        $listPeriodsData = new LengthAwarePaginator(
            $listItems,
            $totalList,
            $perPage,
            $page,
            [
                'path'  => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        $years = SchedulePeriod::query()
            ->selectRaw('DISTINCT YEAR(tanggal_mulai) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('schedules.history', [
            'cardPeriodsData' => $cardPeriodsData,
            'listPeriodsData' => $listPeriodsData,
            'totalPeriode'    => $totalPeriode,
            'years'           => $years,
            // Rekap bulanan selalu dari SELURUH periode (tidak ikut filter),
            // dirender di bawah section card+list dan tidak dimuat ulang via AJAX.
            'rekapBulanan'    => $this->buildRekapBulanan(
                SchedulePeriod::orderByDesc('tanggal_mulai')->get()
            ),
        ]);
    }

    /**
     * Kelompokkan periode yang SUDAH punya hasil generate berdasarkan
     * bulan-tahun dari tanggal_mulai, lalu hitung total hari kerja per
     * agent untuk setiap periode dalam grup itu (rekap, tidak memengaruhi
     * fitness GA sama sekali -- murni untuk dilihat SPV).
     *
     * @return array<int, array{
     *   label: string,
     *   periods: array<int, array{id:int, label:string}>,
     *   rows: array<int, array{agent: Agent, perPeriode: array<int, int>, total: int}>,
     * }>
     */
    protected function buildRekapBulanan($periods): array
    {
        $periodIds = $periods->pluck('id')->all();

        if (empty($periodIds)) {
            return [];
        }

        // Satu query untuk menemukan periode yang punya hasil generate
        // (menggantikan pola exists() per periode yang tadinya N+1).
        $periodIdsWithResult = Schedule::whereIn('schedule_period_id', $periodIds)
            ->distinct()
            ->pluck('schedule_period_id');

        if ($periodIdsWithResult->isEmpty()) {
            return [];
        }

        // Batasi jangkauan ke 12 bulan terakhir agar rekap tidak memuat
        // seluruh jadwal dari seluruh periode (tak berbatas) seiring data
        // menumpuk -- halaman History hanya butuh rekap beberapa bulan ke belakang.
        $batasBulan = Carbon::now()->subMonths(12)->startOfMonth();

        $periodsWithResult = $periods
            ->whereIn('id', $periodIdsWithResult)
            ->filter(fn ($p) => Carbon::parse($p->tanggal_selesai)->gte($batasBulan))
            ->sortBy('tanggal_mulai')
            ->values();

        if ($periodsWithResult->isEmpty()) {
            return [];
        }

        // Ambil semua jadwal kerja dari periode-periode tersebut dalam SATU query
        $schedules = Schedule::whereIn('schedule_period_id', $periodsWithResult->pluck('id'))
            ->where('status', 'kerja')
            ->where('tanggal', '>=', $batasBulan->toDateString())
            ->get(['agent_id', 'tanggal', 'schedule_period_id']);

        // Kelompokkan BUKAN berdasarkan tanggal_mulai periode, melainkan bulan asli dari setiap tanggal jadwal
        $groupedByMonth = $schedules->groupBy(function ($s) {
            return Carbon::parse($s->tanggal)->format('Y-m');
        })->sortKeysDesc();

        $allAgents = Agent::withTrashed()->orderBy('level')->orderBy('nama_agent')->get();
        $rekap = [];

        foreach ($groupedByMonth as $monthKey => $schedulesInMonth) {
            // Cari tahu periode apa saja yang menyumbang hari kerja di bulan ini
            $involvedPeriodIds = $schedulesInMonth->pluck('schedule_period_id')->unique()->all();
            $periodsInMonth = $periodsWithResult->whereIn('id', $involvedPeriodIds)->sortBy('tanggal_mulai')->values();

            $periodColumns = $periodsInMonth->map(fn ($p, $idx) => [
                'id'    => $p->id,
                'label' => 'Minggu ' . Carbon::parse($p->tanggal_mulai)->translatedFormat('d M') . ' - ' . Carbon::parse($p->tanggal_selesai)->translatedFormat('d M'),
                'sublabel' => '',
            ])->all();

            $countsByAgentAndPeriod = $schedulesInMonth
                ->groupBy('agent_id')
                ->map(function ($agentSchedules) {
                    return $agentSchedules->groupBy('schedule_period_id')->map->count();
                });

            $rows = $allAgents->map(function ($agent) use ($countsByAgentAndPeriod, $periodsInMonth) {
                $countsForAgent = $countsByAgentAndPeriod->get($agent->id, collect());
                $perPeriode = [];
                $total = 0;
                
                foreach ($periodsInMonth as $p) {
                    $count = $countsForAgent->get($p->id, 0);
                    $perPeriode[$p->id] = $count;
                    $total += $count;
                }
                
                return [
                    'agent'      => $agent,
                    'perPeriode' => $perPeriode,
                    'total'      => $total,
                ];
            })->filter(function ($row) {
                $agent = $row['agent'];
                $isNotDeleted = $agent->deleted_at === null;
                // Tampilkan semua agen yang belum dihapus (termasuk skorsing),
                // ATAU agen yang sudah dihapus tetapi punya riwayat kerja di bulan ini
                return $isNotDeleted || $row['total'] > 0;
            })->values()->all();

            $rekap[] = [
                'label'   => Carbon::createFromFormat('Y-m', $monthKey)->translatedFormat('F Y'),
                'periods' => $periodColumns,
                'rows'    => $rows,
            ];
        }

        return $rekap;
    }

    /**
     * Halaman detail History: tampilan ringkas (grid + detail per hari)
     * untuk satu periode, difokuskan untuk dilihat/dicetak -- tanpa
     * kontrol generate/finalize seperti di halaman Step 3.
     */
    public function historyShow(SchedulePeriod $period)
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        $viewData = [
            'period'    => $period,
            'hasResult' => $hasResult,
        ];

        if ($hasResult) {
            $viewData = array_merge($viewData, $this->buildReviewData($period));
        }

        return view('schedules.history-show', $viewData);
    }

    /**
     * Update nama_periode (selalu boleh) dan tanggal_mulai/tanggal_selesai
     * (hanya boleh jika periode ini BELUM punya hasil generate, supaya
     * grid mingguan & data schedules yang sudah ada tidak jadi tidak sinkron).
     */
    public function update(Request $request, SchedulePeriod $period)
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        $rules = [
            'nama_periode' => ['required', 'string', 'max:255'],
        ];

        if (!$hasResult) {
            $rules['tanggal_mulai'] = ['required', 'date'];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'nama_periode' => $validated['nama_periode'],
        ];

        if (!$hasResult && isset($validated['tanggal_mulai'])) {
            $tanggalMulai = Carbon::parse($validated['tanggal_mulai']);
            $tanggalSelesai = $tanggalMulai->copy()->addDays(6);

            // Guard sama seperti Step 1: pindah tanggal tidak boleh
            // beririsan dengan periode yang sudah final.
            $overlap = $this->finalPeriodOverlapping($tanggalMulai, $tanggalSelesai, $period->id);
            if ($overlap) {
                return back()
                    ->withErrors([
                        'tanggal_mulai' => 'Rentang tanggal ini beririsan dengan periode final "'
                            . $overlap->nama_periode . '" ('
                            . Carbon::parse($overlap->tanggal_mulai)->translatedFormat('d M Y')
                            . ' &ndash; '
                            . Carbon::parse($overlap->tanggal_selesai)->translatedFormat('d M Y')
                            . '). Pilih rentang lain yang tidak bertabrakan.',
                    ])
                    ->withInput();
            }

            $updateData['tanggal_mulai'] = $tanggalMulai->format('Y-m-d');
            $updateData['tanggal_selesai'] = $tanggalSelesai->format('Y-m-d');
        }

        $period->update($updateData);

        return redirect()
            ->route('schedules.history')
            ->with('success', 'Periode berhasil diperbarui.');
    }

    /**
     * Hapus periode beserta seluruh data terkait (kebutuhan operasional
     * dan hasil jadwal). Foreign key cascade di migration sudah menangani
     * sebagian, tapi kita hapus eksplisit di sini agar tidak bergantung
     * pada urutan constraint database.
     */
    public function destroy(SchedulePeriod $period)
    {
        OperationalRequirement::where('schedule_period_id', $period->id)->delete();
        Schedule::where('schedule_period_id', $period->id)->delete();
        $period->delete();

        return redirect()
            ->route('schedules.history')
            ->with('success', 'Periode berhasil dihapus.');
    }

    /**
     * Export jadwal mingguan periode ini sebagai PDF, mengikuti format
     * kertas yang sudah biasa dipakai PT XYZ, dengan mengisi template
     * Excel asli (header kuning, urutan baris sesuai seeder, format
     * kode_shift/SECTION, X untuk OFF, SKORSING untuk agent non-aktif).
     *
     * Dipanggil baik dari halaman Step 3 (run) maupun History (historyShow),
     * makanya tidak bergantung pada status period -- selama sudah ada
     * hasil generate, file Excel bisa dibuat.
     */
    public function exportExcel(SchedulePeriod $period)
    {
        $hasResult = Schedule::where('schedule_period_id', $period->id)->exists();

        if (!$hasResult) {
            return redirect()
                ->back()
                ->with('error', 'Belum ada hasil jadwal untuk periode ini. Generate jadwal terlebih dahulu.');
        }

        $exporter = new \App\Services\ScheduleExcelExporter();
        $spreadsheet = $exporter->export($period);

        $filename = 'Jadwal-' . str_replace(' ', '-', $period->nama_periode) . '.xlsx';

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
