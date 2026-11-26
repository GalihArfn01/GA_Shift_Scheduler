<x-app-layout>
    <x-slot name="header">Generate Jadwal</x-slot>

    <div class="py-8 animate-fade-in">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="regenForm">

            <div class="mb-6">
                <h1 class="page-title">Generate &amp; Review Jadwal</h1>
            </div>

            {{-- Indikator step --}}
            <div class="flex items-center gap-3 text-sm mb-6">
                <div class="flex items-center gap-2 font-bold" style="color:#059669;">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold"
                        style="background-color:#ECFDF5;color:#047857;">1</span>
                    <span class="hidden sm:inline">Buat Periode</span>
                </div>
                <div class="flex-1 h-1 rounded-full" style="background-color:#ECFDF5;"></div>
                <div class="flex items-center gap-2 font-bold" style="color:#059669;">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold"
                        style="background-color:#ECFDF5;color:#047857;">2</span>
                    <span class="hidden sm:inline">Kebutuhan Operasional</span>
                </div>
                <div class="flex-1 h-1 rounded-full" style="background-color:#E2231A;"></div>
                <div class="flex items-center gap-2 font-extrabold" style="color:#E2231A;">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-extrabold"
                        style="background-color:#E2231A;color:#ffffff;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);">3</span>
                    <span class="hidden sm:inline">Generate &amp; Review</span>
                </div>
            </div>

            {{-- Info periode --}}
            <div class="card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4"
                style="background-color:#FCFAF7;">
                <div>
                    <h3 class="text-lg font-extrabold" style="color:#1F2126;">{{ $period->nama_periode }}</h3>
                    <p class="text-sm font-semibold mt-1" style="color:#82858E;">
                        {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 text-[10px] font-extrabold px-3 py-1.5 rounded-full uppercase tracking-wider"
                    style="{{ $period->status === 'final' ? 'background-color:#ECFDF5;color:#047857;' : 'background-color:#FFFBEB;color:#D97706;' }}">
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $period->status === 'final' ? 'bg-success-500' : 'bg-warning-500' }}"></span>
                    {{ $period->status === 'final' ? 'FINAL' : 'DRAFT' }}
                </span>
            </div>

            {{-- Notifikasi --}}
            @if (session('success'))
            <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                style="background-color:#ECFDF5;color:#047857;border:1px solid #D1FAE5;">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
            @endif

            @if (session('error'))
            <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FEE2E2;">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('error') }}
            </div>
            @endif

            @if (session('info'))
            <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                style="background-color:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('info') }}
            </div>
            @endif
            {{-- ==================== KONDISI TAMPILAN ==================== --}}
            @if ($isProcessing)
            {{-- ===== SEDANG PROCESSING ===== --}}
            <div id="ga-processing-card" class="card p-10 text-center" style="border:2px solid #BFDBFE;">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-5"
                    style="background-color:#EFF6FF;">
                    <svg class="w-8 h-8 animate-spin" style="color:#2563EB;" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-extrabold mb-2" style="color:#1F2126;">Algoritma Sedang Berjalan...</h3>
                <p class="text-sm font-semibold mb-2 max-w-md mx-auto" style="color:#62656F;">
                    Proses GA berjalan di background. Halaman akan otomatis memuat hasil setelah selesai.
                    Jangan tutup tab ini.
                </p>
                <p id="ga-status-text" class="text-xs font-semibold" style="color:#82858E;">Memeriksa status...</p>
                <p id="ga-elapsed-text" class="text-sm font-extrabold mt-3" style="color:#2563EB;display:none;"></p>
            </div>

            <script>
                (function() {
                    const pollUrl = '{{ route('schedules.ga-status', $period) }}';
                    const reloadUrl = '{{ route('schedules.generate.run', $period) }}';
                    const interval = 3000;
                    let attempts = 0;
                    const maxAttempts = 600; // ~30 menit @ 3 detik

                    function formatElapsed(seconds) {
                        if (seconds >= 3600) {
                            const h = Math.floor(seconds / 3600);
                            const m = Math.floor((seconds % 3600) / 60);
                            const s = Math.round(seconds % 60);
                            return h + ' jam ' + m + ' mnt ' + s + ' dtk';
                        }
                        if (seconds >= 60) {
                            const m = Math.floor(seconds / 60);
                            const s = Math.round(seconds % 60);
                            return m + ' mnt ' + s + ' dtk';
                        }
                        return Number(seconds).toFixed(1) + ' detik';
                    }

                    function updateElapsed(data) {
                        const el = document.getElementById('ga-elapsed-text');
                        if (!el) return;

                        if (data.elapsed_seconds != null && (data.status === 'running' || data.status === 'pending')) {
                            el.style.display = 'block';
                            el.textContent = 'Waktu berjalan: ' + formatElapsed(data.elapsed_seconds);
                        } else if (data.duration_seconds != null && data.status === 'done') {
                            el.style.display = 'block';
                            el.textContent = 'Total waktu: ' + formatElapsed(data.duration_seconds);
                        }
                    }

                    function poll() {
                        attempts++;
                        if (attempts > maxAttempts) {
                            document.getElementById('ga-status-text').textContent =
                                'Proses terlalu lama. Silakan refresh halaman secara manual.';
                            return;
                        }

                        fetch(pollUrl, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.json())
                            .then(data => {
                                updateElapsed(data);

                                if (data.status === 'done') {
                                    document.getElementById('ga-status-text').textContent =
                                        'Selesai! Memuat hasil...';
                                    window.location.href = reloadUrl;
                                } else if (data.status === 'failed') {
                                    const card = document.getElementById('ga-processing-card');
                                    card.style.borderColor = '#FCA5A5';
                                    card.style.backgroundColor = '#FFF5F5';
                                    document.getElementById('ga-status-text').textContent =
                                        'Generate gagal: ' + (data.error || 'error tidak diketahui') +
                                        '. Silakan coba generate ulang.';
                                    setTimeout(() => window.location.href = reloadUrl, 2000);
                                } else {
                                    const label = data.status === 'running' ?
                                        'Algoritma sedang berjalan' :
                                        'Menunggu proses dimulai';
                                    document.getElementById('ga-status-text').textContent =
                                        label + '... (pengecekan ke-' + attempts + ')';
                                    setTimeout(poll, interval);
                                }
                            })
                            .catch(() => {
                                document.getElementById('ga-status-text').textContent =
                                    'Koneksi terputus, mencoba lagi...';
                                setTimeout(poll, interval * 2);
                            });
                    }

                    setTimeout(poll, 1500);
                })();
            </script>

            @elseif (!$hasResult)
            {{-- ==================== BELUM ADA HASIL ==================== --}}
            <div class="card p-10 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-5"
                    style="background-color:#FFF1F0;">
                    <svg class="w-8 h-8" style="color:#E2231A;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-extrabold mb-2" style="color:#1F2126;">Siap untuk Generate Jadwal</h3>
                <p class="text-sm font-semibold mb-8 max-w-md mx-auto" style="color:#62656F;">
                    Algoritma genetika akan menyusun jadwal shift untuk seluruh agent aktif
                    berdasarkan kebutuhan operasional yang sudah diisi. Proses berjalan di background,
                    kamu tidak perlu menunggu di halaman ini.
                </p>

                <form id="generate-form" action="{{ route('schedules.generate.process', $period) }}" method="POST">
                    @csrf

                    {{-- ==================== PANEL KONFIGURASI GA ==================== --}}
                    <div class="mb-8 text-left" x-data="{ showConfig: false }">
                        <button type="button" @click="showConfig = !showConfig"
                            class="inline-flex items-center gap-2 text-sm font-bold transition-colors mb-4"
                            style="color:#6B7280;" onmouseover="this.style.color='#1F2126'"
                            onmouseout="this.style.color='#6B7280'">
                            <svg class="w-4 h-4 transition-transform" :class="showConfig ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="showConfig" x-transition class="card p-5 border"
                            style="border-color:#E9E9EB;display:none;">
                            <p class="text-xs font-bold uppercase tracking-wider mb-4" style="color:#82858E;">Parameter
                                Algoritma Genetika</p>

                            {{-- Baris 1: Parameter utama GA --}}
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        Ukuran Populasi
                                        <span class="font-normal" style="color:#AEB0B6;">(default: 100)</span>
                                    </label>
                                    <input type="number" name="population_size" placeholder="100" min="10" max="500"
                                        class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        Maks. Generasi
                                        <span class="font-normal" style="color:#AEB0B6;">(default: 400)</span>
                                    </label>
                                    <input type="number" name="max_generations" placeholder="400" min="10" max="2000"
                                        class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        Stagnation Limit
                                        <span class="font-normal" style="color:#AEB0B6;">(default: 50)</span>
                                    </label>
                                    <input type="number" name="stagnation_limit" placeholder="50" min="5" max="500"
                                        class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        Mutation Rate
                                        <span class="font-normal" style="color:#AEB0B6;">(default: 0.01)</span>
                                    </label>
                                    <input type="number" name="mutation_rate" placeholder="0.01" min="0.001" max="0.5"
                                        step="0.001" class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        Elite Rate
                                        <span class="font-normal" style="color:#AEB0B6;">(default: 0.1)</span>
                                    </label>
                                    <input type="number" name="elite_rate" placeholder="0.1" min="0.05" max="0.5"
                                        step="0.01" class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        Tournament Size
                                        <span class="font-normal" style="color:#AEB0B6;">(default: 3)</span>
                                    </label>
                                    <input type="number" name="tournament_size" placeholder="3" min="2" max="10"
                                        class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                            </div>

                            <p class="text-xs font-bold uppercase tracking-wider mb-3 pt-4 border-t"
                                style="color:#82858E;border-color:#E9E9EB;">Bobot Penalti (Soft Constraints)</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @php
                                $scDefaults = [
                                'weight_sc1' => ['label' => 'SC1 &mdash; Off Request Tinggi', 'default' => 100],
                                'weight_sc2' => ['label' => 'SC2 &mdash; Off Request Rendah', 'default' => 20],
                                'weight_sc3' => ['label' => 'SC3 &mdash; Maks. Hari Kerja', 'default' => 30],
                                'weight_sc4' => ['label' => 'SC4 &mdash; Pemerataan Hari', 'default' => 10],
                                'weight_sc5' => ['label' => 'SC5 &mdash; Istirahat Shift', 'default' => 15],
                                'weight_sc6' => ['label' => 'SC6 &mdash; SOP Inbound', 'default' => 50],
                                'weight_sc7' => ['label' => 'SC7 &mdash; Beban Weekend', 'default' => 12],
                                'weight_sc8' => ['label' => 'SC8 &mdash; Shift Pagi/Siang', 'default' => 5],
                                ];
                                @endphp
                                @foreach ($scDefaults as $name => $sc)
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color:#3F4249;">
                                        {{ $sc['label'] }}
                                        <span class="font-normal" style="color:#AEB0B6;">(default:
                                            {{ $sc['default'] }})</span>
                                    </label>
                                    <input type="number" name="{{ $name }}" placeholder="{{ $sc['default'] }}" min="0"
                                        max="1000" step="0.5" class="input-field py-2 text-sm" style="width:100%;">
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-4 pt-3 border-t flex items-center justify-between"
                                style="border-color:#E9E9EB;">
                                <p class="text-xs" style="color:#AEB0B6;">
                                    Input yang dikosongkan akan menggunakan nilai default. Perubahan hanya berlaku untuk
                                    generate ini.
                                </p>
                                <button type="button"
                                    onclick="this.closest('form').querySelectorAll('[name^=population],[name^=max_gen],[name^=stagnation],[name^=mutation],[name^=elite],[name^=tournament],[name^=weight]').forEach(el => el.value = '')"
                                    class="text-xs px-3 py-1.5 rounded-lg font-bold flex-shrink-0 ml-4"
                                    style="background-color:#F6F6F7;color:#6B7280;border:1px solid #E9E9EB;">
                                    Reset ke Default
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- ==================== END PANEL KONFIGURASI ==================== --}}
                    <button type="submit" id="generate-button"
                        class="inline-flex items-center gap-2.5 px-8 py-4 rounded-xl text-base font-extrabold text-white transition-all"
                        style="background-color:#E2231A;box-shadow:0 8px 24px -4px rgba(226,35,26,0.45);"
                        onmouseover="this.style.background='#C01A12';" onmouseout="this.style.background='#E2231A';">
                        <svg id="generate-spinner" class="hidden animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg id="generate-icon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span id="generate-button-text">Generate Jadwal</span>
                    </button>
                </form>

                <script>
                    document.getElementById('generate-form').addEventListener('submit', function() {
                        const btn = document.getElementById('generate-button');
                        btn.disabled = true;
                        btn.style.opacity = '0.7';
                        btn.style.cursor = 'not-allowed';
                        document.getElementById('generate-spinner').classList.remove('hidden');
                        document.getElementById('generate-icon').classList.add('hidden');
                        document.getElementById('generate-button-text').textContent = 'Mengirim permintaan...';
                    });
                </script>
            </div>
            @else
            {{-- ==================== SUDAH ADA HASIL ==================== --}}

            @php
            $summary = session('ga_summary') ?? ($ga_summary_from_db ?? null);
            $gaConfigUsed = $ga_config_used ?? null;
            $gaStartedAt = $ga_started_at ?? null;
            $gaFinishedAt = $ga_finished_at ?? null;
            $gaDurasiDetik = ($gaStartedAt && $gaFinishedAt)
            ? \Carbon\Carbon::parse($gaStartedAt)->diffInSeconds(\Carbon\Carbon::parse($gaFinishedAt))
            : null;
            @endphp
            @if ($summary)
            <div class="card p-6">
                <h3 class="text-lg font-extrabold mb-4" style="color:#1F2126;">Ringkasan Hasil Generate</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="p-6 rounded-xl text-center" style="background-color:#F6F6F7;">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#82858E;">Total Slot
                            Kebutuhan</p>
                        <p class="text-3xl font-extrabold" style="color:#1F2126;">{{ $summary['total_slots'] }}</p>
                        <p class="text-[11px] mt-1" style="color:#AEB0B6;">Jumlah slot kerja yang harus diisi periode
                            ini</p>
                    </div>
                    <div class="p-6 rounded-xl text-center"
                        style="{{ $summary['unfilled_count'] > 0 ? 'background-color:#FEF2F2;' : 'background-color:#ECFDF5;' }}">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                            style="{{ $summary['unfilled_count'] > 0 ? 'color:#B91C1C;' : 'color:#047857;' }}">Slot
                            Tidak Terisi</p>
                        <p class="text-3xl font-extrabold"
                            style="{{ $summary['unfilled_count'] > 0 ? 'color:#DC2626;' : 'color:#059669;' }}">
                            {{ $summary['unfilled_count'] }}
                        </p>
                        <p class="text-[11px] mt-1" style="color:#AEB0B6;">Kebutuhan yang belum mendapat agent</p>
                    </div>
                </div>

                {{-- ==================== DETAIL ANALISIS ALGORITMA (dropdown samar, di dalam card ringkasan) ==================== --}}
                <div x-data="{ expanded: false }" class="mt-6 pt-5 border-t" style="border-color:#E9E9EB;">
                    <button @click="expanded = !expanded" type="button"
                        class="flex items-center justify-between w-full text-left rounded-lg px-2 py-1.5 transition-colors"
                        onmouseover="this.style.background='#F6F6F7';" onmouseout="this.style.background='';">
                        <svg :class="{'rotate-180': expanded}" class="w-3.5 h-3.5 transition-transform shrink-0"
                            style="color:#C9CBD0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="expanded" x-transition x-cloak class="mt-4 space-y-6">
                        {{-- Ringkasan 1 baris: Fitness, Total Penalty, Unfilled, Generations Run, Total Waktu --}}
                        @php
                        $ringkasanTp = $summary['detail']['total_penalty'] ?? 0;
                        $ringkasanFitness = $summary['detail']['fitness'] ?? (1 / (1 + $ringkasanTp));
                        $ringkasanDurasi = $gaDurasiDetik !== null
                        ? ($gaDurasiDetik >= 60
                            ? floor($gaDurasiDetik / 60) . ' mnt ' . round($gaDurasiDetik % 60, 1) . ' dtk'
                            : number_format($gaDurasiDetik, 2) . ' dtk')
                        : '-';
                        @endphp
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-bold p-3 rounded-lg"
                            style="background-color:#F6F6F7;color:#3F4249;">
                            <span style="color:#82858E;">Ringkasan:</span>
                            <span>Fitness: <span
                                    style="color:#047857;">{{ number_format($ringkasanFitness, 4) }}</span></span>
                            <span style="color:#C9CBD0;">·</span>
                            <span>Total Penalty: <span
                                    style="color:{{ $ringkasanTp > 0 ? '#DC2626' : '#059669' }};">{{ number_format($ringkasanTp, 2) }}</span></span>
                            <span style="color:#C9CBD0;">·</span>
                            <span>Unfilled: <span
                                    style="color:{{ $summary['unfilled_count'] > 0 ? '#DC2626' : '#059669' }};">{{ $summary['unfilled_count'] }}</span></span>
                            <span style="color:#C9CBD0;">·</span>
                            <span>Generations Run: <span
                                    style="color:#1F2126;">{{ $summary['generations_run'] }}</span></span>
                            <span style="color:#C9CBD0;">·</span>
                            <span>Total Waktu: <span style="color:#1F2126;">{{ $ringkasanDurasi }}</span></span>
                        </div>

                        {{-- Metrik GA --}}
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#AEB0B6;">Metrik
                                GA</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                <div class="p-4 rounded-xl" style="background-color:#F6F6F7;">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                                        style="color:#82858E;">Generasi Dijalankan</p>
                                    <p class="text-2xl font-extrabold" style="color:#1F2126;">
                                        {{ $summary['generations_run'] }}
                                    </p>
                                </div>
                                @php
                                $totalPenalty = $summary['detail']['total_penalty'] ?? 0;
                                $fitnessValue = 1 / (1 + $totalPenalty);
                                $fitnessPercent = $fitnessValue * 100;
                                $fitnessColor = $fitnessPercent >= 95 ? '#059669'
                                : ($fitnessPercent >= 80 ? '#D97706' : '#DC2626');
                                $fitnessBg = $fitnessPercent >= 95 ? 'background-color:#ECFDF5;'
                                : ($fitnessPercent >= 80 ? 'background-color:#FFFBEB;' : 'background-color:#FEF2F2;');
                                @endphp
                                <div class="p-4 rounded-xl" style="{{ $fitnessBg }}">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                                        style="color:{{ $fitnessColor }};">Nilai Fitness</p>
                                    <p class="text-2xl font-extrabold" style="color:{{ $fitnessColor }};">
                                        {{ number_format($fitnessValue, 4) }}
                                    </p>
                                    <p class="text-[10px] mt-1" style="color:#82858E;">f = 1 / (1 +
                                        {{ number_format($totalPenalty, 2) }})
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Config GA yang dipakai (hanya tampil jika ada override) --}}
                        @if ($gaConfigUsed)
                        <div class="mb-5 p-4 rounded-xl border" style="background-color:#F0F9FF;border-color:#BAE6FD;">
                            <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color:#0369A1;">
                                Konfigurasi GA Kustom yang Dipakai</p>
                            @php
                            $paramLabels = [
                            'population_size' => 'Ukuran Populasi',
                            'max_generations' => 'Maks. Generasi',
                            'stagnation_limit' => 'Stagnation Limit',
                            'mutation_rate' => 'Mutation Rate',
                            'elite_rate' => 'Elite Rate',
                            'tournament_size' => 'Tournament Size',
                            'weight_sc1' => 'Bobot SC1',
                            'weight_sc2' => 'Bobot SC2',
                            'weight_sc3' => 'Bobot SC3',
                            'weight_sc4' => 'Bobot SC4',
                            'weight_sc5' => 'Bobot SC5',
                            'weight_sc6' => 'Bobot SC6',
                            'weight_sc7' => 'Bobot SC7',
                            'weight_sc8' => 'Bobot SC8',
                            ];
                            @endphp
                            <div class="flex flex-wrap gap-2">
                                @foreach ($gaConfigUsed as $key => $value)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                    style="background-color:#DBEAFE;color:#1D4ED8;">
                                    {{ $paramLabels[$key] ?? $key }}: {{ $value }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#AEB0B6;">Waktu
                                Eksekusi GA</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm" id="timing-cards">
                                @if ($gaStartedAt && $gaFinishedAt)
                                @php
                                $startFormatted =
                                \Carbon\Carbon::parse($gaStartedAt)->setTimezone('Asia/Jakarta')->format('d M Y,
                                H:i:s');
                                $finishFormatted =
                                \Carbon\Carbon::parse($gaFinishedAt)->setTimezone('Asia/Jakarta')->format('d M Y,
                                H:i:s');
                                $durasiMs =
                                \Carbon\Carbon::parse($gaStartedAt)->diffInMilliseconds(\Carbon\Carbon::parse($gaFinishedAt));
                                $durasiDetik = $durasiMs / 1000;
                                $durasiLabel = $durasiMs >= 60000
                                ? floor($durasiMs / 60000) . ' mnt ' . round(($durasiMs % 60000) / 1000, 1) . ' dtk'
                                : number_format($durasiDetik, 2) . ' detik';
                                @endphp
                                <div class="p-4 rounded-xl" style="background-color:#F0F9FF;border:1px solid #BAE6FD;">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                                        style="color:#0369A1;">Waktu Mulai</p>
                                    <p class="text-sm font-extrabold" style="color:#0369A1;">{{ $startFormatted }}</p>
                                </div>
                                <div class="p-4 rounded-xl" style="background-color:#F0F9FF;border:1px solid #BAE6FD;">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                                        style="color:#0369A1;">Waktu Selesai</p>
                                    <p class="text-sm font-extrabold" style="color:#0369A1;">{{ $finishFormatted }}</p>
                                </div>
                                <div class="p-4 rounded-xl" style="background-color:#EEF2FF;border:1px solid #C7D2FE;">
                                    <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                                        style="color:#4338CA;">Total Waktu Eksekusi GA</p>
                                    <p class="text-2xl font-extrabold" style="color:#4338CA;">{{ $durasiLabel }}</p>
                                    <p class="text-[10px] mt-1" style="color:#6366F1;">
                                        {{ number_format($durasiDetik, 3) }} detik ({{ number_format($durasiMs) }} ms)
                                    </p>
                                </div>
                                @else
                                <div class="sm:col-span-3 p-4 rounded-xl" style="background-color:#F6F6F7;">
                                    <p class="text-xs" style="color:#AEB0B6;">Data waktu eksekusi tidak tersedia
                                        (generate dilakukan sebelum fitur ini ditambahkan). Generate ulang untuk melihat
                                        waktu eksekusi.</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Daftar off request yang ditolak kini ditampilkan di card "Keputusan Off Request GA" di atas. --}}

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#AEB0B6;">Detail
                                Penalti <span class="font-normal">(untuk analisis)</span></p>
                            <div class="overflow-x-auto rounded-xl border" style="border-color:#E9E9EB;">
                                <table class="w-full text-sm text-left border-collapse">
                                    <thead>
                                        <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                            <th class="px-4 py-3"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Constraint</span></th>
                                            <th class="px-4 py-3"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Deskripsi</span></th>
                                            <th class="px-4 py-3 text-right"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Nilai</span></th>
                                            <th class="px-4 py-3 text-right"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Penalti (P)</span></th>
                                            <th class="px-4 py-3 text-right"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Kontribusi</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color:#E9E9EB;">
                                        @php
                                        $tp = $summary['detail']['total_penalty'] ?? 0;
                                        $rows = [
                                        ['id'=>'SC1','bg'=>'#fff', 'desc'=>'Off Request (Prioritas Tinggi) dilanggar',
                                        'val'=>($summary['detail']['sc1_violations']).' kasus',
                                        'pen'=>$summary['detail']['sc1_penalty']],
                                        ['id'=>'SC2','bg'=>'#FCFAF7', 'desc'=>'Off Request (Prioritas Rendah)
                                        dilanggar', 'val'=>($summary['detail']['sc2_violations']).' kasus',
                                        'pen'=>$summary['detail']['sc2_penalty']],
                                        ['id'=>'SC3','bg'=>'#fff', 'desc'=>'Melebihi Maks. Hari Kerja
                                        ('.$summary['detail']['sc3_agents_over_limit'].' agent)',
                                        'val'=>$summary['detail']['sc3_total_excess_days'].' hari lebih',
                                        'pen'=>$summary['detail']['sc3_penalty']],
                                        ['id'=>'SC4','bg'=>'#FCFAF7', 'desc'=>'Pemerataan Total Jadwal Kerja (StDev)',
                                        'val'=>number_format($summary['detail']['sc4_stdev'],3),
                                        'pen'=>$summary['detail']['sc4_penalty']],
                                        ['id'=>'SC5','bg'=>'#fff', 'desc'=>'Istirahat Antar Shift < 11 Jam', 'val'=>
                                            ($summary['detail']['sc5_violations']).' kasus',
                                            'pen'=>$summary['detail']['sc5_penalty']],
                                            ['id'=>'SC6','bg'=>'#FCFAF7', 'desc'=>'SOP Inbound 1x (agent toggle ON tidak
                                            mendapat tepat 1x)', 'val'=>($summary['detail']['sc6_violations'] ?? 0).'
                                            agent (deviasi '.($summary['detail']['sc6_total_deviation'] ?? 0).')',
                                            'pen'=>$summary['detail']['sc6_penalty'] ?? 0],
                                            ['id'=>'SC7','bg'=>'#fff', 'desc'=>'Pemerataan Beban Weekend
                                            (StDev='.($summary['detail']['sc7_burden_stdev'] ?? '-').')',
                                            'val'=>($summary['detail']['sc7_per_gen_violations'] ?? 0).' kasus',
                                            'pen'=>$summary['detail']['sc7_penalty'] ?? 0],
                                            ['id'=>'SC8','bg'=>'#FCFAF7', 'desc'=>'Pemerataan Shift Pagi/Siang
                                            (selisih='.($summary['detail']['sc8_avg_shift_stdev'] ?? '-').')',
                                            'val'=>($summary['detail']['sc8_agents_counted'] ?? 0).' agent',
                                            'pen'=>$summary['detail']['sc8_penalty'] ?? 0],
                                            ];
                                            @endphp
                                            @foreach ($rows as $row)
                                            @php
                                            $kontribusi = $tp > 0 ? ($row['pen'] / $tp * 100) : 0;
                                            @endphp
                                            <tr style="background-color:{{ $row['bg'] }};">
                                                <td class="px-4 py-3 font-extrabold" style="color:#E2231A;">
                                                    {{ $row['id'] }}
                                                </td>
                                                <td class="px-4 py-3 font-semibold" style="color:#3F4249;">
                                                    {{ $row['desc'] }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold" style="color:#1F2126;">
                                                    {{ $row['val'] }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold"
                                                    style="color:{{ $row['pen'] > 0 ? '#DC2626' : '#059669' }};">
                                                    {{ number_format($row['pen'], 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    @if ($row['pen'] > 0)
                                                    <div class="flex items-center justify-end gap-2">
                                                        <div class="w-16 h-1.5 rounded-full overflow-hidden"
                                                            style="background-color:#F3F4F6;">
                                                            <div class="h-full rounded-full"
                                                                style="width:{{ min(100, $kontribusi) }}%;background-color:#E2231A;">
                                                            </div>
                                                        </div>
                                                        <span class="text-xs font-bold"
                                                            style="color:#E2231A;">{{ number_format($kontribusi, 1) }}%</span>
                                                    </div>
                                                    @else
                                                    <span class="text-xs font-bold" style="color:#059669;">0%</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="border-top:2px solid #E9E9EB;background-color:#F6F6F7;">
                                            <td colspan="3" class="px-4 py-3 text-right font-extrabold"
                                                style="color:#1F2126;">Total Penalti:</td>
                                            <td class="px-4 py-3 text-right font-extrabold text-base"
                                                style="color:#E2231A;">{{ number_format($tp, 2) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr style="border-top:1px solid #E9E9EB;background-color:#ECFDF5;">
                                            <td colspan="3" class="px-4 py-3 text-right font-extrabold"
                                                style="color:#047857;">
                                                Nilai Fitness = 1 / (1 + {{ number_format($tp, 2) }})
                                            </td>
                                            <td class="px-4 py-3 text-right font-extrabold text-lg"
                                                style="color:#047857;">
                                                {{ number_format(1 / (1 + $tp), 4) }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-xs font-bold" style="color:#059669;">
                                                {{ number_format((1 / (1 + $tp)) * 100, 2) }}%
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==================== KEPUTUSAN OFF REQUEST GA ==================== --}}
            @php
            $offAccepted = $summary['off_request_accepted'] ?? [];
            $offRejected = $summary['off_request_violations'] ?? [];
            $offTotal = count($offAccepted) + count($offRejected);
            @endphp
            <div class="card p-6" x-data="{ offRequestOpen: false }">
                <button type="button" @click="offRequestOpen = !offRequestOpen"
                    class="w-full flex items-start gap-3 text-left transition-colors" style="color:#6B7280;"
                    onmouseover="this.style.color='#1F2126'" onmouseout="this.style.color='#6B7280'">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                        style="background-color:#FFF1F0;">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="#E2231A" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-extrabold" style="color:#1F2126;">Keputusan Off Request GA</h3>
                    </div>
                    <svg class="w-4 h-4 shrink-0 transition-transform mt-1.5"
                        :class="offRequestOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" style="color:#AEB0B6;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="offRequestOpen" x-transition x-cloak class="mt-4">

                    @if ($offTotal === 0)
                    <p class="text-sm font-semibold text-center py-8" style="color:#AEB0B6;">Tidak ada off request pada
                        periode ini.</p>
                    @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {{-- Diterima GA --}}
                        <div class="rounded-xl border p-4" style="border-color:#D1FAE5;background-color:#F0FDF4;">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider mb-1" style="color:#047857;">
                                Diterima GA
                                <span
                                    class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-extrabold align-middle"
                                    style="background-color:#DCFCE7;color:#16A34A;">{{ count($offAccepted) }}</span>
                            </p>
                            <p class="text-[11px] font-semibold mb-2" style="color:#6B7280;">Agent libur sesuai
                                permintaan off-nya.</p>
                            @if (empty($offAccepted))
                            <p class="text-xs font-semibold py-3" style="color:#AEB0B6;">Tidak ada.</p>
                            @else
                            <ul class="divide-y" style="border-color:#D1FAE5;">
                                @foreach ($offAccepted as $v)
                                <li class="flex items-center justify-between gap-3 py-2">
                                    <span class="text-sm font-bold truncate"
                                        style="color:#1F2126;">{{ $v['agent_nama'] }}</span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs font-semibold"
                                            style="color:#3F4249;">{{ $v['hari_nama'] }}</span>
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                            style="{{ $v['prioritas'] === 'tinggi' ? 'background-color:#FEF2F2;color:#B91C1C;' : 'background-color:#FFFBEB;color:#D97706;' }}">{{ ucfirst($v['prioritas']) }}</span>
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>

                        {{-- Ditolak GA --}}
                        <div class="rounded-xl border p-4" style="border-color:#FFE0DD;background-color:#FFF5F5;">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider mb-1" style="color:#B91C1C;">
                                Ditolak GA
                                <span
                                    class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-extrabold align-middle"
                                    style="background-color:#FEE2E2;color:#DC2626;">{{ count($offRejected) }}</span>
                            </p>
                            <p class="text-[11px] font-semibold mb-2" style="color:#6B7280;">Agent tetap dijadwalkan
                                kerja meski mengajukan off.</p>
                            @if (empty($offRejected))
                            <p class="text-xs font-semibold py-3" style="color:#AEB0B6;">Tidak ada.</p>
                            @else
                            <ul class="divide-y" style="border-color:#FFE0DD;">
                                @foreach ($offRejected as $v)
                                <li class="flex items-center justify-between gap-3 py-2">
                                    <span class="text-sm font-bold truncate"
                                        style="color:#1F2126;">{{ $v['agent_nama'] }}</span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs font-semibold" style="color:#3F4249;">{{ $v['hari_nama'] }}
                                            &middot; {{ $v['kode_shift'] }} {{ $v['nama_section'] }}</span>
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                            style="{{ $v['prioritas'] === 'tinggi' ? 'background-color:#FEF2F2;color:#B91C1C;' : 'background-color:#FFFBEB;color:#D97706;' }}">{{ ucfirst($v['prioritas']) }}</span>
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if ($unfilledCount > 0)
            <div class="flex items-start gap-3 p-4 rounded-xl text-sm font-semibold"
                style="background-color:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Ada <strong>{{ $unfilledCount }}</strong> slot kebutuhan operasional yang belum terisi.
                Pertimbangkan untuk generate ulang, atau lanjut dengan kondisi ini jika sudah dianggap wajar.
            </div>
            @endif

            {{-- Tombol aksi --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 card p-5">
                <form id="regen-form-element" action="{{ route('schedules.generate.process', $period) }}" method="POST">
                    @csrf
                    <button type="button" @click="regenShowModal = true" @if ($period->status === 'final') disabled
                        @endif
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold border
                        transition-colors"
                        style="{{ $period->status === 'final'
                                ? 'border-color:#D2D3D6;color:#AEB0B6;cursor:not-allowed;'
                                : 'border-color:#D2D3D6;color:#3F4249;background-color:#ffffff;' }}"
                        onmouseover="{{ $period->status !== 'final' ? "this.style.background='#F6F6F7';" : '' }}"
                        onmouseout="{{ $period->status !== 'final' ? "this.style.background='#ffffff';" : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Generate Ulang
                    </button>
                </form>

                <div class="flex items-center gap-3">
                    <a href="{{ route('pic.schedule-grid.edit', $period) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-colors"
                        style="background-color:#ffffff;color:#3F4249;border:1px solid #D2D3D6;"
                        onmouseover="this.style.background='#F6F6F7';" onmouseout="this.style.background='#ffffff';">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                            </path>
                        </svg>
                        Ubah Grid
                    </a>

                    <a href="{{ route('schedules.export-excel', $period) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all"
                        style="background-color:#10B981;color:#ffffff;box-shadow:0 4px 14px -2px rgba(16,185,129,0.35);"
                        onmouseover="this.style.background='#059669';" onmouseout="this.style.background='#10B981';">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Export Excel
                    </a>

                    @if ($period->status !== 'final')
                    <form action="{{ route('schedules.finalize', $period) }}" method="POST"
                        onsubmit="return confirm('Setelah disimpan sebagai final, jadwal tidak bisa di-generate ulang. Lanjutkan?');">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                            Simpan sebagai Final
                        </button>
                    </form>
                    @else
                    <a href="{{ route('schedules.history.show', $period) }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                        style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                        onmouseover="this.style.background='#C01A12';" onmouseout="this.style.background='#E2231A';">
                        Lihat di History
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Tab: Grid Mingguan vs Detail per Hari --}}
            <div x-data="{ activeTab: 'grid' }" class="card overflow-hidden">
                <div class="flex border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                    <button @click="activeTab = 'grid'"
                        class="flex-1 px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                        :style="activeTab === 'grid' ? 'border-bottom-color:#E2231A;color:#E2231A;background-color:#ffffff;' : 'border-bottom-color:transparent;color:#62656F;'">
                        Grid Mingguan
                    </button>
                    <button @click="activeTab = 'detail'"
                        class="flex-1 px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                        :style="activeTab === 'detail' ? 'border-bottom-color:#E2231A;color:#E2231A;background-color:#ffffff;' : 'border-bottom-color:transparent;color:#62656F;'">
                        Detail per Hari
                    </button>
                </div>

                {{-- ===== GRID MINGGUAN ===== --}}
                <div x-show="activeTab === 'grid'" class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr style="border-bottom:1px solid #E9E9EB;background-color:#F6F6F7;">
                                <th class="px-5 py-3 sticky left-0 z-10"
                                    style="background-color:#F6F6F7;min-width:200px;">
                                    <span class="text-[10px] font-bold uppercase tracking-wider"
                                        style="color:#82858E;">Nama Agent</span>
                                </th>
                                @foreach ($hariList as $hari)
                                @php $isUnfulfilled = !empty($unfulfilledDays[$hari['index']]); @endphp
                                <th class="px-5 py-3 text-center min-w-[110px]"
                                    style="{{ $isUnfulfilled ? 'background-color:#FEE2E2;' : '' }}">
                                    <span class="text-xs font-bold"
                                        style="{{ $isUnfulfilled ? 'color:#B91C1C;' : 'color:#3F4249;' }}">{{ ucfirst($hari['nama']) }}</span>
                                    <div class="text-[10px]"
                                        style="{{ $isUnfulfilled ? 'color:#DC2626;' : 'color:#82858E;' }}">
                                        {{ $hari['label'] }}
                                    </div>
                                    @if($isUnfulfilled)
                                    <div class="text-[9px] font-bold mt-1 uppercase"
                                        style="color:#E2231A; padding:2px 4px; background:#FFE0DD; border-radius:4px; display:inline-block;">
                                        Tidak Terpenuhi</div>
                                    @endif
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E9E9EB;">
                            @foreach ($agents as $agent)
                            <tr style="{{ $agent->status !== 'aktif' ? 'background-color:#FEF2F2;' : 'background-color:#ffffff;' }}"
                                onmouseover="if(this.style.backgroundColor!=='rgb(254, 242, 242)') this.style.background='#F6F6F7';"
                                onmouseout="if(this.style.backgroundColor!=='rgb(254, 242, 242)') this.style.background='#ffffff';">
                                <td class="px-5 py-3 sticky left-0 z-10"
                                    style="{{ $agent->status !== 'aktif' ? 'background-color:#FEF2F2;' : 'background-color:#ffffff;' }}">
                                    <div class="font-bold text-sm" style="color:#1F2126;">{{ $agent->nama_agent }}</div>
                                    <div class="text-xs mt-0.5" style="color:#82858E;">Level {{ $agent->level }}</div>
                                </td>

                                @if ($agent->status !== 'aktif')
                                <td colspan="7" class="text-center px-3 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg"
                                        style="background-color:#FEE2E2;color:#B91C1C;">
                                        SKORSING &mdash; Tidak Dijadwalkan
                                    </span>
                                </td>
                                @else
                                @foreach ($hariList as $hari)
                                @php $cell = $grid[$agent->id][$hari['index']] ?? null; @endphp
                                @php $isUnfulfilled = !empty($unfulfilledDays[$hari['index']]); @endphp
                                <td class="text-center px-5 py-3"
                                    style="{{ $isUnfulfilled ? 'background-color:#FEF2F2;' : '' }}">
                                    @if ($cell && $cell->status === 'kerja')
                                    <span
                                        class="inline-flex flex-col items-center justify-center p-1.5 rounded-lg w-full"
                                        style="background-color:#FFF1F0;border:1px solid #FFE0DD;">
                                        <span class="font-extrabold text-sm"
                                            style="color:#E2231A;">{{ $cell->shift->kode_shift ?? '-' }}</span>
                                        <span class="text-[10px] font-semibold mt-0.5"
                                            style="color:#82858E;">{{ $cell->section->nama_section ?? '-' }}</span>
                                    </span>
                                    @else
                                    <span class="text-xs font-bold"
                                        style="{{ $isUnfulfilled ? 'color:#FCA5A5;' : 'color:#AEB0B6;' }}">OFF</span>
                                    @endif
                                </td>
                                @endforeach
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ===== RINGKASAN GRID OPERASIONAL ===== --}}
                <div x-show="activeTab === 'grid'" class="overflow-x-auto border-t" style="border-color:#E9E9EB;">
                    <div class="px-5 py-3 flex items-center gap-2" style="background-color:#FCFAF7;">
                        <svg class="w-4 h-4" fill="none" stroke="#E2231A" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider" style="color:#E2231A;">Ringkasan
                            Operasional</span>
                        <span class="text-xs ml-2" style="color:#82858E;">(Terisi / Target)</span>
                    </div>
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr style="background-color:#F6F6F7; border-bottom:1px solid #E9E9EB;">
                                <th class="px-4 py-2 sticky left-0 z-10"
                                    style="background-color:#F6F6F7; min-width:200px;">
                                    <span class="text-[10px] font-bold uppercase tracking-wider"
                                        style="color:#82858E;">Section / Shift</span>
                                </th>
                                @foreach ($hariList as $hari)
                                @php $isUnfulfilled = !empty($unfulfilledDays[$hari['index']]); @endphp
                                <th class="px-4 py-2 text-center min-w-[90px]"
                                    style="{{ $isUnfulfilled ? 'background-color:#FEE2E2;' : '' }}">
                                    <span class="font-bold"
                                        style="{{ $isUnfulfilled ? 'color:#B91C1C;' : 'color:#62656F;' }}">{{ ucfirst($hari['nama']) }}</span>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summaryGrid as $sectionName => $shiftRows)
                            {{-- Section Header Row --}}
                            <tr style="background-color:#FFF8F7; border-top:2px solid #E9E9EB;">
                                <td colspan="{{ count($hariList) + 1 }}" class="px-4 py-1.5 sticky left-0">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest"
                                        style="color:#E2231A;">{{ $sectionName }}</span>
                                </td>
                            </tr>
                            {{-- Shift Rows --}}
                            @foreach ($shiftRows as $shiftCode => $days)
                            @php $firstDay = array_values($days)[0] ?? []; @endphp
                            <tr class="border-b" style="border-color:#F0F0F0;"
                                onmouseover="this.style.background='#FAFAFA'"
                                onmouseout="this.style.background='#ffffff'">
                                <td class="px-4 py-2 sticky left-0" style="background-color:inherit;">
                                    <span class="font-semibold"
                                        style="color:#3F4249;">{{ $firstDay['shift_detail'] ?? $shiftCode }}</span>
                                </td>
                                @foreach ($hariList as $hariSel)
                                @php
                                $d = $days[$hariSel['index']] ?? ['terisi' => 0, 'kebutuhan' => 0];
                                $isUnfulfilled = !empty($unfulfilledDays[$hariSel['index']]);
                                $isCellShort = $d['terisi'] < $d['kebutuhan'] && $d['kebutuhan']> 0;
                                    @endphp
                                    <td class="px-4 py-2 text-center"
                                        style="{{ $isUnfulfilled && $isCellShort ? 'background-color:#FEF2F2;' : ($isUnfulfilled ? 'background-color:#FEF2F2;' : '') }}">
                                        @if ($d['kebutuhan'] > 0 || $d['terisi'] > 0)
                                        <span class="inline-flex items-center gap-0.5 font-bold text-xs"
                                            style="{{ $isCellShort ? 'color:#DC2626;' : 'color:#16A34A;' }}">
                                            {{ $d['terisi'] }}
                                        </span>
                                        <span class="text-[10px]" style="color:#AEB0B6;">/</span>
                                        <span class="text-[10px] font-semibold"
                                            style="color:#62656F;">{{ $d['kebutuhan'] }}</span>
                                        @else
                                        <span style="color:#D1D5DB;">-</span>
                                        @endif
                                    </td>
                                    @endforeach
                            </tr>
                            @endforeach
                            {{-- Section Total Row --}}
                            @php
                            $secTotals = [];
                            foreach ($hariList as $h) {
                            $secTotals[$h['index']] = ['terisi' => 0, 'kebutuhan' => 0];
                            foreach ($shiftRows as $sr) {
                            $secTotals[$h['index']]['terisi'] += $sr[$h['index']]['terisi'] ?? 0;
                            $secTotals[$h['index']]['kebutuhan'] += $sr[$h['index']]['kebutuhan'] ?? 0;
                            }
                            }
                            @endphp
                            <tr style="background-color:#F0F9FF; border-top:1px solid #BAE6FD;">
                                <td class="px-4 py-1.5 sticky left-0" style="background-color:#F0F9FF;">
                                    <span class="text-[10px] font-extrabold uppercase" style="color:#0369A1;">Total
                                        {{ $sectionName }}</span>
                                </td>
                                @foreach ($hariList as $hariTot)
                                @php $st = $secTotals[$hariTot['index']]; @endphp
                                <td class="px-4 py-1.5 text-center">
                                    <span class="font-extrabold text-xs"
                                        style="{{ $st['terisi'] < $st['kebutuhan'] ? 'color:#DC2626;' : 'color:#0369A1;' }}">
                                        {{ $st['terisi'] }}/{{ $st['kebutuhan'] }}
                                    </span>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ===== DETAIL PER HARI ===== --}}
                <div x-show="activeTab === 'detail'">
                    <div x-data="{ activeDay: 0 }" class="p-6">
                        <div class="flex gap-2 overflow-x-auto pb-4 mb-4 border-b scrollbar-thin"
                            style="border-color:#E9E9EB;">
                            @foreach ($hariList as $hari)
                            <button @click="activeDay = {{ $hari['index'] }}"
                                class="px-5 py-2.5 rounded-lg text-sm font-bold whitespace-nowrap transition-all"
                                :style="activeDay === {{ $hari['index'] }} ? 'background-color:#E2231A;color:#fff;' : 'background-color:#F6F6F7;color:#62656F;'"
                                style="{{ $loop->first ? 'background-color:#E2231A;color:#fff;' : 'background-color:#F6F6F7;color:#62656F;' }}">
                                {{ ucfirst($hari['nama']) }}
                            </button>
                            @endforeach
                        </div>

                        @foreach ($hariList as $hari)
                        <div x-show="activeDay === {{ $hari['index'] }}">
                            <h4 class="text-sm font-extrabold mb-4" style="color:#1F2126;">
                                {{ ucfirst($hari['nama']) }}, {{ $hari['label'] }}
                            </h4>

                            @if ($perHari[$hari['index']]->isEmpty())
                            <p class="text-sm font-semibold text-center py-6" style="color:#82858E;">Tidak ada penugasan
                                di hari ini.</p>
                            @else
                            <div class="overflow-x-auto rounded-xl border" style="border-color:#E9E9EB;">
                                <table class="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                            <th class="px-5 py-3"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Shift</span></th>
                                            <th class="px-5 py-3"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Section</span></th>
                                            <th class="px-5 py-3"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Nama Agent</span></th>
                                            <th class="px-5 py-3"><span
                                                    class="text-[10px] font-bold uppercase tracking-wider"
                                                    style="color:#82858E;">Level</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color:#E9E9EB;">
                                        @foreach ($perHari[$hari['index']] as $row)
                                        <tr style="background-color:#ffffff;"
                                            onmouseover="this.style.background='#F6F6F7';"
                                            onmouseout="this.style.background='#ffffff';">
                                            <td class="px-5 py-3 font-extrabold" style="color:#E2231A;">
                                                {{ $row->shift->kode_shift ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 font-semibold" style="color:#3F4249;">
                                                {{ $row->section->nama_section ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 font-bold" style="color:#1F2126;">
                                                {{ $row->agent->nama_agent ?? '-' }}
                                            </td>
                                            <td class="px-5 py-3 text-sm" style="color:#62656F;">Level
                                                {{ $row->agent->level ?? '-' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Modal Konfirmasi Generate Ulang --}}
            <div x-show="regenShowModal" x-cloak @keydown.escape.window="regenShowModal = false"
                class="fixed inset-0 z-[99999] flex items-center justify-center overflow-y-auto p-5"
                style="display:none;">

                <div @click="regenShowModal = false" class="fixed inset-0 h-full w-full"
                    style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

                <div @click.stop x-show="regenShowModal" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-lift space-y-4">

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center"
                            style="background-color:#FFFBEB;">
                            <svg class="w-6 h-6" style="color:#D97706;" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold" style="color:#1F2126;">Generate Ulang Jadwal?</h3>
                            <p class="text-sm font-semibold mt-1" style="color:#3F4249;">
                                Seluruh hasil jadwal yang ada saat ini akan <strong style="color:#D97706;">dihapus dan
                                    diganti</strong>
                                dengan hasil generate yang baru. Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    {{-- Panel Konfigurasi GA di dalam Modal --}}
                    <div class="border-t pt-4" style="border-color:#E9E9EB;">
                        <button type="button" @click="regenShowCfg = !regenShowCfg"
                            class="inline-flex items-center gap-1.5 text-xs font-bold transition-colors"
                            style="color:#6B7280;">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="regenShowCfg ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <span
                                x-text="regenShowCfg ? 'Sembunyikan Konfigurasi GA' : 'Konfigurasi GA (Opsional)'"></span>
                        </button>

                        <div x-show="regenShowCfg" x-transition class="mt-3 p-4 rounded-xl border"
                            style="border-color:#E9E9EB;background-color:#FCFAF7;display:none;">
                            <p class="text-[10px] font-bold uppercase tracking-wider mb-3" style="color:#82858E;">
                                Parameter GA &mdash; Kosongkan untuk pakai default</p>

                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1" style="color:#3F4249;">Ukuran
                                        Populasi <span style="color:#AEB0B6;">(def: 100)</span></label>
                                    <input type="number" id="regen_population_size" placeholder="100" min="10" max="500"
                                        class="input-field py-1.5 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1" style="color:#3F4249;">Maks.
                                        Generasi <span style="color:#AEB0B6;">(def: 400)</span></label>
                                    <input type="number" id="regen_max_generations" placeholder="400" min="10"
                                        max="2000" class="input-field py-1.5 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1"
                                        style="color:#3F4249;">Stagnation Limit <span style="color:#AEB0B6;">(def:
                                            50)</span></label>
                                    <input type="number" id="regen_stagnation_limit" placeholder="50" min="5" max="500"
                                        class="input-field py-1.5 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1" style="color:#3F4249;">Mutation
                                        Rate <span style="color:#AEB0B6;">(def: 0.01)</span></label>
                                    <input type="number" id="regen_mutation_rate" placeholder="0.01" min="0.001"
                                        max="0.5" step="0.001" class="input-field py-1.5 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1" style="color:#3F4249;">Elite
                                        Rate <span style="color:#AEB0B6;">(def: 0.1)</span></label>
                                    <input type="number" id="regen_elite_rate" placeholder="0.1" min="0.05" max="0.5"
                                        step="0.01" class="input-field py-1.5 text-xs w-full">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1"
                                        style="color:#3F4249;">Tournament Size <span style="color:#AEB0B6;">(def:
                                            3)</span></label>
                                    <input type="number" id="regen_tournament_size" placeholder="3" min="2" max="10"
                                        class="input-field py-1.5 text-xs w-full">
                                </div>
                            </div>

                            <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#82858E;">Bobot
                                SC</p>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach (['sc1' => 100, 'sc2' => 20, 'sc3' => 30, 'sc4' => 10, 'sc5' => 15, 'sc6' =>
                                50, 'sc7' => 12, 'sc8' => 5] as $sc => $def)
                                <div>
                                    <label class="block text-[10px] font-semibold mb-1"
                                        style="color:#3F4249;">{{ strtoupper($sc) }} <span
                                            style="color:#AEB0B6;">({{ $def }})</span></label>
                                    <input type="number" id="regen_weight_{{ $sc }}" placeholder="{{ $def }}" min="0"
                                        max="1000" step="0.5" class="input-field py-1.5 text-xs w-full">
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="regenShowModal = false" :disabled="regenLoading"
                            class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-colors"
                            style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                            onmouseover="this.style.background='#F6F6F7';" onmouseout="this.style.background='#fff';">
                            Batal
                        </button>
                        <button type="button" @click="regenSubmit()" :disabled="regenLoading"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#D97706;" onmouseover="this.style.background='#B45309';"
                            onmouseout="this.style.background='#D97706';">
                            <svg x-show="regenLoading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="regenLoading ? 'Memproses...' : 'Ya, Generate Ulang'"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>