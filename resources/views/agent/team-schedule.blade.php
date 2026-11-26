<x-app-layout>
    <x-slot name="header">Jadwal Tim</x-slot>

    <div class="py-8 animate-fade-in"
         x-data="teamSchedule()"
         x-init="init()">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ===== HEADER ===== --}}
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h1 class="page-title">Jadwal Tim</h1>
                </div>

                {{-- Badge periode aktif --}}
                <span x-show="period" x-cloak
                      class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full"
                      style="background-color:#FFF1F0;color:#C01A12;border:1px solid #FECDCA;">
                    <span class="w-1.5 h-1.5 rounded-full inline-block" style="background-color:#E2231A;"></span>
                    <span x-text="period ? period.nama_periode + ' · ' + period.tanggal_mulai + ' – ' + period.tanggal_selesai : ''"></span>
                </span>
            </div>

            {{-- ===== FILTER BAR ===== --}}
            <div class="flex flex-col sm:flex-row gap-3">

                {{-- Pilih Periode --}}
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-semibold mb-1.5" style="color:#62656F;">Periode</label>
                    <select x-model="selectedPeriodId"
                            @change="onPeriodChange()"
                            class="input-field py-2.5 text-sm bg-white">
                        @forelse ($allPeriods as $period)
                            <option value="{{ $period->id }}">
                                {{ $period->nama_periode }}
                                ({{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }})
                            </option>
                        @empty
                            <option disabled>Belum ada periode final</option>
                        @endforelse
                    </select>
                </div>

                {{-- Cari Nama Agent --}}
                <div class="flex-1 min-w-0">
                    <label class="block text-xs font-semibold mb-1.5" style="color:#62656F;">Cari Agent</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                             style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                        </svg>
                        <input type="text"
                               x-model="search"
                               @keydown.enter.prevent="fetchData()"
                               placeholder="Nama agent..."
                               class="input-field py-2.5 text-sm"
                               style="padding-left:2.25rem;">
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="button"
                            @click="fetchData()"
                            class="px-4 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                        Cari
                    </button>
                    <button type="button"
                            x-show="search"
                            x-cloak
                            @click="search = ''; fetchData()"
                            class="px-4 py-2.5 rounded-lg text-sm font-semibold transition-all"
                            style="background-color:#F6F6F7;color:#62656F;"
                            onmouseover="this.style.background='#E9E9EB';"
                            onmouseout="this.style.background='#F6F6F7';">
                        Reset
                    </button>
                </div>
            </div>

            {{-- ===== LOADING SKELETON ===== --}}
            <div x-show="loading" class="card overflow-hidden animate-pulse">
                <div class="p-5 space-y-3">
                    <div class="h-8 rounded-lg w-full" style="background-color:#F3F3F4;"></div>
                    @for ($i = 0; $i < 6; $i++)
                        <div class="h-12 rounded-lg w-full" style="background-color:#F9F9FA;"></div>
                    @endfor
                </div>
            </div>

            {{-- ===== KONTEN UTAMA ===== --}}
            <div x-show="!loading" x-cloak>

                {{-- Belum ada periode --}}
                <template x-if="!loading && headers.length === 0 && rows.length === 0">
                    <div class="card p-12 text-center border-dashed">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                             style="background-color:#F6F6F7;">
                            <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold" style="color:#62656F;">Belum ada jadwal yang dipublikasikan.</p>
                    </div>
                </template>

                {{-- Tidak ada agent hasil filter --}}
                <template x-if="!loading && headers.length > 0 && rows.length === 0">
                    <div class="card p-12 text-center border-dashed">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3"
                             style="background-color:#F6F6F7;">
                            <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold" style="color:#62656F;">
                            Tidak ada agent yang cocok
                            <template x-if="search">
                                <span> dengan nama "<strong x-text="search"></strong>"</span>
                            </template>.
                        </p>
                    </div>
                </template>

                {{-- Tabel jadwal --}}
                <template x-if="!loading && rows.length > 0">
                    <div>
                        <div class="card overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr style="background-color:#FCFAF7;border-bottom:2px solid #E9E9EB;">
                                            <th class="text-left px-5 py-3.5 font-bold text-xs uppercase tracking-wider sticky left-0 z-10"
                                                style="color:#62656F;background-color:#FCFAF7;min-width:160px;">
                                                Agent
                                            </th>
                                            <template x-for="h in headers" :key="h.tanggal_raw">
                                                <th class="text-center px-3 py-3.5 font-bold text-xs uppercase tracking-wider"
                                                    :style="{
                                                        color: h.tanggal_raw === today ? '#C01A12' : '#62656F',
                                                        backgroundColor: h.tanggal_raw === today ? '#FFF1F0' : '#FCFAF7',
                                                        minWidth: '90px'
                                                    }">
                                                    <div x-text="h.nama"></div>
                                                    <div class="text-[10px] font-semibold mt-0.5"
                                                         :style="{ color: h.tanggal_raw === today ? '#E2231A' : '#AEB0B6' }">
                                                        <span x-text="h.tanggal"></span>
                                                        <template x-if="h.tanggal_raw === today">
                                                            <span class="ml-1 px-1 py-0.5 rounded font-extrabold"
                                                                  style="background-color:#E2231A;color:#fff;font-size:9px;">
                                                                HARI INI
                                                            </span>
                                                        </template>
                                                    </div>
                                                </th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color:#F3F3F4;">
                                        <template x-for="row in rows" :key="row.id">
                                            <tr class="transition-colors"
                                                :style="{ backgroundColor: row.is_me ? '#FFFBF0' : '#ffffff' }"
                                                @mouseover="$el.style.backgroundColor = row.is_me ? '#FFF3CC' : '#F9F9FA'"
                                                @mouseout="$el.style.backgroundColor = row.is_me ? '#FFFBF0' : '#ffffff'">

                                                {{-- Nama agent --}}
                                                <td class="px-5 py-3.5 sticky left-0 z-10"
                                                    :style="{ backgroundColor: 'inherit' }">
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 text-[11px] font-extrabold text-white"
                                                             :style="{ backgroundColor: row.is_me ? '#E2231A' : '#AEB0B6' }"
                                                             x-text="row.inisial">
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-xs leading-tight"
                                                               :style="{ color: row.is_me ? '#C01A12' : '#1F2126' }">
                                                                <span x-text="row.nama"></span>
                                                                <template x-if="row.is_me">
                                                                    <span class="ml-1 text-[9px] font-extrabold px-1 py-0.5 rounded uppercase"
                                                                          style="background-color:#E2231A;color:#fff;">Saya</span>
                                                                </template>
                                                            </p>
                                                            <p class="text-[10px] mt-0.5" style="color:#AEB0B6;">
                                                                Lvl <span x-text="row.level"></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- Sel jadwal per hari --}}
                                                <template x-for="hari in row.hari_list" :key="hari.tanggal_raw">
                                                    <td class="px-2 py-3 text-center"
                                                        :style="{ backgroundColor: hari.tanggal_raw === today ? '#FFF8F8' : '' }">
                                                        <template x-if="hari.is_kerja">
                                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-extrabold"
                                                                      :style="{
                                                                          backgroundColor: hari.tanggal_raw === today ? '#FEE2E0' : '#F3F3F4',
                                                                          color: hari.tanggal_raw === today ? '#C01A12' : '#1F2126'
                                                                      }"
                                                                      x-text="hari.kode_shift">
                                                                </span>
                                                                <span class="text-[9px] font-semibold"
                                                                      style="color:#AEB0B6;"
                                                                      x-show="hari.section"
                                                                      x-text="hari.section">
                                                                </span>
                                                            </div>
                                                        </template>
                                                        <template x-if="!hari.is_kerja">
                                                            <span class="inline-flex items-center justify-center px-2 py-1 rounded-lg text-[10px] font-bold uppercase"
                                                                  style="background-color:#F3F3F4;color:#D2D3D6;">
                                                                OFF
                                                            </span>
                                                        </template>
                                                    </td>
                                                </template>

                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div class="flex items-center gap-4 text-xs mt-3" style="color:#AEB0B6;">
                            <span><span x-text="rows.length"></span> agent ditampilkan</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded" style="background-color:#FFFBF0;border:1px solid #E9E9EB;"></span>
                                Jadwal saya
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-6 h-5 rounded text-[9px] font-bold"
                                      style="background-color:#F3F3F4;color:#1F2126;">S1</span>
                                Shift kerja
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-8 h-5 rounded text-[9px] font-bold"
                                      style="background-color:#F3F3F4;color:#D2D3D6;">OFF</span>
                                Hari libur
                            </span>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>

    <script>
        function teamSchedule() {
            return {
                // State
                selectedPeriodId: '{{ $selectedPeriod?->id ?? '' }}',
                search:           '',
                loading:          true,
                period:           null,
                headers:          [],
                rows:             [],
                today:            '{{ today()->toDateString() }}',

                init() {
                    this.fetchData();
                },

                onPeriodChange() {
                    // Reset search saat ganti periode supaya tidak confusing
                    this.search = '';
                    this.fetchData();
                },

                fetchData() {
                    this.loading = true;

                    const params = new URLSearchParams({
                        period_id: this.selectedPeriodId,
                        search:    this.search,
                    });

                    fetch(`{{ route('agent.schedule.team.data') }}?${params}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.period  = data.period;
                        this.headers = data.headers;
                        this.rows    = data.rows;
                        this.today   = data.today;
                    })
                    .catch(err => console.error('Gagal memuat jadwal tim:', err))
                    .finally(() => {
                        this.loading = false;
                    });
                },
            };
        }
    </script>
</x-app-layout>
