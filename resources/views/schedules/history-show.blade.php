<x-app-layout>
    <x-slot name="header">Detail Jadwal</x-slot>

    <div class="py-8 print:py-0 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 print:px-0 print:max-w-full">

            {{-- Navigasi (disembunyikan saat print) --}}
            <div class="flex flex-wrap items-center justify-between gap-2 print:hidden mb-2">
                <a href="{{ route('schedules.history') }}"
                    class="text-sm font-bold transition-colors inline-flex items-center gap-1.5"
                    style="color:#62656F;"
                    onmouseover="this.style.color='#1F2126';"
                    onmouseout="this.style.color='#62656F';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke History
                </a>

                @if ($hasResult)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('schedules.export-excel', $period) }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#10B981;box-shadow:0 4px 14px -2px rgba(16,185,129,0.35);"
                            onmouseover="this.style.background='#059669';"
                            onmouseout="this.style.background='#10B981';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            Download Excel
                        </a>
                        <button onclick="window.print()"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-colors"
                            style="background-color:#ffffff;color:#3F4249;border:1px solid #D2D3D6;"
                            onmouseover="this.style.background='#F6F6F7';"
                            onmouseout="this.style.background='#ffffff';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"></path>
                            </svg>
                            Cetak
                        </button>
                    </div>
                @endif
            </div>

            {{-- Info periode --}}
            <div class="card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 print:border-0 print:shadow-none print:p-0">
                <div>
                    <h3 class="page-title">{{ $period->nama_periode }}</h3>
                    <p class="text-sm mt-1" style="color:#82858E;">
                        {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider
                    {{ $period->status === 'final' ? 'bg-success-50 text-success-700' : 'bg-warning-50 text-gold-700' }}"
                    style="{{ $period->status === 'final' ? 'background-color:#ECFDF5;color:#047857;' : 'background-color:#FFFBEB;color:#D97706;' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $period->status === 'final' ? 'bg-success-500' : 'bg-warning-500' }}"></span>
                    {{ $period->status === 'final' ? 'FINAL' : 'DRAFT' }}
                </span>
            </div>

            @if (!$hasResult)
                <div class="card p-12 text-center border-dashed">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                        <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold" style="color:#62656F;">Periode ini belum memiliki hasil jadwal.</p>
                </div>
            @else
                {{-- Grid Mingguan --}}
                <div class="card overflow-hidden print:border-0 print:shadow-none">
                    <div class="px-6 py-4 border-b print:hidden" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                        <h3 class="text-sm font-bold" style="color:#1F2126;">Grid Mingguan</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr style="border-bottom:1px solid #E9E9EB;background-color:#F6F6F7;">
                                    <th class="px-5 py-3 sticky left-0 z-10" style="background-color:#F6F6F7;min-w:200px;">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Agent</span>
                                    </th>
                                    @foreach ($hariList as $hari)
                                        @php $isUnfulfilled = !empty($unfulfilledDays[$hari['index']]); @endphp
                                        <th class="px-5 py-3 text-center min-w-[110px]"
                                            style="{{ $isUnfulfilled ? 'background-color:#FEE2E2;' : '' }}">
                                            <span class="text-xs font-bold" style="{{ $isUnfulfilled ? 'color:#B91C1C;' : 'color:#3F4249;' }}">{{ ucfirst($hari['nama']) }}</span>
                                            <div class="text-[10px]" style="{{ $isUnfulfilled ? 'color:#DC2626;' : 'color:#82858E;' }}">{{ $hari['label'] }}</div>
                                            @if($isUnfulfilled)
                                                <div class="text-[9px] font-bold mt-1 uppercase" style="color:#E2231A; padding:2px 4px; background:#FFE0DD; border-radius:4px; display:inline-block;">Tidak Terpenuhi</div>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color:#E9E9EB;">
                                @foreach ($agents as $agent)
                                    <tr class="transition-colors hover:bg-ink-50"
                                        style="background-color:#ffffff;"
                                        onmouseover="this.style.background='#F6F6F7';"
                                        onmouseout="this.style.background='#ffffff';">

                                        <td class="px-5 py-3 sticky left-0 z-10"
                                            style="background-color:#ffffff;">
                                            <div class="font-bold text-sm" style="color:#1F2126;">{{ $agent->nama_agent }}</div>
                                            <div class="text-xs mt-0.5" style="color:#82858E;">Level {{ $agent->level }}</div>
                                        </td>

                                        @foreach ($hariList as $hari)
                                            @php $cell = $grid[$agent->id][$hari['index']] ?? null; @endphp
                                            @php $isUnfulfilled = !empty($unfulfilledDays[$hari['index']]); @endphp
                                            <td class="text-center px-5 py-3" style="{{ $isUnfulfilled ? 'background-color:#FEF2F2;' : '' }}">
                                                @if ($cell && $cell->status === 'kerja')
                                                    <span class="inline-flex flex-col items-center justify-center p-2 rounded-lg"
                                                          style="background-color:#FCFAF7;border:1px solid #E9E9EB;width:100%;">
                                                        <span class="font-extrabold text-sm" style="color:#E2231A;">{{ $cell->shift->kode_shift ?? '-' }}</span>
                                                        <span class="text-[10px] font-semibold mt-0.5" style="color:#62656F;">{{ $cell->section->nama_section ?? '-' }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-xs font-bold" style="{{ $isUnfulfilled ? 'color:#FCA5A5;' : 'color:#AEB0B6;' }}">OFF</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Ringkasan Operasional --}}
                <div class="card overflow-hidden print:hidden">
                    <div class="px-6 py-3 flex items-center gap-2 border-b" style="border-color:#E9E9EB; background-color:#FCFAF7;">
                        <svg class="w-4 h-4" fill="none" stroke="#E2231A" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span class="text-xs font-bold uppercase tracking-wider" style="color:#E2231A;">Ringkasan Operasional</span>
                        <span class="text-xs ml-2" style="color:#82858E;">(Terisi / Target)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr style="background-color:#F6F6F7; border-bottom:1px solid #E9E9EB;">
                                    <th class="px-4 py-2 sticky left-0 z-10" style="background-color:#F6F6F7; min-width:200px;">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Section / Shift</span>
                                    </th>
                                    @foreach ($hariList as $hari)
                                        @php $isUnfulfilled = !empty($unfulfilledDays[$hari['index']]); @endphp
                                        <th class="px-4 py-2 text-center min-w-[90px]"
                                            style="{{ $isUnfulfilled ? 'background-color:#FEE2E2;' : '' }}">
                                            <span class="font-bold" style="{{ $isUnfulfilled ? 'color:#B91C1C;' : 'color:#62656F;' }}">{{ ucfirst($hari['nama']) }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summaryGrid as $sectionName => $shiftRows)
                                    <tr style="background-color:#FFF8F7; border-top:2px solid #E9E9EB;">
                                        <td colspan="{{ count($hariList) + 1 }}" class="px-4 py-1.5 sticky left-0">
                                            <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color:#E2231A;">{{ $sectionName }}</span>
                                        </td>
                                    </tr>
                                    @foreach ($shiftRows as $shiftCode => $days)
                                        @php $firstDay = array_values($days)[0] ?? []; @endphp
                                        <tr class="border-b" style="border-color:#F0F0F0;"
                                            onmouseover="this.style.background='#FAFAFA'"
                                            onmouseout="this.style.background='#ffffff'">
                                            <td class="px-4 py-2 sticky left-0" style="background-color:inherit;">
                                                <span class="font-semibold" style="color:#3F4249;">{{ $firstDay['shift_detail'] ?? $shiftCode }}</span>
                                            </td>
                                            @foreach ($hariList as $hariSel)
                                                @php
                                                    $d = $days[$hariSel['index']] ?? ['terisi' => 0, 'kebutuhan' => 0];
                                                    $isUnfulfilled = !empty($unfulfilledDays[$hariSel['index']]);
                                                    $isCellShort = $d['terisi'] < $d['kebutuhan'] && $d['kebutuhan'] > 0;
                                                @endphp
                                                <td class="px-4 py-2 text-center"
                                                    style="{{ $isUnfulfilled ? 'background-color:#FEF2F2;' : '' }}">
                                                    @if ($d['kebutuhan'] > 0 || $d['terisi'] > 0)
                                                        <span class="font-bold text-xs" style="{{ $isCellShort ? 'color:#DC2626;' : 'color:#16A34A;' }}">{{ $d['terisi'] }}</span>
                                                        <span class="text-[10px]" style="color:#AEB0B6;">/</span>
                                                        <span class="text-[10px] font-semibold" style="color:#62656F;">{{ $d['kebutuhan'] }}</span>
                                                    @else
                                                        <span style="color:#D1D5DB;">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
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
                                            <span class="text-[10px] font-extrabold uppercase" style="color:#0369A1;">Total {{ $sectionName }}</span>
                                        </td>
                                        @foreach ($hariList as $hariTot)
                                            @php $st = $secTotals[$hariTot['index']]; @endphp
                                            <td class="px-4 py-1.5 text-center">
                                                <span class="font-extrabold text-xs" style="{{ $st['terisi'] < $st['kebutuhan'] ? 'color:#DC2626;' : 'color:#0369A1;' }}">
                                                    {{ $st['terisi'] }}/{{ $st['kebutuhan'] }}
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Detail per Hari --}}
                <div x-data="{ activeDay: 0 }" class="card p-6 print:hidden">
                    <h3 class="text-lg font-bold mb-5" style="color:#1F2126;">Detail per Hari</h3>

                    <div class="flex gap-2 overflow-x-auto pb-4 mb-4 scrollbar-thin">
                        @foreach ($hariList as $hari)
                            <button @click="activeDay = {{ $hari['index'] }}"
                                :class="activeDay === {{ $hari['index'] }} ? 'bg-brand-500 text-white shadow-md' : 'bg-ink-100 text-ink-600 hover:bg-ink-200'"
                                class="px-5 py-2.5 rounded-lg text-sm font-bold whitespace-nowrap transition-all"
                                x-bind:style="activeDay === {{ $hari['index'] }} ? 'background-color:#E2231A;color:#fff;' : 'background-color:#F6F6F7;color:#62656F;'"
                                style="{{ $loop->first ? 'background-color:#E2231A;color:#fff;' : 'background-color:#F6F6F7;color:#62656F;' }}">
                                {{ ucfirst($hari['nama']) }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($hariList as $hari)
                        <div x-show="activeDay === {{ $hari['index'] }}">
                            <h4 class="text-sm font-bold mb-4" style="color:#1F2126;">
                                {{ ucfirst($hari['nama']) }}, {{ $hari['label'] }}
                            </h4>

                            @if ($perHari[$hari['index']]->isEmpty())
                                <p class="text-sm font-semibold text-center py-6" style="color:#82858E;font-style:italic;">Tidak ada penugasan di hari ini.</p>
                            @else
                                <div class="overflow-x-auto rounded-xl border" style="border-color:#E9E9EB;">
                                    <table class="w-full text-left text-sm border-collapse">
                                        <thead>
                                            <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                                <th class="px-5 py-3">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Shift</span>
                                                </th>
                                                <th class="px-5 py-3">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Section</span>
                                                </th>
                                                <th class="px-5 py-3">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Agent</span>
                                                </th>
                                                <th class="px-5 py-3">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Level</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color:#E9E9EB;">
                                            @foreach ($perHari[$hari['index']] as $row)
                                                <tr class="transition-colors hover:bg-ink-50" style="background-color:#ffffff;"
                                                    onmouseover="this.style.background='#F6F6F7';"
                                                    onmouseout="this.style.background='#ffffff';">
                                                    <td class="px-5 py-3 font-extrabold" style="color:#E2231A;">{{ $row->shift->kode_shift ?? '-' }}</td>
                                                    <td class="px-5 py-3 font-semibold" style="color:#3F4249;">{{ $row->section->nama_section ?? '-' }}</td>
                                                    <td class="px-5 py-3 font-bold" style="color:#1F2126;">{{ $row->agent->nama_agent ?? '-' }}</td>
                                                    <td class="px-5 py-3 text-sm" style="color:#62656F;">Level {{ $row->agent->level ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
