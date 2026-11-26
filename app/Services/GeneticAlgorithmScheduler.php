<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\OffRequest;
use App\Models\OperationalRequirement;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use Carbon\Carbon;

/**
 * GeneticAlgorithmScheduler
 *
 * Port dari prototipe GA standalone (ga-prototype/*.php) ke Laravel.
 * Encoding tetap SLOT-CENTRIC: satu gen = satu slot kebutuhan operasional
 * (hari_index, shift_id, section_id, slot_ke) -> agent_id (atau null).
 *
 * Perbedaan dari prototipe:
 * - Bekerja dengan ID asli (shift_id, section_id, agent_id) dari Eloquent,
 *   bukan kode/nama string.
 * - off_requests disimpan per tanggal di DB -> dikonversi ke hari_index
 *   (0=Senin .. 6=Minggu) relatif terhadap tanggal_mulai periode.
 * - agents.level adalah ENUM string ('1','2','3') -> selalu di-cast (int).
 *
 * HC1 (slot kebutuhan terisi) terjamin oleh encoding + diperkuat penalti
 * dominan (HC1_WEIGHT) di fitness; HC3 terjamin oleh encoding (slot dibangun
 * dari kebutuhan operasional yang sudah difilter levelAllowedForSection
 * saat cari agent eligible).
 * HC2, HC4, HC5 ditangani oleh repair operator.
 * HC6 (shift A7/A eksklusif Chat) dicek lewat kode_shift & nama_section.
 */
class GeneticAlgorithmScheduler
{
    // ============================================================
    // KONFIGURASI GA
    // ============================================================
    // Default hasil tuning benchmark 65 run (pop 100 · gen 400 · mut 0.01).
    protected int $populationSize = 100;
    protected int $maxGenerations = 400;
    protected float $mutationRate = 0.01;
    protected float $eliteRate = 0.1;
    protected int $tournamentSize = 3;
    protected int $stagnationLimit = 50;

    /**
     * Terapkan konfigurasi GA dari luar (misalnya dari input PIC di UI).
     * Hanya parameter yang dikirim yang akan di-override — sisanya tetap
     * menggunakan nilai default di atas.
     *
     * @param array{
     *   population_size?: int,
     *   max_generations?: int,
     *   mutation_rate?: float,
     *   elite_rate?: float,
     *   tournament_size?: int,
     *   stagnation_limit?: int,
     *   weight_sc1?: float,
     *   weight_sc2?: float,
     *   weight_sc3?: float,
     *   weight_sc4?: float,
     *   weight_sc5?: float,
     *   weight_sc6?: float,
     *   weight_sc7?: float,
     *   weight_sc8?: float,
     * } $config
     */
    public function applyConfig(array $config): void
    {
        if (isset($config['population_size']))  $this->populationSize  = max(10, (int)   $config['population_size']);
        if (isset($config['max_generations']))  $this->maxGenerations  = max(10, (int)   $config['max_generations']);
        if (isset($config['mutation_rate']))    $this->mutationRate    = max(0.001, min(0.5, (float) $config['mutation_rate']));
        if (isset($config['elite_rate']))       $this->eliteRate       = max(0.05,  min(0.5, (float) $config['elite_rate']));
        if (isset($config['tournament_size']))  $this->tournamentSize  = max(2, (int)   $config['tournament_size']);
        if (isset($config['stagnation_limit'])) $this->stagnationLimit = max(5, (int)   $config['stagnation_limit']);
        if (isset($config['weight_sc1']))       $this->weightSc1       = max(0.0, (float) $config['weight_sc1']);
        if (isset($config['weight_sc2']))       $this->weightSc2       = max(0.0, (float) $config['weight_sc2']);
        if (isset($config['weight_sc3']))       $this->weightSc3       = max(0.0, (float) $config['weight_sc3']);
        if (isset($config['weight_sc4']))       $this->weightSc4       = max(0.0, (float) $config['weight_sc4']);
        if (isset($config['weight_sc5']))       $this->weightSc5       = max(0.0, (float) $config['weight_sc5']);
        if (isset($config['weight_sc6']))       $this->weightSc6       = max(0.0, (float) $config['weight_sc6']);
        if (isset($config['weight_sc7']))       $this->weightSc7       = max(0.0, (float) $config['weight_sc7']);
        if (isset($config['weight_sc8']))       $this->weightSc8       = max(0.0, (float) $config['weight_sc8']);
    }

    /**
     * Konfigurasi GA yang sedang berlaku (default + override). Dipakai
     * benchmark supaya setiap run tercatat dengan parameter LENGKAP
     * (bukan hanya delta), sehingga data hasil tidak ambigu.
     */
    public function getConfig(): array
    {
        return [
            'population_size'  => $this->populationSize,
            'max_generations'  => $this->maxGenerations,
            'mutation_rate'    => $this->mutationRate,
            'elite_rate'       => $this->eliteRate,
            'tournament_size'  => $this->tournamentSize,
            'stagnation_limit' => $this->stagnationLimit,
        ];
    }

    // ============================================================
    // BOBOT PENALTI
    // ============================================================
    protected float $weightSc1 = 100.0;
    protected float $weightSc2 = 50.0;
    protected float $weightSc3 = 30.0;
    protected float $weightSc4 = 10.0;
    protected float $weightSc5 = 15.0;
    protected float $weightSc6 = 50.0;
    protected float $weightSc7 = 12.0;
    protected float $weightSc8 = 5.0;

    /**
     * Bobot HC1 (hard): slot kebutuhan operasional yang tidak terisi.
     * Sengaja jauh lebih besar dari total penalti soft maksimum realistis
     * sehingga kromosom dengan slot kosong praktis ditolak (quasi-hard).
     * Slot kosong hanya tersisa jika periode benar-benar infeasible
     * (tidak ada agent eligible untuk mengisinya).
     */
    protected const HC1_WEIGHT = 1000000.0;

    protected const MAX_HARI_KERJA = 5;

    /**
     * Data yang dimuat sekali dari DB, dipakai berulang sepanjang proses GA.
     */
    protected SchedulePeriod $period;

    /** @var array<int, array{id:int, nama_agent:string, level:int, status:string}> */
    protected array $agents = [];

    /** @var array<int, array{id:int, nama_agent:string, level:int, status:string}> indexed by id */
    protected array $agentsById = [];

    /** @var array<int, array{id:int, kode_shift:string}> indexed by id */
    protected array $shiftsById = [];

    /** @var array<int, array{id:int, nama_section:string}> indexed by id */
    protected array $sectionsById = [];

    /**
     * Daftar slot kebutuhan operasional yang sudah diratakan (1 entri
     * per unit kebutuhan, bukan per baris operational_requirements).
     * @var array<int, array{hari_index:int, shift_id:int, section_id:int, slot_ke:int}>
     */
    protected array $emptySlots = [];

    /**
     * Index off request: "agent_id-hari_index" => 'tinggi'|'rendah'
     * @var array<string, string>
     */
    protected array $offRequestsIndex = [];

    /** @var array<int, int> */
    protected array $historicalWorkCount = [];

    /** @var array<int, int> */
    protected array $historicalInboundCount = [];

    /**
     * Skor beban weekend historis per agent dari periode-periode final
     * sebelumnya dalam bulan yang sama. Skala 0–4:
     *   4 = masuk keduanya dengan shift berat (jam_masuk > 09:00)
     *   3 = satu shift berat, satu shift ringan
     *   2 = masuk keduanya dengan shift ringan
     *   1 = masuk salah satu weekend
     *   0 = libur keduanya
     * @var array<int, int>
     */
    protected array $historicalWeekendBurden = [];

