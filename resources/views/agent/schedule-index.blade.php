<x-app-layout>
    <x-slot name="header">Jadwal Saya</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="mb-6">                    <h1 class="page-title">Jadwal Saya</h1>
            </div>

            @if (!$agent)
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Akun kamu belum terhubung dengan data Agent. Hubungi PIC untuk menyelesaikan masalah ini.
                </div>

            @elseif (!$periodAktif && $periodsData->isEmpty())
                <div class="card p-12 text-center border-dashed">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                        <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold" style="color:#62656F;">Belum ada jadwal yang tercatat untuk kamu.</p>
                </div>

            @else

                {{-- ===== JADWAL AKTIF / TERDEKAT ===== --}}
                @if ($periodAktif)
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xs font-bold uppercase tracking-wider" style="color:#82858E;">
                                Jadwal Minggu Ini
                            </h2>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background-color:#F6F6F7;color:#3F4249;">
                                {{ \Carbon\Carbon::parse($periodAktif->tanggal_mulai)->translatedFormat('d M') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($periodAktif->tanggal_selesai)->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        <div class="card overflow-hidden divide-y" style="border-color:#E9E9EB;">
                            @foreach ($hariListAktif as $hari)
                                @php $isToday = $hari['tanggal_raw'] === $today; @endphp
                                <div class="flex items-center justify-between px-6 py-4 transition-colors"
                                    style="{{ $isToday ? 'background-color:#FFF1F0;' : 'background-color:#ffffff;' }}"
                                    onmouseover="if(this.style.backgroundColor!=='rgb(255, 241, 240)') this.style.background='#F6F6F7';"
                                    onmouseout="if(this.style.backgroundColor!=='rgb(255, 241, 240)') this.style.background='#ffffff';">
                                    <div class="flex items-center gap-4">
                                        @if ($isToday)
                                            <div class="shrink-0 w-2 h-2 rounded-full animate-pulse" style="background-color:#E2231A;"></div>
                                        @else
                                            <div class="shrink-0 w-2 h-2 rounded-full bg-transparent"></div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-bold" style="color:{{ $isToday ? '#C01A12' : '#1F2126' }};">
                                                {{ $hari['nama'] }}
                                                @if ($isToday)
                                                    <span class="ml-2 text-[10px] font-extrabold px-1.5 py-0.5 rounded uppercase tracking-wider"
                                                          style="background-color:#E2231A;color:#fff;">HARI INI</span>
                                                @endif
                                            </p>
                                            <p class="text-xs mt-0.5 font-semibold" style="color:#82858E;">{{ $hari['tanggal'] }}</p>
                                        </div>
                                    </div>

                                    @if ($hari['is_kerja'])
                                        <div class="text-right">
                                            <span class="text-base font-black" style="color:{{ $isToday ? '#E2231A' : '#1F2126' }};">{{ $hari['kode_shift'] }}</span>
                                            @if ($hari['jam_masuk'] && $hari['jam_pulang'])
                                                <p class="text-[10px] font-bold mt-0.5" style="color:#82858E;">
                                                    {{ \Carbon\Carbon::parse($hari['jam_masuk'])->format('H:i') }} – {{ \Carbon\Carbon::parse($hari['jam_pulang'])->format('H:i') }}
                                                </p>
                                            @endif
                                            @if ($hari['section'])
                                                <p class="text-[10px] font-semibold mt-0.5" style="color:#AEB0B6;">{{ $hari['section'] }}</p>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                                              style="background-color:#F6F6F7;color:#AEB0B6;">
                                            OFF
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ===== HISTORI JADWAL LAMA ===== --}}
                @if ($periodsData->isNotEmpty())
                    <div>
                        <h2 class="text-xs font-bold uppercase tracking-wider mb-4" style="color:#82858E;">
                            Riwayat Jadwal
                        </h2>

                        <div class="space-y-4">
                            @foreach ($periodsData as $item)
                                @php $period = $item['period']; @endphp
                                <div x-data="{ open: false }" class="card overflow-hidden">
                                    <button type="button" @click="open = !open"
                                        class="w-full flex items-center justify-between px-6 py-4 transition-colors"
                                        style="background-color:#FCFAF7;"
                                        onmouseover="this.style.background='#F6F6F7';"
                                        onmouseout="this.style.background='#FCFAF7';">
                                        <div class="text-left">
                                            <p class="text-sm font-bold" style="color:#1F2126;">{{ $period->nama_periode }}</p>
                                            <p class="text-xs mt-0.5 font-semibold" style="color:#82858E;">
                                                {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                                                &ndash;
                                                {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>
                                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''"
                                            style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" x-cloak class="border-t divide-y" style="border-color:#E9E9EB;">
                                        @foreach ($item['hariList'] as $hari)
                                            <div class="flex items-center justify-between px-6 py-3 transition-colors hover:bg-ink-50" style="background-color:#ffffff;">
                                                <div>
                                                    <p class="text-sm font-semibold" style="color:#3F4249;">{{ $hari['nama'] }}</p>
                                                    <p class="text-xs" style="color:#82858E;">{{ $hari['tanggal'] }}</p>
                                                </div>
                                                @if ($hari['is_kerja'])
                                                    <div class="text-right">
                                                        <span class="text-sm font-extrabold" style="color:#1F2126;">{{ $hari['kode_shift'] }}</span>
                                                        <p class="text-[10px]" style="color:#82858E;">{{ $hari['section'] }}</p>
                                                    </div>
                                                @else
                                                    <span class="text-xs font-bold" style="color:#AEB0B6;">OFF</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($periodsData->hasPages())
                            <div class="pt-4">
                                {{ $periodsData->links() }}
                            </div>
                        @endif
                    </div>
                @endif

            @endif
        </div>
    </div>
</x-app-layout>
