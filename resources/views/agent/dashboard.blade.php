<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="space-y-6">

            @if (!$agent)
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Akun kamu belum terhubung dengan data Agent. Hubungi PIC untuk menyelesaikan masalah ini.
                </div>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h3 class="page-title">
                            Selamat datang, {{ $agent->nama_agent }}!
                        </h3>
                        <p class="text-sm font-semibold mt-1" style="color:#82858E;">
                            Level {{ $agent->level }} &middot; Status: {{ ucfirst($agent->status) }}
                        </p>
                    </div>
                </div>

                @if ($formRequestWindow)
                    <a href="{{ route('agent.off-requests.index') }}"
                        x-data="{
                            closesAt: new Date('{{ $formRequestWindow->closes_at->toIso8601String() }}').getTime(),
                            hari: 0, jam: 0, menit: 0, sudahTutup: false,
                            tick() {
                                const diff = this.closesAt - Date.now();
                                if (diff <= 0) { this.sudahTutup = true; return; }
                                this.hari = Math.floor(diff / 86400000);
                                this.jam = Math.floor((diff % 86400000) / 3600000);
                                this.menit = Math.floor((diff % 3600000) / 60000);
                            }
                        }"
                        x-init="tick(); setInterval(() => tick(), 60000)"
                        class="block rounded-xl p-5 transition-all text-white card-hover relative overflow-hidden group"
                        style="background-color:#E2231A;box-shadow:0 10px 25px -5px rgba(226,35,26,0.4);"
                        onmouseover="this.style.background='#C01A12';"
                        onmouseout="this.style.background='#E2231A';">
                        
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 20px 20px;"></div>

                        <div class="relative flex items-center justify-between gap-4 z-10">
                            <div>
                                <p class="text-lg font-extrabold">Form Request sedang dibuka!</p>
                                <p class="text-xs mt-1" style="color:#FFD4D2;" x-show="sudahTutup">Sudah ditutup</p>
                                <div class="flex items-center gap-2 mt-3" x-show="!sudahTutup">
                                    <div class="text-center rounded-lg px-3 py-1.5 backdrop-blur-sm" style="background-color:rgba(255,255,255,0.15);">
                                        <p class="text-xl font-black leading-none" x-text="hari"></p>
                                        <p class="text-[10px] font-bold mt-1 uppercase tracking-wider" style="color:#FFD4D2;">Hari</p>
                                    </div>
                                    <div class="text-center rounded-lg px-3 py-1.5 backdrop-blur-sm" style="background-color:rgba(255,255,255,0.15);">
                                        <p class="text-xl font-black leading-none" x-text="jam"></p>
                                        <p class="text-[10px] font-bold mt-1 uppercase tracking-wider" style="color:#FFD4D2;">Jam</p>
                                    </div>
                                    <div class="text-center rounded-lg px-3 py-1.5 backdrop-blur-sm" style="background-color:rgba(255,255,255,0.15);">
                                        <p class="text-xl font-black leading-none" x-text="menit"></p>
                                        <p class="text-[10px] font-bold mt-1 uppercase tracking-wider" style="color:#FFD4D2;">Menit</p>
                                    </div>
                                </div>
                            </div>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition-transform group-hover:translate-x-1" style="background-color:rgba(255,255,255,0.2);">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endif

                {{-- Statistik --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="card p-5 card-hover">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#82858E;">Hari Kerja (Minggu)</p>
                        <p class="text-3xl font-extrabold" style="color:#E2231A;">{{ $hariKerjaMingguIni }}</p>
                    </div>
                    <div class="card p-5 card-hover">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#82858E;">Hari Kerja (Bulan)</p>
                        <p class="text-3xl font-extrabold" style="color:#1F2126;">{{ $hariKerjaBulanIni }}</p>
                    </div>
                    <div class="card p-5 card-hover">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#82858E;">Off Req. Pending</p>
                        <p class="text-3xl font-extrabold" style="color:#D97706;">{{ $offRequestPending }}</p>
                    </div>
                    <div class="card p-5 card-hover">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#82858E;">Off Req. Diterima</p>
                        <p class="text-3xl font-extrabold" style="color:#059669;">{{ $offRequestDiterima }}</p>
                    </div>
                </div>

                {{-- Jadwal Terdekat --}}
                <div class="card overflow-hidden mt-6">
                    <div class="px-6 py-4 border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                        <h4 class="text-sm font-bold" style="color:#1F2126;">Jadwal Terdekat</h4>
                    </div>

                    <div class="p-6">
                        @if ($jadwalTerdekat->isEmpty())
                            <div class="text-center py-6">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                    <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold" style="color:#62656F;">Tidak ada jadwal kerja untuk hari ini atau besok.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($jadwalTerdekat as $row)
                                    @php $isToday = \Carbon\Carbon::parse($row->tanggal)->isToday(); @endphp
                                    <div class="flex items-center justify-between px-5 py-4 rounded-xl border transition-colors"
                                         style="{{ $isToday ? 'background-color:#FFF1F0;border-color:#FFE0DD;' : 'background-color:#F6F6F7;border-color:#E9E9EB;' }}">
                                        <div>
                                            <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                <span class="mr-1 {{ $isToday ? 'text-[#E2231A]' : 'text-[#82858E]' }}">{{ $isToday ? 'Hari ini' : 'Besok' }}</span> 
                                                &middot; <span style="color:#3F4249;font-weight:600;">{{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d M Y') }}</span>
                                            </p>
                                            <p class="text-xs font-semibold mt-1" style="color:#62656F;">{{ $row->section->nama_section ?? '-' }}</p>
                                        </div>
                                        <div class="px-4 py-2 rounded-lg font-black text-lg"
                                             style="{{ $isToday ? 'background-color:#E2231A;color:#fff;' : 'background-color:#ffffff;color:#1F2126;border:1px solid #D2D3D6;' }}">
                                            {{ $row->shift->kode_shift ?? '-' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
