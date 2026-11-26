<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="animate-fade-in">

        {{-- Page heading + CTA --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="page-title">Operations Dashboard</h1>
                <p class="text-sm mt-1" style="color:#82858E;">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>
            <a href="{{ url('/schedules/generate') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
               style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
               onmouseover="this.style.background='#C01A12';"
               onmouseout="this.style.background='#E2231A';">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Generate Jadwal Baru
            </a>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

            {{-- Total Agent --}}
            <div class="card p-5 card-hover">
                <div class="flex items-start justify-between">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:#FFF1F0;">
                        <svg class="w-5 h-5" style="color:#E2231A;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 110 7.75M9 8a4 4 0 100 7.75M12 12a4 4 0 100-8 4 4 0 000 8z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold tracking-tight" style="color:#1F2126;">{{ $totalAgent }}</div>
                    <div class="text-sm font-semibold mt-1" style="color:#62656F;">Total Agent</div>
                    <div class="text-xs mt-1" style="color:#AEB0B6;">Terdaftar di roster</div>
                </div>
            </div>

            {{-- Agent Aktif --}}
            <div class="card p-5 card-hover">
                <div class="flex items-start justify-between">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:#ECFDF5;">
                        <svg class="w-5 h-5" style="color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full"
                          style="background:#ECFDF5;color:#059669;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Aktif
                    </span>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold tracking-tight" style="color:#1F2126;">{{ $agentAktif }}</div>
                    <div class="text-sm font-semibold mt-1" style="color:#62656F;">Agent Aktif</div>
                    <div class="text-xs mt-1" style="color:#AEB0B6;">Di atas target operasional</div>
                </div>
            </div>

            {{-- Agent Skorsing --}}
            <div class="card p-5 card-hover">
                <div class="flex items-start justify-between">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:#FFFBEB;">
                        <svg class="w-5 h-5" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.502-3.032-1.502-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold tracking-tight" style="color:#1F2126;">{{ $agentSkorsing }}</div>
                    <div class="text-sm font-semibold mt-1" style="color:#62656F;">Agent Non-Aktif</div>
                    <div class="text-xs mt-1" style="color:#AEB0B6;">Perlu ditinjau</div>
                </div>
            </div>

            {{-- Periode Aktif --}}
            <div class="card p-5 card-hover">
                <div class="flex items-start justify-between">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:#EEF2FF;">
                        <svg class="w-5 h-5" style="color:#4F46E5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-lg font-extrabold tracking-tight leading-tight" style="color:#1F2126;">
                        {{ $periodeTerbaru?->nama_periode ?? '—' }}
                    </div>
                    <div class="text-sm font-semibold mt-1" style="color:#62656F;">Periode Aktif</div>
                    @if($periodeTerbaru)
                        <span class="inline-flex items-center gap-1.5 mt-1.5 px-2 py-0.5 rounded-full text-xs font-semibold"
                              style="{{ $periodeTerbaru->status === 'final' ? 'background:#ECFDF5;color:#059669;' : 'background:#FFFBEB;color:#D97706;' }}">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  style="background-color:{{ $periodeTerbaru->status === 'final' ? '#059669' : '#D97706' }};"></span>
                            {{ $periodeTerbaru->status === 'final' ? 'Final' : 'Draft' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ==================== REMINDER SOP INBOUND ==================== --}}
        @php
            $sopStatus = $inboundSop['sudah'] ?? 0;
            $sopBelum  = $inboundSop['belum'] ?? 0;
            $sopTotal  = $inboundSop['total'] ?? 0;
            $sopAktif  = $inboundSop['ada_periode_aktif'] ?? false;
            $sopAgents = $inboundSop['belum_agents'] ?? [];
            $sopBulan  = now()->translatedFormat('F Y');
        @endphp

        @if ($sopTotal > 0)
        <div class="card p-5 mb-6"
             :class="''"
             style="{{ $sopBelum === 0 ? 'border-left:4px solid #16A34A;' : ($sopAktif ? 'border-left:4px solid #D97706;' : 'border-left:4px solid #DC2626;') }}"
             x-data="{ showDetail: false }">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    {{-- Icon --}}
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="{{ $sopBelum === 0 ? 'background-color:#DCFCE7;' : ($sopAktif ? 'background-color:#FFFBEB;' : 'background-color:#FEF2F2;') }}">
                        @if ($sopBelum === 0)
                            <svg class="w-5 h-5" fill="none" stroke="#16A34A" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif ($sopAktif)
                            <svg class="w-5 h-5" fill="none" stroke="#D97706" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="#DC2626" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="font-extrabold text-sm" style="color:#1F2126;">
                                SOP Inbound — {{ $sopBulan }}
                            </h4>
                            @if ($sopBelum === 0)
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" style="background-color:#DCFCE7;color:#16A34A;">Semua Terpenuhi</span>
                            @elseif ($sopAktif)
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" style="background-color:#FFFBEB;color:#D97706;">Masih Ada Periode Aktif</span>
                            @else
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" style="background-color:#FEF2F2;color:#DC2626;">Ada yang Terlewat</span>
                            @endif
                        </div>

                        @if ($sopBelum === 0)
                            <p class="text-xs mt-1" style="color:#6B7280;">
                                Semua {{ $sopTotal }} agent sudah mendapat jadwal Inbound di {{ $sopBulan }}.
                            </p>
                        @else
                            <p class="text-xs mt-1" style="color:#6B7280;">
                                {{ $sopBelum }} dari {{ $sopTotal }} agent belum mendapat jadwal Inbound.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Toggle detail & Link --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if ($sopBelum > 0)
                        <button type="button" @click="showDetail = !showDetail"
                            class="text-xs px-3 py-1.5 rounded-lg font-bold transition-colors"
                            style="background-color:#F6F6F7;color:#3F4249;border:1px solid #E9E9EB;"
                            x-text="showDetail ? 'Sembunyikan' : 'Lihat Detail'">
                        </button>
                        @if ($sopAktif)
                            <a href="{{ url('/schedules/generate') }}"
                               class="text-xs px-3 py-1.5 rounded-lg font-bold transition-colors"
                               style="background-color:#E2231A;color:#fff;">
                                Atur Sekarang
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Detail agent yang belum --}}
            @if ($sopBelum > 0)
                <div x-show="showDetail" x-transition class="mt-4 pt-4 border-t" style="border-color:#E9E9EB;display:none;">
                    <p class="text-xs font-bold uppercase tracking-wider mb-3" style="color:#82858E;">Agent Belum Mendapat Inbound</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach ($sopAgents as $agentSop)
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg"
                                 style="{{ $sopAktif ? 'background-color:#FFFBEB;' : 'background-color:#FEF2F2;' }}">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                      style="{{ $agentSop['level'] == 1 ? 'background-color:#EEF2FF;color:#4F46E5;' : ($agentSop['level'] == 2 ? 'background-color:#F0FDF4;color:#16A34A;' : 'background-color:#F3F4F6;color:#6B7280;') }}">
                                    L{{ $agentSop['level'] }}
                                </span>
                                <span class="text-xs font-semibold truncate" style="color:#1F2126;">{{ $agentSop['nama_agent'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif
        {{-- ==================== END REMINDER SOP ==================== --}}

        {{-- Baris tengah: Period Info + Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

            {{-- Periode Terbaru --}}
            <div class="lg:col-span-2 card">
                <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b" style="border-color:#E9E9EB;">
                    <div>
                        <h3 class="font-bold" style="color:#1F2126;">Status Periode Jadwal</h3>
                    </div>
                    @if($periodeTerbaru)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('schedules.history.show', $periodeTerbaru) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                               style="background-color:#E2231A;"
                               onmouseover="this.style.background='#C01A12';"
                               onmouseout="this.style.background='#E2231A';">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Lihat Jadwal
                            </a>
                            <a href="{{ route('schedules.export-excel', $periodeTerbaru) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                               style="border:1px solid #D2D3D6;color:#3F4249;background:#fff;"
                               onmouseover="this.style.background='#F6F6F7';"
                               onmouseout="this.style.background='#fff';">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export Excel
                            </a>
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    @if($periodeTerbaru)
                        <div class="rounded-xl p-4 text-white" style="background:linear-gradient(135deg,#E2231A,#C01A12);">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wide" style="color:rgba(255,255,255,0.7);">
                                    {{ $periodeTerbaru->status === 'final' ? 'Dipublikasikan' : 'Draft' }}
                                </span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-lg font-extrabold mt-2">{{ $periodeTerbaru->nama_periode }}</div>
                            <div class="text-sm mt-1" style="color:rgba(255,255,255,0.8);">
                                {{ $periodeTerbaru->tanggal_mulai->translatedFormat('d M Y') }}
                                →
                                {{ $periodeTerbaru->tanggal_selesai->translatedFormat('d M Y') }}
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl p-6 text-center border-2 border-dashed" style="border-color:#D2D3D6;">
                            <svg class="w-8 h-8 mx-auto mb-2" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm font-semibold" style="color:#62656F;">Belum ada periode jadwal</p>
                            <a href="{{ url('/schedules/generate') }}"
                               class="inline-flex items-center gap-1 mt-2 text-sm font-bold transition-colors"
                               style="color:#E2231A;"
                               onmouseover="this.style.color='#C01A12';"
                               onmouseout="this.style.color='#E2231A';">
                                Buat sekarang →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="px-6 py-4 border-b" style="border-color:#E9E9EB;">
                    <h3 class="font-bold" style="color:#1F2126;">Aksi Cepat</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ url('/agents') }}"
                       class="flex items-center gap-3 p-3 rounded-xl transition-colors"
                       style="background:#FFF1F0;border:1px solid #FFE0DD;"
                       onmouseover="this.style.background='#FFE0DD';"
                       onmouseout="this.style.background='#FFF1F0';">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white" style="background:#E2231A;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 110 7.75M9 8a4 4 0 100 7.75M12 12a4 4 0 100-8 4 4 0 000 8z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-bold" style="color:#C01A12;">Kelola Agent</div>
                            <div class="text-xs" style="color:#E2231A;">Data & status roster</div>
                        </div>
                        <svg class="w-4 h-4" style="color:#E2231A;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ url('/off-requests') }}"
                       class="flex items-center gap-3 p-3 rounded-xl transition-colors"
                       style="background:#FFFBEB;border:1px solid #FEF3C7;"
                       onmouseover="this.style.background='#FEF3C7';"
                       onmouseout="this.style.background='#FFFBEB';">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white" style="background:#D97706;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-bold" style="color:#B45309;">Off Requests</div>
                            <div class="text-xs" style="color:#D97706;">Persetujuan izin agent</div>
                        </div>
                        <svg class="w-4 h-4" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ url('/shift-swaps') }}"
                       class="flex items-center gap-3 p-3 rounded-xl transition-colors"
                       style="background:#EEF2FF;border:1px solid #E0E7FF;"
                       onmouseover="this.style.background='#E0E7FF';"
                       onmouseout="this.style.background='#EEF2FF';">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white" style="background:#4F46E5;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-bold" style="color:#3730A3;">Shift Swaps</div>
                            <div class="text-xs" style="color:#4F46E5;">Tukar jadwal antar agent</div>
                        </div>
                        <svg class="w-4 h-4" style="color:#4F46E5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ url('/schedules/history') }}"
                       class="flex items-center gap-3 p-3 rounded-xl transition-colors"
                       style="background:#F6F6F7;border:1px solid #E9E9EB;"
                       onmouseover="this.style.background='#E9E9EB';"
                       onmouseout="this.style.background='#F6F6F7';">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white" style="background:#3F4249;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-bold" style="color:#3F4249;">History Jadwal</div>
                            <div class="text-xs" style="color:#62656F;">Arsip periode lampau</div>
                        </div>
                        <svg class="w-4 h-4" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Menu Lengkap --}}
        <div class="card">
            <div class="px-6 py-4 border-b" style="border-color:#E9E9EB;">
                <h3 class="font-bold" style="color:#1F2126;">Menu Lengkap</h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

                <a href="{{ url('/agents') }}"
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all card-hover"
                   style="border:1px solid #E9E9EB;">
                    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#EEF2FF;">
                        <svg class="w-5 h-5" style="color:#4F46E5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 110 7.75M9 8a4 4 0 100 7.75M12 12a4 4 0 100-8 4 4 0 000 8z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-bold text-sm" style="color:#E2231A;">Kelola Agent</p>
                    </div>
                </a>

                <a href="{{ url('/schedules/generate') }}"
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all card-hover"
                   style="border:1px solid #E9E9EB;">
                    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FFFBEB;">
                        <svg class="w-5 h-5" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-bold text-sm" style="color:#E2231A;">Generate Jadwal</p>
                    </div>
                </a>

                <a href="{{ url('/schedules/history') }}"
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all card-hover"
                   style="border:1px solid #E9E9EB;">
                    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#EFF6FF;">
                        <svg class="w-5 h-5" style="color:#3B82F6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-bold text-sm" style="color:#E2231A;">History Jadwal</p>
                    </div>
                </a>

                <a href="{{ url('/operational-templates') }}"
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all card-hover"
                   style="border:1px solid #E9E9EB;">
                    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FFF1F0;">
                        <svg class="w-5 h-5" style="color:#E2231A;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-bold text-sm" style="color:#E2231A;">Template Operasional</p>
                    </div>
                </a>

                <a href="{{ url('/off-requests') }}"
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all card-hover"
                   style="border:1px solid #E9E9EB;">
                    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#FFFBEB;">
                        <svg class="w-5 h-5" style="color:#F59E0B;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-bold text-sm" style="color:#E2231A;">Off Request</p>
                    </div>
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="group flex items-center gap-4 p-4 rounded-xl transition-all card-hover"
                   style="border:1px solid #E9E9EB;">
                    <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background:#F6F6F7;">
                        <svg class="w-5 h-5" style="color:#62656F;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-bold text-sm" style="color:#E2231A;">Profil</p>
                    </div>
                </a>

            </div>
        </div>

    </div>
</x-app-layout>