    /**
     * Agent Level 2 & 3 yang ditandai PIC mendapat giliran Inbound
     * di periode ini (dari tabel pivot schedule_period_inbound_agents).
     * Level 1 selalu eligible Inbound tanpa perlu masuk daftar ini.
     * @var array<int>
     */
    protected array $inboundAssignedAgents = [];

    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /**
     * Jalankan GA penuh untuk satu periode, kembalikan hasil terbaik
     * beserta detail fitness-nya.
     *
     * @return array{
     *   chromosome: array<int, array{hari_index:int, shift_id:int, section_id:int, slot_ke:int, agent_id:int|null}>,
     *   detail: array,
     *   generations_run: int,
     *   unfilled_count: int,
     * }
     */
    public function run(SchedulePeriod $period): array
    {
        $this->period = $period;
        $this->loadData();

        $population = $this->buildInitialPopulation();

        $bestFitnessEver = -1.0;
        $bestChromosomeEver = null;
        $bestDetailEver = null;
        $stagnationCounter = 0;
        $generationsRun = 0;

        for ($generation = 1; $generation <= $this->maxGenerations; $generation++) {
            $fitnessScores = [];
            $details = [];

            foreach ($population as $i => $chromosome) {
                $result = $this->evaluateFitness($chromosome);
                $fitnessScores[$i] = $result['fitness'];
                $details[$i] = $result;
            }

            $bestIndex = array_keys($fitnessScores, max($fitnessScores))[0];
            $bestFitnessThisGen = $fitnessScores[$bestIndex];

            if ($bestFitnessThisGen > $bestFitnessEver) {
                $bestFitnessEver = $bestFitnessThisGen;
                $bestChromosomeEver = $population[$bestIndex];
                $bestDetailEver = $details[$bestIndex];
                $stagnationCounter = 0;
            } else {
                $stagnationCounter++;
            }

            $generationsRun = $generation;

            if ($stagnationCounter >= $this->stagnationLimit) {
                break;
            }

            $population = $this->buildNextGeneration($population, $fitnessScores);
        }

        $unfilledCount = 0;
        foreach ($bestChromosomeEver as $gen) {
            if ($gen['agent_id'] === null) {
                $unfilledCount++;
            }
        }

        return [
            'chromosome'           => $bestChromosomeEver,
            'detail'               => $bestDetailEver,
            'generations_run'      => $generationsRun,
            'unfilled_count'       => $unfilledCount,
            'total_slots'          => count($bestChromosomeEver),
            'off_request_violations' => $this->extractOffRequestViolations($bestChromosomeEver),
            'off_request_accepted'   => $this->extractOffRequestAccepted($bestChromosomeEver),
        ];
    }

    /**
     * Ekstrak daftar off request yang DIPATUHI oleh GA (agent tidak
     * dijadwalkan kerja di hari tersebut). Kebalikan dari pelanggaran --
     * dipakai untuk menampilkan keputusan GA: diterima vs ditolak.
     *
     * @return array<int, array{
     *   agent_nama: string, hari_index: int, hari_nama: string, prioritas: string,
     * }>
     */
    protected function extractOffRequestAccepted(array $chromosome): array
    {
        $byAgentDay = $this->groupAssignmentsByAgentDay($chromosome);

        $accepted = [];

        foreach ($this->offRequestsIndex as $key => $prioritas) {
            if (isset($byAgentDay[$key])) {
                continue; // agent tetap dijadwalkan -> pelanggaran, bukan diterima
            }

            [$agentId, $hariIndex] = array_map('intval', explode('-', $key));

            $accepted[] = [
                'agent_nama' => $this->agentsById[$agentId]['nama_agent'] ?? "Agent #{$agentId}",
                'hari_index' => $hariIndex,
                'hari_nama'  => ucfirst(self::NAMA_HARI[$hariIndex] ?? '-'),
                'prioritas'  => $prioritas,
            ];
        }

        // Urutkan: prioritas tinggi dulu, lalu berdasarkan hari
        usort($accepted, function ($a, $b) {
            if ($a['prioritas'] !== $b['prioritas']) {
                return $a['prioritas'] === 'tinggi' ? -1 : 1;
            }
            return $a['hari_index'] <=> $b['hari_index'];
        });

        return $accepted;
    }

    /**
     * Ekstrak daftar detail off request yang dilanggar pada kromosom terbaik --
     * siapa, di hari apa, prioritas apa, dan ditugaskan kerja apa padahal
     * minta off. Dipakai untuk ditampilkan ke SPV di halaman Generate &
     * Review, supaya bisa ditindaklanjuti (follow-up ke agent terkait).
     *
     * @return array<int, array{
     *   agent_nama: string, hari_index: int, hari_nama: string, prioritas: string,
     *   kode_shift: string, nama_section: string,
     * }>
     */
    protected function extractOffRequestViolations(array $chromosome): array
    {
        $byAgentDay = $this->groupAssignmentsByAgentDay($chromosome);

        $violations = [];

        foreach ($this->offRequestsIndex as $key => $prioritas) {
            if (!isset($byAgentDay[$key])) {
                continue; // off request ini dipatuhi, bukan pelanggaran
            }

            [$agentId, $hariIndex] = array_map('intval', explode('-', $key));
            $gen = $byAgentDay[$key];

            $violations[] = [
                'agent_nama'   => $this->agentsById[$agentId]['nama_agent'] ?? "Agent #{$agentId}",
                'hari_index'   => $hariIndex,
                'hari_nama'    => ucfirst(self::NAMA_HARI[$hariIndex] ?? '-'),
                'prioritas'    => $prioritas,
                'kode_shift'   => $this->shiftsById[$gen['shift_id']]['kode_shift'] ?? '-',
                'nama_section' => $this->sectionsById[$gen['section_id']]['nama_section'] ?? '-',
            ];
        }

        // Urutkan: prioritas tinggi dulu, lalu berdasarkan hari
        usort($violations, function ($a, $b) {
            if ($a['prioritas'] !== $b['prioritas']) {
                return $a['prioritas'] === 'tinggi' ? -1 : 1;
            }
            return $a['hari_index'] <=> $b['hari_index'];
        });

        return $violations;
    }

    // ============================================================
    // 1. MUAT DATA DARI DATABASE
    // ============================================================
    protected function loadData(): void
    {
        // Hanya masukkan agen yang statusnya aktif ke dalam pool
        $agentModels = Agent::aktif()->get();

        $this->agents = [];
        $this->agentsById = [];
        foreach ($agentModels as $a) {
            $row = [
                'id'            => $a->id,
                'nama_agent'    => $a->nama_agent,
                'level'         => (int) $a->level,
                'status'        => $a->status,
                'jenis_kelamin' => $a->jenis_kelamin,
                'is_training'   => (string) $a->level === '0',
            ];
            $this->agents[] = $row;
            $this->agentsById[$a->id] = $row;
        }

        $this->shiftsById = [];
        foreach (Shift::all() as $s) {
            $this->shiftsById[$s->id] = [
                'id'         => $s->id,
                'kode_shift' => $s->kode_shift,
                'jam_masuk'  => $s->jam_masuk,  // format "H:i" atau "H:i:s"
                'jam_pulang' => $s->jam_pulang,
            ];
        }

        $this->sectionsById = [];
        foreach (Section::all() as $sec) {
            $this->sectionsById[$sec->id] = [
                'id'           => $sec->id,
                'nama_section' => $sec->nama_section,
            ];
        }

        $this->emptySlots = $this->buildEmptySlots();
        $this->offRequestsIndex = $this->buildOffRequestsIndex();
        $this->loadHistoricalData();

        // Muat daftar agent Level 2 & 3 yang ditandai PIC untuk Inbound periode ini
        $this->inboundAssignedAgents = $this->period->inboundAgents()
            ->pluck('agents.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Menghitung akumulasi hari kerja (total, inbound, dan beban weekend) dari
     * periode-periode sebelumnya yang berstatus 'final' pada bulan yang sama.
     */
    protected function loadHistoricalData(): void
    {
        $this->historicalWorkCount    = [];
        $this->historicalInboundCount = [];
        $this->historicalWeekendBurden = [];

        foreach ($this->agents as $agent) {
            $this->historicalWorkCount[$agent['id']]    = 0;
            $this->historicalInboundCount[$agent['id']] = 0;
            $this->historicalWeekendBurden[$agent['id']] = 0;
        }

        $tanggalMulai   = Carbon::parse($this->period->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($this->period->tanggal_selesai);

        // Tentukan bulan yang relevan: bulan dengan lebih banyak hari dalam periode ini.
        // Menangani periode lintas bulan (mis. "31 Agt – 6 Sep") dengan benar.
        if ($tanggalMulai->format('Y-m') === $tanggalSelesai->format('Y-m')) {
            $bulanTahun = $tanggalMulai->format('Y-m');
        } else {
            $hariDiBulanMulai   = $tanggalMulai->daysInMonth - $tanggalMulai->day + 1;
            $hariDiBulanSelesai = $tanggalSelesai->day;
            $bulanTahun = $hariDiBulanMulai >= $hariDiBulanSelesai
                ? $tanggalMulai->format('Y-m')
                : $tanggalSelesai->format('Y-m');
        }

        [$tahun, $bulan] = explode('-', $bulanTahun);
        $bulanStart = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $bulanEnd   = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();

        // Ambil periode final yang punya hari dalam bulan relevan,
        // berbasis rentang tanggal aktual (bukan hanya tanggal_mulai).
        $pastPeriods = SchedulePeriod::where('status', 'final')
            ->where('id', '!=', $this->period->id)
            ->get()
            ->filter(function ($p) use ($bulanStart, $bulanEnd) {
                $mulai   = Carbon::parse($p->tanggal_mulai)->toDateString();
                $selesai = Carbon::parse($p->tanggal_selesai)->toDateString();
                return $mulai <= $bulanEnd && $selesai >= $bulanStart;
            });

        if ($pastPeriods->isEmpty()) {
            return;
        }

        $pastPeriodIds = $pastPeriods->pluck('id')->all();

        // Hanya hitung jadwal yang tanggalnya jatuh di bulan yang relevan.
        $pastSchedules = \App\Models\Schedule::with(['section', 'shift'])
            ->whereIn('schedule_period_id', $pastPeriodIds)
            ->where('status', 'kerja')
            ->whereBetween('tanggal', [$bulanStart, $bulanEnd])
            ->get();

        // Kumpulkan data weekend per agent per periode untuk kalkulasi skor burden
        // Struktur: [agent_id][period_id] = ['sabtu' => shift_jam_masuk|null, 'minggu' => shift_jam_masuk|null]
        $weekendDataByAgentPeriod = [];

        foreach ($pastSchedules as $schedule) {
            $agentId = $schedule->agent_id;
            if (!isset($this->historicalWorkCount[$agentId])) {
                continue;
            }

            $this->historicalWorkCount[$agentId]++;

            $namaSection = strtolower($schedule->section->nama_section ?? '');
            if ($namaSection === 'inbound') {
                $this->historicalInboundCount[$agentId]++;
            }

            // Kumpulkan jadwal weekend untuk kalkulasi burden
            $tanggal = Carbon::parse($schedule->tanggal);
            if ($tanggal->isSaturday() || $tanggal->isSunday()) {
                $periodId  = $schedule->schedule_period_id;
                $hariKey   = $tanggal->isSaturday() ? 'sabtu' : 'minggu';
                $jamMasuk  = $schedule->shift->jam_masuk ?? '00:00:00';

                if (!isset($weekendDataByAgentPeriod[$agentId][$periodId])) {
                    $weekendDataByAgentPeriod[$agentId][$periodId] = ['sabtu' => null, 'minggu' => null];
                }
                $weekendDataByAgentPeriod[$agentId][$periodId][$hariKey] = $jamMasuk;
            }
        }

        // Akumulasikan skor burden dari setiap periode historis
        foreach ($weekendDataByAgentPeriod as $agentId => $periods) {
            $totalBurden = 0;
            foreach ($periods as $periodData) {
                $totalBurden += $this->calcWeekendBurdenScore(
                    $periodData['sabtu'],
                    $periodData['minggu']
                );
            }
            $this->historicalWeekendBurden[$agentId] = $totalBurden;
        }
    }

    /**
     * Hitung skor beban weekend untuk satu periode berdasarkan jam masuk shift
     * pada hari Sabtu dan Minggu. Shift berat = jam masuk > 09:00.
     *
     * Skala skor:
     *   4 = masuk keduanya dengan shift berat
     *   3 = satu shift berat + satu shift ringan/pagi
     *   2 = masuk keduanya dengan shift ringan/pagi
     *   1 = masuk salah satu weekend (shift apapun)
     *   0 = libur keduanya
     *
     * @param string|null $jamMasukSabtu  format "H:i" atau "H:i:s", null = libur
     * @param string|null $jamMasukMinggu format "H:i" atau "H:i:s", null = libur
     */
    protected function calcWeekendBurdenScore(?string $jamMasukSabtu, ?string $jamMasukMinggu): int
    {
        $beratSabtu  = $this->isShiftBerat($jamMasukSabtu);
        $beratMinggu = $this->isShiftBerat($jamMasukMinggu);

        $masukSabtu  = $jamMasukSabtu !== null;
        $masukMinggu = $jamMasukMinggu !== null;

        if ($masukSabtu && $masukMinggu) {
            // Masuk keduanya — beda skor berdasarkan beratnya shift
            $jumlahBerat = (int) $beratSabtu + (int) $beratMinggu;
            return match ($jumlahBerat) {
                2 => 4, // keduanya shift berat
                1 => 3, // satu berat, satu ringan
                0 => 2, // keduanya shift ringan/pagi
            };
        }

        if ($masukSabtu || $masukMinggu) {
            return 1; // masuk salah satu
        }

        return 0; // libur keduanya
    }

    /**
     * Cek apakah sebuah shift termasuk "berat" berdasarkan jam masuknya.
     * Shift berat = jam masuk setelah 09:00 (tidak termasuk pukul 09:00 tepat).
     * Null (libur) dianggap tidak berat.
     */
    protected function isShiftBerat(?string $jamMasuk): bool
    {
        if ($jamMasuk === null) {
            return false;
        }
        // Normalise ke "H:i" agar perbandingan string aman
        $jam = Carbon::createFromTimeString($jamMasuk);
        return $jam->hour > 9 || ($jam->hour === 9 && $jam->minute > 0);
    }

    /**
     * Cek apakah sebuah shift_id termasuk "shift pagi" berdasarkan jam masuk.
     * Shift pagi  = jam masuk < 12:00.
     * Shift siang = jam masuk >= 12:00.
     */
    protected function isShiftPagi(int $shiftId): bool
    {
        $jamMasuk = $this->shiftsById[$shiftId]['jam_masuk'] ?? '00:00:00';
        $jam = Carbon::createFromTimeString($jamMasuk);
        return $jam->hour < 12;
    }

    /**
     * Ratakan operational_requirements (hari_ke ENUM string + jumlah_kebutuhan)
     * jadi daftar slot individual dengan hari_index numerik (0=Senin..6=Minggu).
     */
    protected function buildEmptySlots(): array
    {
        $requirements = OperationalRequirement::where('schedule_period_id', $this->period->id)->get();

        $slots = [];
        foreach ($requirements as $req) {
            $hariIndex = array_search($req->hari_ke, self::NAMA_HARI, true);
            if ($hariIndex === false) {
                continue; // data tidak valid, lewati
            }

            for ($k = 0; $k < $req->jumlah_kebutuhan; $k++) {
                $slots[] = [
                    'hari_index' => $hariIndex,
                    'shift_id'   => $req->shift_id,
                    'section_id' => $req->section_id,
                    'slot_ke'    => $k,
                    'agent_id'   => null,
                ];
            }
        }

        return $slots;
    }

    /**
     * Konversi off_requests (per tanggal) jadi index "agent_id-hari_index" => prioritas,
     * hanya untuk request yang tanggalnya berada di dalam periode ini.
     */
    protected function buildOffRequestsIndex(): array
    {
        $tanggalMulai = Carbon::parse($this->period->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($this->period->tanggal_selesai)->startOfDay();

        $offRequests = OffRequest::whereBetween('tanggal', [
            $tanggalMulai->format('Y-m-d'),
            $tanggalSelesai->format('Y-m-d'),
        ])->diterima()->get();

        $index = [];
        foreach ($offRequests as $req) {
            $tanggal = Carbon::parse($req->tanggal)->startOfDay();
            $hariIndex = $tanggalMulai->diffInDays($tanggal);

            if ($hariIndex < 0 || $hariIndex > 6) {
                continue;
            }

            $key = $req->agent_id . '-' . $hariIndex;
            if (!isset($index[$key]) || $req->prioritas === 'tinggi') {
                $index[$key] = $req->prioritas;
            }
        }

        return $index;
    }

    // ============================================================
    // 2. ATURAN DOMAIN (HC3, HC6, HC7, HC8)
    // ============================================================

    /**
     * Batas HC7: shift dengan jam pulang SETELAH pukul 22:00 hanya boleh
     * diisi agent laki-laki. Shift yang pulang tepat 22:00 tidak termasuk.
     */
    protected const BATAS_JAM_PULANG_MALAM = '22:00';

    protected function levelAllowedForSection(int $level, int $sectionId): bool
    {
        $namaSection = strtolower($this->sectionsById[$sectionId]['nama_section'] ?? '');

        // HC8 (dua arah): agent Training hanya boleh di section Training,
        // dan section Training hanya boleh diisi agent level Training.
        if ($level === 0) {
            return $namaSection === 'training';
        }
        if ($namaSection === 'training') {
            return false;
        }

        return match ($namaSection) {
            'inbound'   => in_array($level, [1, 2, 3], true),
            'informasi' => in_array($level, [2, 3], true),
            'website'   => $level === 3,
            'chat'      => $level === 3,
            default     => false,
        };
    }

    protected function shiftAllowedForSection(int $shiftId, int $sectionId): bool
    {
        $kodeShift = $this->shiftsById[$shiftId]['kode_shift'] ?? '';
        $namaSection = strtolower($this->sectionsById[$sectionId]['nama_section'] ?? '');

        if (in_array($kodeShift, ['A7', 'A'], true)) {
            return $namaSection === 'chat';
        }

        return true;
    }

    /**
     * HC7: apakah agent ini boleh mengisi slot dengan shift tertentu?
     *
     * Shift malam hanya boleh diisi laki-laki. Shift dikategorikan malam jika:
     * - jam pulang SETELAH pukul 22:00 (shift yang tepat 22:00 tidak termasuk), atau
     * - melewati tengah malam (overnight, jam_pulang <= jam_masuk) -- shift
     *   semacam ini pasti berakhir setelah 22:00 hari masuknya.
     *
     * Agent tanpa data jenis kelamin diperlakukan bukan-laki-laki.
     */
    protected function genderAllowedForShift(array $agent, int $shiftId): bool
    {
        $jamMasuk  = self::normTime($this->shiftsById[$shiftId]['jam_masuk'] ?? '');
        $jamPulang = self::normTime($this->shiftsById[$shiftId]['jam_pulang'] ?? '');

        if ($jamPulang === '' || $jamMasuk === '') {
            return true;
        }

        $isShiftMalam = strcmp($jamPulang, self::BATAS_JAM_PULANG_MALAM) > 0
            || strcmp($jamPulang, $jamMasuk) <= 0;

        if (!$isShiftMalam) {
            return true;
        }

        return ($agent['jenis_kelamin'] ?? null) === 'L';
    }

    /**
     * Normalisasi "H:i" / "H:i:s" menjadi "H:i" agar pembandingan string
     * leksikal sama dengan pembandingan kronologis.
     */
    protected static function normTime(string $time): string
    {
        if ($time === '') {
            return '';
        }

        [$h, $i] = array_pad(explode(':', $time, 3), 2, '0');

        return sprintf('%02d:%02d', (int) $h, (int) $i);
    }

    // ============================================================
    // 3. ELIGIBILITAS AGENT UNTUK SATU SLOT
    // ============================================================
    /**
     * Kembalikan daftar agent yang secara hard constraint boleh mengisi slot ini.
     *
     * Off request prioritas TINGGI tidak lagi di-exclude sepenuhnya —
     * agent tersebut dikembalikan, tapi caller (pickAgentWeighted) akan
     * memberi bobot sangat kecil agar hanya dipilih kalau benar-benar
     * tidak ada kandidat lain yang layak. Ini mencegah agent dengan banyak
     * off request weekday terjebak mendapat 0–1 hari kerja per periode.
     */
    protected function getEligibleAgentIds(array $slot): array
    {
        $eligible = [];
        $namaSection = strtolower($this->sectionsById[$slot['section_id']]['nama_section'] ?? '');
        $isInboundSlot = ($namaSection === 'inbound');

        foreach ($this->agents as $agent) {
            if ($agent['status'] !== 'aktif') {
                continue; // HC4
            }
            if (!$this->levelAllowedForSection($agent['level'], $slot['section_id'])) {
                continue; // HC3 & HC8
            }
            if (!$this->shiftAllowedForSection($slot['shift_id'], $slot['section_id'])) {
                continue; // HC6
            }
            if (!$this->genderAllowedForShift($agent, $slot['shift_id'])) {
                continue; // HC7
            }

            // TOGGLE INBOUND: Level 2 & 3 yang tidak ditandai PIC
            // diblokir sepenuhnya dari slot Inbound.
            // Level 1 selalu eligible (mereka hanya bisa Inbound).
            if ($isInboundSlot && $agent['level'] >= 2) {
                if (!in_array($agent['id'], $this->inboundAssignedAgents, true)) {
                    continue;
                }
            }

            // Off request tinggi TIDAK lagi dibuang — agent tetap masuk pool,
            // tapi pickAgentWeighted akan memberinya bobot sangat kecil.
            $eligible[] = $agent['id'];
        }

        return $eligible;
    }

    // ============================================================
    // 4. PEMILIHAN AGENT BERBOBOT (heuristic seeding)
    // ============================================================

    /**
     * Pilih agent dengan bobot berdasarkan beban kerja, beban weekend (SC7),
     * dan variasi shift (SC8).
     *
     * @param array<int>        $eligibleAgentIds   Agent yang boleh mengisi slot ini
     * @param int               $sectionId          Section dari slot yang akan diisi
     * @param int               $shiftId            Shift dari slot yang akan diisi
     * @param array<int,int>    $workCountSoFar     Total slot kerja per agent dalam kromosom ini
     * @param array<int,int>    $weekdayWorkSoFar   Slot weekday (Senin-Jumat) per agent dalam kromosom ini
     * @param array<int,int>    $weekendCountSoFar  Slot weekend per agent dalam kromosom ini
     * @param bool              $isWeekendSlot      Apakah slot ini hari Sabtu/Minggu
     * @param array<int,array<int,int>> $shiftCountSoFar Jumlah tiap shift_id per agent dalam kromosom ini
     * @param int               $slotHariIndex      Hari index slot (0–6), untuk penalti bobot off request
     * @param array<int,int>    $inboundCountSoFar  Jumlah slot Inbound per agent dalam kromosom ini (SC6)
     */
    protected function pickAgentWeighted(
        array $eligibleAgentIds,
        int   $sectionId,
        int   $shiftId,
        array $workCountSoFar,
        array $weekdayWorkSoFar  = [],
        array $weekendCountSoFar = [],
        bool  $isWeekendSlot     = false,
        array $shiftCountSoFar   = [],
        int   $slotHariIndex     = -1,
        array $inboundCountSoFar = []
    ): ?int {
        if (empty($eligibleAgentIds)) {
            return null;
        }

        $namaSection = strtolower($this->sectionsById[$sectionId]['nama_section'] ?? '');
        $isInbound   = ($namaSection === 'inbound');

        $weights = [];
        foreach ($eligibleAgentIds as $agentId) {
            $level = $this->agentsById[$agentId]['level'];
            $w = 1.0;

            // ---- Bobot dasar per section/level ----
            if ($isInbound) {
                if ($level === 1) {
                    $w *= 20.0;
                    $w /= (1 + (($this->historicalInboundCount[$agentId] ?? 0) ** 2));
                } else {
                    // Agent Level 2/3 toggle ON (SC6 SOP 1x): dorong kuat sampai
                    // mendapat tepat 1x Inbound di periode ini, lalu kecilkan
                    // bobotnya setelah tercapai agar tidak terpilih lagi.
                    $currentInbound = $inboundCountSoFar[$agentId] ?? 0;
                    if ($currentInbound < 1) {
                        $w *= 20.0;
                        $w /= (1 + (($this->historicalInboundCount[$agentId] ?? 0) ** 2));
                    } else {
                        $w /= 50.0;
                    }
                }
            } else {
                $w *= match (true) {
                    $namaSection === 'informasi' && $level === 2 => 2.0,
                    $namaSection === 'informasi' && $level === 3 => 1.0,
                    default => 1.0,
                };
            }

            // ---- Penalti off request: bobot sangat kecil, bukan exclude ----
            // Off request tinggi → dipilih hanya kalau benar-benar tidak ada kandidat lain.
            if ($slotHariIndex >= 0) {
                $offKey    = $agentId . '-' . $slotHariIndex;
                $offStatus = $this->offRequestsIndex[$offKey] ?? null;
                if ($offStatus === 'tinggi') {
                    $w /= 50.0;
                } elseif ($offStatus === 'rendah') {
                    $w /= 5.0;
                }
            }

            // ---- Penalti pemerataan total hari kerja (kuadrat, lebih lembut dari kubik) ----
            $workedSoFar    = $workCountSoFar[$agentId] ?? 0;
            $historicalWork = $this->historicalWorkCount[$agentId] ?? 0;
            $totalWorked    = $workedSoFar + $historicalWork;
            $w /= (1 + ($totalWorked ** 2));

            // ---- SC7: Penalti bobot untuk slot weekend ----
            if ($isWeekendSlot) {
                $historicalBurden  = $this->historicalWeekendBurden[$agentId] ?? 0;
                $weekendSoFar      = $weekendCountSoFar[$agentId] ?? 0;
                $weekdaySoFar      = $weekdayWorkSoFar[$agentId] ?? 0;

                // Penalti dari beban weekend historis
                $w /= (1 + ($historicalBurden ** 2));

                // Penalti weekend ke-2: dikurangi proporsional dengan keterbatasan weekday.
                // Agent yang weekday-nya semua terkunci off request tidak dihukum —
                // mereka justru harus didorong ke weekend agar hari kerja dimaksimalkan.
                if ($weekendSoFar >= 1) {
                    $hariWeekdayTersedia = 0;
                    for ($h = 1; $h <= 5; $h++) {
                        $offKey = $agentId . '-' . $h;
                        if (($this->offRequestsIndex[$offKey] ?? null) !== 'tinggi') {
                            $hariWeekdayTersedia++;
                        }
                    }
                    // Interpolasi linear: 0 hari weekday tersedia → faktor 1.0 (tidak dihukum)
                    // 5 hari weekday tersedia → faktor 21.0 (penalti penuh)
                    $penaltiFaktor = 1.0 + ($hariWeekdayTersedia / 5.0) * 20.0;
                    $w /= $penaltiFaktor;
                }

                // Agent dengan banyak kerja weekday → bobot weekend lebih kecil
                $w /= (1 + ($weekdaySoFar ** 2));
            }

            // ---- SC8: Penalti bobot untuk pemerataan shift pagi/siang ----
            $slotIsPagi = $this->isShiftPagi($shiftId);
            $pagiSoFar  = $shiftCountSoFar[$agentId]['pagi'] ?? 0;
            $siangSoFar = $shiftCountSoFar[$agentId]['siang'] ?? 0;

            if ($slotIsPagi && $pagiSoFar > $siangSoFar) {
                $w /= (1 + (($pagiSoFar - $siangSoFar) ** 2));
            } elseif (!$slotIsPagi && $siangSoFar > $pagiSoFar) {
                $w /= (1 + (($siangSoFar - $pagiSoFar) ** 2));
            }

            $weights[$agentId] = max($w, 1e-9);
        }

        return $this->weightedRandomPick($weights);
    }

    protected function weightedRandomPick(array $weights): int
    {
        $total = array_sum($weights);
        $rand = mt_rand() / mt_getrandmax() * $total;
        $cumulative = 0.0;
        foreach ($weights as $key => $w) {
            $cumulative += $w;
            if ($rand <= $cumulative) {
                return $key;
            }
        }

        return array_key_last($weights);
    }

    // ============================================================
    // 5. BANGUN SATU KROMOSOM (heuristic seeding)
    // ============================================================
    protected function buildChromosome(): array
    {
        $chromosome = $this->emptySlots; // copy, agent_id masih null

        // Acak urutan pemrosesan lewat index, BUKAN nilai slot. Ini
        // menghindari array_search($slot, $this->emptySlots, true) yang
        // O(S^2) per kromosom (jadi O(S^2 * populasi * generasi) total).
        $order = range(0, count($chromosome) - 1);
        shuffle($order);

        $assignedDayAgent  = [];
        $workCountSoFar    = [];   // total slot kerja per agent
        $weekdayWorkSoFar  = [];   // slot weekday (hari_index 0–4) per agent
        $weekendCountSoFar = [];   // slot weekend (hari_index 5–6) per agent
        $shiftCountSoFar   = [];   // [agent_id][shift_id] => jumlah
        $inboundCountSoFar = [];   // [agent_id] => jumlah slot Inbound (SC6)

        foreach ($order as $idx) {
            $slot         = $chromosome[$idx];
            $isWeekend    = in_array($slot['hari_index'], [5, 6], true);

            $eligible = $this->getEligibleAgentIds($slot);

            $eligible = array_values(array_filter($eligible, function ($agentId) use ($slot, $assignedDayAgent) {
                $key = $agentId . '-' . $slot['hari_index'];
                return !isset($assignedDayAgent[$key]);
            }));

            $chosenAgentId = $this->pickAgentWeighted(
                $eligible,
                $slot['section_id'],
                $slot['shift_id'],
                $workCountSoFar,
                $weekdayWorkSoFar,
                $weekendCountSoFar,
                $isWeekend,
                $shiftCountSoFar,
                $slot['hari_index'],
                $inboundCountSoFar
            );

            $chromosome[$idx]['agent_id'] = $chosenAgentId;

            if ($chosenAgentId !== null) {
                $key = $chosenAgentId . '-' . $slot['hari_index'];
                $assignedDayAgent[$key] = true;

                $workCountSoFar[$chosenAgentId] = ($workCountSoFar[$chosenAgentId] ?? 0) + 1;

                if ($isWeekend) {
                    $weekendCountSoFar[$chosenAgentId] = ($weekendCountSoFar[$chosenAgentId] ?? 0) + 1;
                } else {
                    $weekdayWorkSoFar[$chosenAgentId] = ($weekdayWorkSoFar[$chosenAgentId] ?? 0) + 1;
                }

                // Tracking variasi shift pagi/siang (SC8)
                if (!isset($shiftCountSoFar[$chosenAgentId])) {
                    $shiftCountSoFar[$chosenAgentId] = ['pagi' => 0, 'siang' => 0];
                }
                $tipeShift = $this->isShiftPagi($slot['shift_id']) ? 'pagi' : 'siang';
                $shiftCountSoFar[$chosenAgentId][$tipeShift]++;

                // Tracking Inbound (SC6)
                $namaSectionSlot = strtolower($this->sectionsById[$slot['section_id']]['nama_section'] ?? '');
                if ($namaSectionSlot === 'inbound') {
                    $inboundCountSoFar[$chosenAgentId] = ($inboundCountSoFar[$chosenAgentId] ?? 0) + 1;
                }
            }
        }

        return $chromosome;
    }

    protected function buildInitialPopulation(): array
    {
        $population = [];
        for ($i = 0; $i < $this->populationSize; $i++) {
            $chromosome = $this->buildChromosome();
            $chromosome = $this->fillRemainingGapsEmergency($chromosome);
            $population[] = $chromosome;
        }

        return $population;
    }

    // ============================================================
    // 6. REPAIR OPERATOR (HC2/HC5: anti double-booking per hari)
    // ============================================================
    protected function repairChromosome(array $chromosome): array
    {
        $confirmed = [];
        $workCount = [];

        foreach ($chromosome as $idx => $gen) {
            if ($gen['agent_id'] === null) {
                continue;
            }
            $key = $gen['agent_id'] . '-' . $gen['hari_index'];

            if (!isset($confirmed[$key])) {
                $confirmed[$key] = true;
                $workCount[$gen['agent_id']] = ($workCount[$gen['agent_id']] ?? 0) + 1;
                continue;
            }

            $replacement = $this->findReplacement($gen, $confirmed, $workCount);

            if ($replacement !== null) {
                $chromosome[$idx]['agent_id'] = $replacement;
                $confirmed[$replacement . '-' . $gen['hari_index']] = true;
                $workCount[$replacement] = ($workCount[$replacement] ?? 0) + 1;
            } else {
                $chromosome[$idx]['agent_id'] = null;
            }
        }

        return $chromosome;
    }

    /**
     * Fallback darurat HC1: tutup slot yang masih kosong (bukan kasus
     * double-booking) dengan kandidat terbaik yang tersedia, termasuk
     * (sebagai upaya terakhir) agent dengan off request prioritas tinggi.
     */
    protected function fillRemainingGapsEmergency(array $chromosome): array
    {
        $confirmed = [];
        $workCount = [];
        foreach ($chromosome as $gen) {
            if ($gen['agent_id'] === null) {
                continue;
            }
            $confirmed[$gen['agent_id'] . '-' . $gen['hari_index']] = true;
            $workCount[$gen['agent_id']] = ($workCount[$gen['agent_id']] ?? 0) + 1;
        }

        foreach ($chromosome as $idx => $gen) {
            if ($gen['agent_id'] !== null) {
                continue;
            }

            $replacement = $this->findReplacement($gen, $confirmed, $workCount);

            // Upaya terakhir HC1: bila tidak ada kandidat yang lolos HC7
            // (misalnya tidak ada agent laki-laki yang bebas), longgarkan
            // HC7 agar slot tetap terisi. HC8 tetap absolut.
            if ($replacement === null) {
                $replacement = $this->findReplacement($gen, $confirmed, $workCount, allowGenderException: true);
            }

            if ($replacement !== null) {
                $chromosome[$idx]['agent_id'] = $replacement;
                $confirmed[$replacement . '-' . $gen['hari_index']] = true;
                $workCount[$replacement] = ($workCount[$replacement] ?? 0) + 1;
            }
        }

        return $chromosome;
    }

    /**
     * Cari pengganti untuk satu slot konflik/kosong. 3 tingkat prioritas:
     * tier 1 (tanpa off) -> tier 2 (off rendah) -> tier 3 (off tinggi),
     * dipakai berurutan sehingga yang dikorbankan selalu bobot penalti terkecil.
     *
     * $allowGenderException hanya dipakai fillRemainingGapsEmergency sebagai
     * upaya TERAKHIR: bila tidak ada kandidat laki-laki sama sekali untuk
     * shift malam, HC1 (slot wajib terisi) menang atas HC7 (gender). HC8
     * (Training) TIDAK pernah dilonggarkan.
     */
    protected function findReplacement(array $slot, array $confirmed, array $workCount, bool $allowGenderException = false): ?int
    {
        $tier1NoOff = [];
        $tier2LowPriority = [];
        $tier3HighPriority = [];

        foreach ($this->agentsById as $agentId => $agent) {
            if ($agent['status'] !== 'aktif') {
                continue;
            }
            if (!$this->levelAllowedForSection($agent['level'], $slot['section_id'])) {
                continue; // HC3 & HC8
            }
            if (!$this->shiftAllowedForSection($slot['shift_id'], $slot['section_id'])) {
                continue; // HC6
            }
            if (!$allowGenderException && !$this->genderAllowedForShift($agent, $slot['shift_id'])) {
                continue; // HC7
            }

            // Sama seperti getEligibleAgentIds: Level 2 & 3 yang tidak ditandai
            // PIC (toggle Inbound) tidak boleh mengisi slot Inbound.
            $namaSectionRepair = strtolower($this->sectionsById[$slot['section_id']]['nama_section'] ?? '');
            if ($namaSectionRepair === 'inbound' && $agent['level'] >= 2
                && !in_array($agentId, $this->inboundAssignedAgents, true)) {
                continue;
            }

            $dayKey = $agentId . '-' . $slot['hari_index'];
            if (isset($confirmed[$dayKey])) {
                continue;
            }

            $offStatus = $this->offRequestsIndex[$dayKey] ?? null;

            match ($offStatus) {
                'tinggi' => $tier3HighPriority[] = $agentId,
                'rendah' => $tier2LowPriority[] = $agentId,
                default  => $tier1NoOff[] = $agentId,
            };
        }

        $pool = match (true) {
            !empty($tier1NoOff)        => $tier1NoOff,
            !empty($tier2LowPriority)  => $tier2LowPriority,
            !empty($tier3HighPriority) => $tier3HighPriority,
            default                   => [],
        };

        if (empty($pool)) {
            return null;
        }

        usort($pool, function ($a, $b) use ($workCount) {
            return ($workCount[$a] ?? 0) <=> ($workCount[$b] ?? 0);
        });

        return $pool[0];
    }

    // ============================================================
    // 7. OPERATOR GA: selection, crossover, mutation
    // ============================================================
    protected function tournamentSelection(array $population, array $fitnessScores): array
    {
        $populationSize = count($population);
        $best = null;
        $bestFitness = -1;

        for ($i = 0; $i < $this->tournamentSize; $i++) {
            $randomIndex = mt_rand(0, $populationSize - 1);
            if ($fitnessScores[$randomIndex] > $bestFitness) {
                $bestFitness = $fitnessScores[$randomIndex];
                $best = $population[$randomIndex];
            }
        }

        return $best;
    }

    protected function crossover(array $parentA, array $parentB): array
    {
        $child = [];
        $length = count($parentA);

        // Day-Based Crossover: Tentukan untuk setiap hari (0-6), ambil dari Parent A atau Parent B
        $useParentAForDay = [];
        for ($hari = 0; $hari < 7; $hari++) {
            $useParentAForDay[$hari] = (mt_rand(0, 1) === 0);
        }

        for ($i = 0; $i < $length; $i++) {
            $hariIndex = $parentA[$i]['hari_index'];
            $child[$i] = $useParentAForDay[$hariIndex] ? $parentA[$i] : $parentB[$i];
        }

        return $child;
    }

    protected function mutate(array $chromosome): array
    {
        // Hitung beban kerja saat ini untuk setiap agen dalam kromosom
        $workCount     = [];
        $inboundCount  = [];
        $weekdayWork   = [];
        $weekendCount  = [];
        $shiftCount    = [];

        foreach ($chromosome as $gen) {
            if ($gen['agent_id'] === null) continue;

            $agentId   = $gen['agent_id'];
            $isWeekend = in_array($gen['hari_index'], [5, 6], true);

            $workCount[$agentId] = ($workCount[$agentId] ?? 0) + 1;

            $namaSection = strtolower($this->sectionsById[$gen['section_id']]['nama_section'] ?? '');
            if ($namaSection === 'inbound') {
                $inboundCount[$agentId] = ($inboundCount[$agentId] ?? 0) + 1;
            }

            if ($isWeekend) {
                $weekendCount[$agentId] = ($weekendCount[$agentId] ?? 0) + 1;
            } else {
                $weekdayWork[$agentId] = ($weekdayWork[$agentId] ?? 0) + 1;
            }

            if (!isset($shiftCount[$agentId])) {
                $shiftCount[$agentId] = ['pagi' => 0, 'siang' => 0];
            }
            $tipeShift = $this->isShiftPagi($gen['shift_id']) ? 'pagi' : 'siang';
            $shiftCount[$agentId][$tipeShift]++;
        }

        foreach ($chromosome as $idx => $gen) {
            if (mt_rand() / mt_getrandmax() > $this->mutationRate) {
                continue;
            }

            $eligible  = $this->getEligibleAgentIds($gen);
            $isWeekend = in_array($gen['hari_index'], [5, 6], true);

            if (!empty($eligible)) {
                $namaSection = strtolower($this->sectionsById[$gen['section_id']]['nama_section'] ?? '');
                $isInbound   = ($namaSection === 'inbound');

                // Kurangi counter agent lama
                $oldAgentId = $gen['agent_id'];
                if ($oldAgentId !== null) {
                    $workCount[$oldAgentId]--;
                    if ($isInbound) {
                        $inboundCount[$oldAgentId]--;
                    }
                    if ($isWeekend) {
                        $weekendCount[$oldAgentId] = max(0, ($weekendCount[$oldAgentId] ?? 0) - 1);
                    } else {
                        $weekdayWork[$oldAgentId] = max(0, ($weekdayWork[$oldAgentId] ?? 0) - 1);
                    }
                    if (isset($shiftCount[$oldAgentId])) {
                        $tipeShiftLama = $this->isShiftPagi($gen['shift_id']) ? 'pagi' : 'siang';
                        $shiftCount[$oldAgentId][$tipeShiftLama] = max(0,
                            ($shiftCount[$oldAgentId][$tipeShiftLama] ?? 0) - 1
                        );
                    }
                }

                // Pilih agent baru dengan context lengkap (SC7 + SC8 ikut dipertimbangkan)
                $newAgentId = $this->pickAgentWeighted(
                    $eligible,
                    $gen['section_id'],
                    $gen['shift_id'],
                    $workCount,
                    $weekdayWork,
                    $weekendCount,
                    $isWeekend,
                    $shiftCount,
                    $gen['hari_index'],
                    $inboundCount
                );

                $chromosome[$idx]['agent_id'] = $newAgentId;

                // Tambahkan counter agent baru
                if ($newAgentId !== null) {
                    $workCount[$newAgentId] = ($workCount[$newAgentId] ?? 0) + 1;
                    if ($isInbound) {
                        $inboundCount[$newAgentId] = ($inboundCount[$newAgentId] ?? 0) + 1;
                    }
                    if ($isWeekend) {
                        $weekendCount[$newAgentId] = ($weekendCount[$newAgentId] ?? 0) + 1;
                    } else {
                        $weekdayWork[$newAgentId] = ($weekdayWork[$newAgentId] ?? 0) + 1;
                    }
                    if (!isset($shiftCount[$newAgentId])) {
                        $shiftCount[$newAgentId] = ['pagi' => 0, 'siang' => 0];
                    }
                    $tipeShiftBaru = $this->isShiftPagi($gen['shift_id']) ? 'pagi' : 'siang';
                    $shiftCount[$newAgentId][$tipeShiftBaru]++;
                }
            }
        }

        return $chromosome;
    }

    protected function buildNextGeneration(array $population, array $fitnessScores): array
    {
        $populationSize = count($population);
        $newPopulation = [];

        $eliteCount = max(1, (int) floor($populationSize * $this->eliteRate));
        $sortedIndexes = range(0, $populationSize - 1);
        usort($sortedIndexes, function ($a, $b) use ($fitnessScores) {
            return $fitnessScores[$b] <=> $fitnessScores[$a];
        });
        for ($i = 0; $i < $eliteCount; $i++) {
            $newPopulation[] = $population[$sortedIndexes[$i]];
        }

        while (count($newPopulation) < $populationSize) {
            $parentA = $this->tournamentSelection($population, $fitnessScores);
            $parentB = $this->tournamentSelection($population, $fitnessScores);

            $child = $this->crossover($parentA, $parentB);
            $child = $this->mutate($child);
            $child = $this->repairChromosome($child);
            $child = $this->fillRemainingGapsEmergency($child);

            $newPopulation[] = $child;
        }

        return $newPopulation;
    }

    // ============================================================
    // 8. FITNESS (SC1-SC6) — HC1,HC3 dijamin encoding; HC2,HC4,HC5 oleh repair
    // ============================================================
    protected function groupAssignmentsByAgent(array $chromosome): array
    {
        $byAgent = [];
        foreach ($chromosome as $gen) {
            if ($gen['agent_id'] === null) {
                continue;
            }
            $byAgent[$gen['agent_id']][] = $gen;
        }
        return $byAgent;
    }

    protected function groupAssignmentsByAgentDay(array $chromosome): array
    {
        $byAgentDay = [];
        foreach ($chromosome as $gen) {
            if ($gen['agent_id'] === null) {
                continue;
            }
            $key = $gen['agent_id'] . '-' . $gen['hari_index'];
            $byAgentDay[$key] = $gen;
        }
        return $byAgentDay;
    }

    protected function calculatePenaltySc1Sc2(array $chromosome): array
    {
        $byAgentDay = $this->groupAssignmentsByAgentDay($chromosome);

        $violationsTinggi = 0;
        $violationsRendah = 0;

        foreach ($this->offRequestsIndex as $key => $prioritas) {
            if (isset($byAgentDay[$key])) {
                if ($prioritas === 'tinggi') {
                    $violationsTinggi++;
                } else {
                    $violationsRendah++;
                }
            }
        }

        return [
            'sc1_violations' => $violationsTinggi,
            'sc1_penalty'    => $violationsTinggi * $this->weightSc1,
            'sc2_violations' => $violationsRendah,
            'sc2_penalty'    => $violationsRendah * $this->weightSc2,
        ];
    }

    protected function calculatePenaltySc3(array $chromosome): array
    {
        $byAgent = $this->groupAssignmentsByAgent($chromosome);

        $totalExcess          = 0;
        $agentsOverLimit      = 0;
        $totalDeficit         = 0;
        $agentsUnderScheduled = 0;

        foreach ($this->agents as $agent) {
            if ($agent['status'] !== 'aktif') {
                continue;
            }

            $totalKerja = count($byAgent[$agent['id']] ?? []);

            // Penalti atas: terlalu banyak kerja
            if ($totalKerja > self::MAX_HARI_KERJA) {
                $excess = $totalKerja - self::MAX_HARI_KERJA;
                $totalExcess += $excess;
                $agentsOverLimit++;
            }

            // Penalti bawah (SC3_MIN): hanya berlaku untuk agent yang harinya
            // BENAR-BENAR TERBATAS karena off request tinggi (hariTersedia < MAX_HARI_KERJA).
            // Agent normal (7 hari tersedia) tidak dikenai penalti ini — mereka boleh
            // kerja 3–5 hari tergantung kebutuhan operasional.
            $hariOffTinggi = 0;
            for ($h = 0; $h < 7; $h++) {
                $key = $agent['id'] . '-' . $h;
                if (($this->offRequestsIndex[$key] ?? null) === 'tinggi') {
                    $hariOffTinggi++;
                }
            }
            $hariTersedia = 7 - $hariOffTinggi;

            if ($hariTersedia < self::MAX_HARI_KERJA) {
                $targetMin = $hariTersedia; // harus isi semua hari yang tersedia
                if ($totalKerja < $targetMin) {
                    $deficit = $targetMin - $totalKerja;
                    $totalDeficit += $deficit;
                    $agentsUnderScheduled++;
                }
            }
        }

        return [
            'sc3_agents_over_limit'  => $agentsOverLimit,
            'sc3_total_excess_days'  => $totalExcess,
            'sc3_agents_under_min'   => $agentsUnderScheduled,
            'sc3_total_deficit_days' => $totalDeficit,
            'sc3_penalty'            => ($totalExcess  * $this->weightSc3)
                                      + ($totalDeficit * $this->weightSc3),
        ];
    }

    protected function calculatePenaltySc4(array $chromosome): array
    {
        $byAgent = $this->groupAssignmentsByAgent($chromosome);

        $counts = [];
        foreach ($this->agents as $agent) {
            if ($agent['status'] !== 'aktif') {
                continue;
            }
            $currentWorked    = count($byAgent[$agent['id']] ?? []);
            $historicalWorked = $this->historicalWorkCount[$agent['id']] ?? 0;
            $counts[]         = $currentWorked + $historicalWorked;
        }

        $stdev = $this->calculateStdev($counts);

        return [
            'sc4_stdev'   => round($stdev, 4),
            'sc4_penalty' => $stdev * $this->weightSc4,
        ];
    }

    protected function calculatePenaltySc5(array $chromosome): array
    {
        $byAgent = $this->groupAssignmentsByAgent($chromosome);

        $violations = 0;

        // Minimum rest hours required between end of one shift and start of the next
        $minRestHours = 11;

        foreach ($this->agents as $agent) {
            if ($agent['status'] !== 'aktif') {
                continue;
            }

            $gensForAgent = $byAgent[$agent['id']] ?? [];
            if (count($gensForAgent) < 2) {
                continue;
            }

            // Kelompokkan per hari, ambil satu shift per hari (HC4 sudah menjamin ini)
            $shiftByDay = [];
            foreach ($gensForAgent as $gen) {
                $shiftByDay[$gen['hari_index']] = $gen['shift_id'];
            }

            ksort($shiftByDay);
            $days = array_keys($shiftByDay);

            for ($i = 1; $i < count($days); $i++) {
                $today = $days[$i];
                $yesterday = $days[$i - 1];

                // Hanya cek pelanggaran pada hari yang berurutan
                if ($today - $yesterday !== 1) {
                    continue;
                }

                $shiftYesterday = $this->shiftsById[$shiftByDay[$yesterday]] ?? null;
                $shiftToday = $this->shiftsById[$shiftByDay[$today]] ?? null;

                if (!$shiftYesterday || !$shiftToday) {
                    continue;
                }

                // Hitung jam selesai kemarin dan jam mulai hari ini
                $jamPulangKemarin = Carbon::createFromTimeString($shiftYesterday['jam_pulang']);
                $jamMasukHariIni  = Carbon::createFromTimeString($shiftToday['jam_masuk']);

                // Jika jam mulai hari ini < jam pulang kemarin,
                // artinya shift hari ini melewati tengah malam (next day), tambah 24 jam
                if ($jamMasukHariIni->lte($jamPulangKemarin)) {
                    $jamMasukHariIni->addDay();
                }

                $restHours = $jamPulangKemarin->diffInHours($jamMasukHariIni, true);

                if ($restHours < $minRestHours) {
                    $violations++;
                }
            }
        }

        return [
            'sc5_violations' => $violations,
            'sc5_penalty'    => $violations * $this->weightSc5,
        ];
    }

    protected function calculatePenaltySc7(array $chromosome): array
    {
        // SC7: Pemerataan beban weekend.
        //
        // Dua komponen penalti:
        //
        // (a) Penalti per-gen: agent dengan beban weekend historis tinggi (≥ 2)
        //     yang mendapat slot weekend dengan shift berat (jam masuk > 09:00)
        //     di periode ini → penalti per kejadian.
        //
        // (b) Penalti distribusi: standar deviasi skor beban weekend gabungan
        //     (historis + periode ini) antar semua agent aktif → makin tidak merata
        //     makin besar penalti.

        // Bangun skor weekend periode ini per agent dari kromosom
        $weekendThisPeriod = [];
        foreach ($this->agents as $agent) {
            $weekendThisPeriod[$agent['id']] = ['sabtu' => null, 'minggu' => null];
        }

        foreach ($chromosome as $gen) {
            if ($gen['agent_id'] === null) {
                continue;
            }
            if (!in_array($gen['hari_index'], [5, 6], true)) {
                continue;
            }

            $agentId = $gen['agent_id'];
            $hariKey = $gen['hari_index'] === 5 ? 'sabtu' : 'minggu';
            $shift   = $this->shiftsById[$gen['shift_id']] ?? null;
            $weekendThisPeriod[$agentId][$hariKey] = $shift['jam_masuk'] ?? '00:00:00';
        }

        // (a) Penalti per-gen: agent burden tinggi mendapat shift berat di weekend
        // Threshold dinaikkan ke >= 4 agar tidak terlalu banyak agent yang kena
        // penalti setelah beberapa periode berjalan di bulan yang sama.
        $perGenViolations = 0;
        foreach ($this->agents as $agent) {
            $agentId          = $agent['id'];
            $historicalBurden = $this->historicalWeekendBurden[$agentId] ?? 0;

            // Hanya agent yang sudah sangat berat historisnya (>= 4)
            // yang dikenai penalti shift berat weekend ini
            if ($historicalBurden < 4) {
                continue;
            }

            $data = $weekendThisPeriod[$agentId];
            foreach (['sabtu', 'minggu'] as $hari) {
                if ($data[$hari] !== null && $this->isShiftBerat($data[$hari])) {
                    $perGenViolations++;
                }
            }
        }

        // (b) Penalti distribusi: std deviasi skor gabungan semua agent aktif
        $combinedScores = [];
        foreach ($this->agents as $agent) {
            $agentId          = $agent['id'];
            $historicalBurden = $this->historicalWeekendBurden[$agentId] ?? 0;
            $currentBurden    = $this->calcWeekendBurdenScore(
                $weekendThisPeriod[$agentId]['sabtu'],
                $weekendThisPeriod[$agentId]['minggu']
            );
            $combinedScores[] = $historicalBurden + $currentBurden;
        }

        $stdev = $this->calculateStdev($combinedScores);

        $penaltyA = $perGenViolations * $this->weightSc7;
        $penaltyB = $stdev * $this->weightSc7;

        return [
            'sc7_per_gen_violations' => $perGenViolations,
            'sc7_burden_stdev'       => round($stdev, 4),
            'sc7_penalty'            => $penaltyA + $penaltyB,
        ];
    }

    protected function calculatePenaltySc8(array $chromosome): array
    {
        // SC8: Pemerataan shift pagi vs siang dalam satu periode.
        //
        // Shift pagi  = jam masuk < 12:00
        // Shift siang = jam masuk >= 12:00
        //
        // Untuk setiap agent aktif yang mendapat lebih dari satu slot kerja,
        // hitung selisih antara jumlah shift pagi dan shift siang.
        // Penalti = rata-rata selisih mutlak per agent.
        // Selisih nol = distribusi sempurna; makin besar selisih → makin besar penalti.

        $byAgent = $this->groupAssignmentsByAgent($chromosome);

        $totalSelisih    = 0;
        $agentsWithShifts = 0;

        foreach ($this->agents as $agent) {
            $agentId = $agent['id'];
            $gens    = $byAgent[$agentId] ?? [];

            if (count($gens) < 2) {
                continue; // satu slot tidak ada distribusi yang bisa diukur
            }

            $pagiCount  = 0;
            $siangCount = 0;

            foreach ($gens as $gen) {
                if ($this->isShiftPagi($gen['shift_id'])) {
                    $pagiCount++;
                } else {
                    $siangCount++;
                }
            }

            $totalSelisih += abs($pagiCount - $siangCount);
            $agentsWithShifts++;
        }

        $avgSelisih = $agentsWithShifts > 0 ? $totalSelisih / $agentsWithShifts : 0.0;

        return [
            'sc8_agents_counted'  => $agentsWithShifts,
            'sc8_avg_shift_stdev' => round($avgSelisih, 4), // key dipertahankan agar blade tidak perlu diubah
            'sc8_penalty'         => $avgSelisih * $this->weightSc8,
        ];
    }

    protected function calculatePenaltyHc1(array $chromosome): array
    {
        // HC1 (hard): slot kebutuhan operasional yang TIDAK terisi (agent_id null).
        // Penalti dominan membuatnya setara hard constraint — semua kromosom
        // yang terisi penuh selalu menang atas yang punya slot kosong.
        $unfilledCount = 0;
        foreach ($chromosome as $gen) {
            if ($gen['agent_id'] === null) {
                $unfilledCount++;
            }
        }

        return [
            'hc1_unfilled_count' => $unfilledCount,
            'hc1_penalty'        => $unfilledCount * self::HC1_WEIGHT,
        ];
    }

    protected function calculatePenaltySc6(array $chromosome): array
    {
        // SC6 baru: penalti per agent Level 2+3 yang toggle ON tapi tidak
        // mendapat tepat 1x Inbound di periode ini.
        // Agent Level 1 tidak dihitung (mereka memang dapat banyak Inbound).
        // Agent yang toggle OFF tidak dihitung (by design tidak dijadwalkan Inbound).

        if (empty($this->inboundAssignedAgents)) {
            return [
                'sc6_violations'      => 0,
                'sc6_total_deviation' => 0,
                'sc6_penalty'         => 0.0,
            ];
        }

        $byAgent = $this->groupAssignmentsByAgent($chromosome);

        $violations      = 0;
        $totalDeviation  = 0;

        foreach ($this->inboundAssignedAgents as $agentId) {
            if (!isset($this->agentsById[$agentId])) {
                continue;
            }

            $gensForAgent = $byAgent[$agentId] ?? [];
            $inboundCount = 0;

            foreach ($gensForAgent as $gen) {
                $namaSection = strtolower($this->sectionsById[$gen['section_id']]['nama_section'] ?? '');
                if ($namaSection === 'inbound') {
                    $inboundCount++;
                }
            }

            // Target: tepat 1x Inbound. Penalti untuk setiap deviasi dari 1.
            $deviation = abs($inboundCount - 1);
            if ($deviation > 0) {
                $violations++;
                $totalDeviation += $deviation;
            }
        }

        return [
            'sc6_violations'      => $violations,
            'sc6_total_deviation' => $totalDeviation,
            'sc6_penalty'         => $totalDeviation * $this->weightSc6,
        ];
    }

    protected function calculateStdev(array $values): float
    {
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }
        $mean = array_sum($values) / $n;
        $variance = 0.0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $variance /= $n;
        return sqrt($variance);
    }

    protected function evaluateFitness(array $chromosome): array
    {
        $sc1sc2 = $this->calculatePenaltySc1Sc2($chromosome);
        $sc3    = $this->calculatePenaltySc3($chromosome);
        $sc4    = $this->calculatePenaltySc4($chromosome);
        $sc5    = $this->calculatePenaltySc5($chromosome);
        $sc6    = $this->calculatePenaltySc6($chromosome);
        $sc7    = $this->calculatePenaltySc7($chromosome);
        $sc8    = $this->calculatePenaltySc8($chromosome);
        $hc1    = $this->calculatePenaltyHc1($chromosome);

        $totalPenalty = $hc1['hc1_penalty']
            + $sc1sc2['sc1_penalty'] + $sc1sc2['sc2_penalty']
            + $sc3['sc3_penalty']
            + $sc4['sc4_penalty']
            + $sc5['sc5_penalty']
            + $sc6['sc6_penalty']
            + $sc7['sc7_penalty']
            + $sc8['sc8_penalty'];

        return array_merge(
            $hc1,
            $sc1sc2,
            $sc3,
            $sc4,
            $sc5,
            $sc6,
            $sc7,
            $sc8,
            [
                'total_penalty' => $totalPenalty,
                'fitness'       => 1 / (1 + $totalPenalty),
            ]
        );
    }
}
