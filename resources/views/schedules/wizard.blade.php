<x-app-layout>
    <x-slot name="header">Generate Jadwal</x-slot>

    @php
        // Warna identitas per section (dipakai sebagai warna border baris kebutuhan operasional).
        // Dihitung di sini supaya @json hanya menerima variabel sederhana (tanpa koma).
        $sectionColorsData = collect(['Chat', 'Website', 'Informasi', 'Inbound'])
            ->mapWithKeys(fn ($s) => [$s => \App\Models\Section::warna($s)])
            ->all();
    @endphp

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="mb-8">
                <h1 class="page-title">Wizard Generate Jadwal</h1>
            </div>

            @if (session('info'))
                <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                     style="background-color:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('info') }}
                </div>
            @endif

            @if (session('error'))
                <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                     style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FEE2E2;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Indikator step --}}
            <div class="flex items-center gap-3 text-sm mb-6">
                <div class="flex items-center gap-2 {{ $period ? 'font-bold' : 'font-extrabold' }}"
                     style="color:{{ $period ? '#059669' : '#E2231A' }};">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                        style="{{ $period ? 'background-color:#D1FAE5;color:#047857;' : 'background-color:#E2231A;color:#ffffff;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);' }}">1</span>
                    <span class="hidden sm:inline">Buat Periode</span>
                </div>
                <div class="flex-1 h-px" style="background-color:#E9E9EB;"></div>
                <div class="flex items-center gap-2 {{ $period ? 'font-extrabold' : 'font-semibold' }}"
                     style="color:{{ $period ? '#E2231A' : '#AEB0B6' }};">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                        style="{{ $period ? 'background-color:#E2231A;color:#ffffff;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);' : 'background-color:#F6F6F7;color:#AEB0B6;' }}">2</span>
                    <span class="hidden sm:inline">Kebutuhan Operasional</span>
                </div>
                <div class="flex-1 h-px" style="background-color:#E9E9EB;"></div>
                <div class="flex items-center gap-2 font-semibold" style="color:#AEB0B6;">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                          style="background-color:#F6F6F7;color:#AEB0B6;">3</span>
                    <span class="hidden sm:inline">Generate &amp; Review</span>
                </div>
            </div>

            @if (!$period)
                {{-- ==================== STEP 1 ==================== --}}
                <div class="card p-8">
                    <h3 class="text-lg font-bold mb-1" style="color:#1F2126;">Buat Periode Jadwal</h3>

                    <form action="{{ route('schedules.periods.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Nama Periode</label>
                            <input type="text" name="nama_periode" id="nama_periode" required class="input-field" placeholder="Misal: Minggu Ke-3 Juni 2024">
                            @error('nama_periode')
                                <p class="text-xs mt-1 font-semibold" style="color:#DC2626;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Tanggal Mulai (Senin)</label>
                            <input type="date" name="tanggal_mulai" required class="input-field" />
                            @error('tanggal_mulai')
                                <p class="text-xs mt-1 font-semibold" style="color:#DC2626;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg text-sm font-bold text-white transition-all"
                                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                onmouseover="this.style.background='#C01A12';"
                                onmouseout="this.style.background='#E2231A';">
                                Lanjut ke Kebutuhan Operasional &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            @else
                {{-- ==================== STEP 2 ==================== --}}
                <div x-data="kebutuhanWizard()" x-init="init()">

                    {{-- Toast Notification --}}
                    <div x-show="toastMsg"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold shadow-lg"
                         :style="toastType === 'success' ? 'background:#16A34A;color:#fff;' : 'background:#DC2626;color:#fff;'"
                         style="display:none;">
                        <span x-text="toastMsg"></span>
                    </div>
                    <div class="card p-6 mb-5 flex flex-col md:flex-row md:items-center justify-between gap-4" style="background-color:#FCFAF7;border:1px solid #E9E9EB;">
                        <div>
                            <h3 class="text-lg font-bold" style="color:#1F2126;">{{ $period->nama_periode }}</h3>
                            <p class="text-sm font-semibold mt-1" style="color:#82858E;">
                                {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                            </p>
                        </div>
                    </div>

                    {{-- Tab per hari --}}
                    <div class="grid grid-cols-7 gap-2 mb-5 overflow-x-auto pb-2 scrollbar-thin">
                        @foreach ($hariList as $hari)
                            @php $hariAlreadyFilled = in_array($hari['nama'], $filledDays); @endphp
                            <button type="button" @click="activeDay = {{ $hari['index'] }}"
                                class="px-5 py-2.5 rounded-lg text-sm font-bold whitespace-nowrap transition-all flex items-center justify-center gap-2 border"
                                :style="tabStyle({{ $hari['index'] }})"
                                style="{{ $loop->first ? 'background-color:#E2231A;color:#fff;border-color:#E2231A;' : 'background-color:#ffffff;color:#62656F;border-color:#D2D3D6;' }}">
                                {{ \Carbon\Carbon::parse($hari['tanggal'])->translatedFormat('D, d M') }}
                                <span x-show="isDaySaved({{ $hari['index'] }})"
                                      class="inline-flex items-center shrink-0"
                                      style="{{ $hariAlreadyFilled ? '' : 'display:none;' }}">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :style="activeDay === {{ $hari['index'] }} ? 'background-color:#ffffff;' : 'background-color:#10B981;'"
                                          style="{{ $hariAlreadyFilled ? ($loop->first ? 'background-color:#ffffff;' : 'background-color:#10B981;') : '' }}"></span>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Konten per hari --}}
                    @foreach ($hariList as $hari)
                        <div x-show="activeDay === {{ $hari['index'] }}" class="card p-6 min-h-[400px]">
                            <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 pb-4 border-b gap-4" style="border-color:#E9E9EB;">
                                <h4 class="font-extrabold text-lg" style="color:#1F2126;">{{ $hari['label'] }}</h4>
                                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                                    <select x-model="selectedTemplate[{{ $hari['index'] }}]"
                                            @change="markDirty({{ $hari['index'] }})"
                                            class="input-field py-2 text-sm w-full sm:w-auto">
                                        <option value="">— Pilih Template —</option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}">{{ $template->nama_template }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="applyTemplate({{ $hari['index'] }})"
                                        class="px-4 py-2 rounded-lg text-sm font-bold transition-colors w-full sm:w-auto whitespace-nowrap"
                                        :style="applied[{{ $hari['index'] }}]
                                            ? 'background-color:#16A34A;color:#fff;border:1px solid #16A34A;'
                                            : 'background-color:#F6F6F7;color:#3F4249;border:1px solid #D2D3D6;'">
                                        Terapkan
                                    </button>
                                </div>
                            </div>

                            <div class="mb-6">
                                <div class="overflow-x-auto rounded-xl border" style="border-color:#E9E9EB;min-height:180px;">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr style="background-color:#FCFAF7;border-bottom:1px solid #E9E9EB;">
                                                <th class="px-4 py-2.5">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Shift</span>
                                                </th>
                                                <th class="px-4 py-2.5">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Section</span>
                                                </th>
                                                <th class="px-4 py-2.5 w-28">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Jumlah</span>
                                                </th>
                                                <th class="px-4 py-2.5 w-14 text-right">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color:#F0F0F0;">
                                            <template x-for="(row, index) in rows[{{ $hari['index'] }}]" :key="index">
                                                <tr class="transition-colors animate-fade-in"
                                                    :style="'background-color:#ffffff;border-left:4px solid ' + (row.section_id ? sectionWarna(row.section_id).accent : 'transparent') + ';'"
                                                    style="background-color:#ffffff;"
                                                    onmouseover="this.style.background='#FCFAF7';"
                                                    onmouseout="this.style.background='#ffffff';">
                                                    <td class="px-4 py-2">
                                                        <select x-model="row.shift_id"
                                                            @change="row.shift_id = $event.target.value; sortRows({{ $hari['index'] }}); markDirty({{ $hari['index'] }})"
                                                            class="w-full rounded-lg border px-2.5 py-2 text-sm outline-none bg-white transition-all"
                                                            style="min-width:160px; border-color:#E9E9EB;"
                                                            onfocus="this.style.borderColor='#E2231A'"
                                                            onblur="this.style.borderColor='#E9E9EB'">
                                                            <option value="">Pilih Shift</option>
                                                            @foreach ($shifts as $shift)
                                                                <option value="{{ $shift->id }}">{{ $shift->kode_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }})</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <select x-model="row.section_id"
                                                            @change="row.section_id = $event.target.value; sortRows({{ $hari['index'] }}); markDirty({{ $hari['index'] }})"
                                                            class="w-full rounded-lg border px-2.5 py-2 text-sm outline-none bg-white transition-all"
                                                            style="min-width:140px; border-color:#E9E9EB;"
                                                            onfocus="this.style.borderColor='#E2231A'"
                                                            onblur="this.style.borderColor='#E9E9EB'">
                                                            <option value="">Pilih Section</option>
                                                            @foreach ($sections as $section)
                                                                <option value="{{ $section->id }}">{{ $section->nama_section }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="number" x-model="row.jumlah_kebutuhan" min="1" placeholder="Jml"
                                                            @input="markDirty({{ $hari['index'] }})"
                                                            class="w-full rounded-lg border px-2 py-2 text-sm outline-none bg-white text-center"
                                                            style="border-color:#E9E9EB;"
                                                            onfocus="this.style.borderColor='#E2231A'"
                                                            onblur="this.style.borderColor='#E9E9EB'">
                                                    </td>
                                                    <td class="px-4 py-2 text-right">
                                                        <button type="button" @click="removeRow({{ $hari['index'] }}, index)"
                                                            class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors ml-auto"
                                                            style="color:#AEB0B6;background-color:#ffffff;border:1px solid #D2D3D6;"
                                                            onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#FCA5A5';"
                                                            onmouseout="this.style.background='#ffffff';this.style.color='#AEB0B6';this.style.borderColor='#D2D3D6';"
                                                            title="Hapus baris">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                            </div>

                            <div class="flex justify-between items-center border-t pt-4" style="border-color:#E9E9EB;">
                                <button type="button" @click="addRow({{ $hari['index'] }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                                    style="background-color:#FFF1F0;color:#E2231A;"
                                    onmouseover="this.style.background='#FFE0DD';"
                                    onmouseout="this.style.background='#FFF1F0';">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Tambah Baris
                                </button>
                                <button type="button" @click="saveDay({{ $hari['index'] }})"
                                    :disabled="saving"
                                    class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all flex items-center gap-2"
                                    :style="saving
                                        ? 'background-color:#16A34A;cursor:not-allowed;'
                                        : (isDaySaved({{ $hari['index'] }}) ? 'background-color:#16A34A;' : 'background-color:#E2231A;')"
                                    style="background-color:#E2231A;">
                                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                    <span x-text="saving ? 'Menyimpan...' : (isDayFilled({{ $hari['index'] }}) ? 'Simpan Ulang' : 'Simpan Hari Ini')"></span>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <div class="card p-6 mt-6 flex flex-col md:flex-row md:items-center justify-between gap-4" style="background-color:#F6F6F7;">
                         <p class="text-sm font-semibold" :class="allDaysFilled() ? 'text-success-700' : 'text-gold-700'">
                            <span class="font-extrabold text-lg" x-text="filledCount()"></span> dari 7 hari sudah diisi.
                            <br class="md:hidden">
                            <span x-show="!allDaysFilled()" class="text-xs font-normal" style="color:#82858E;">Lengkapi semua hari sebelum generate.</span>
                        </p>
                    </div>

                    {{-- Ringkasan Grid Operasional (Reaktif) --}}
                    <div class="card overflow-hidden mt-5">
                        <div class="px-6 py-3 flex items-center gap-2 border-b" style="border-color:#E9E9EB; background-color:#FCFAF7;">
                            <svg class="w-4 h-4" fill="none" stroke="#E2231A" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span class="text-xs font-bold uppercase tracking-wider" style="color:#E2231A;">Ringkasan Kebutuhan Per Hari</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr style="background-color:#F6F6F7; border-bottom:1px solid #E9E9EB;">
                                        <th class="px-4 py-2 sticky left-0 z-10" style="background-color:#F6F6F7; min-width:200px;">
                                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Section / Shift</span>
                                        </th>
                                        @foreach ($hariList as $hari)
                                            <th class="px-4 py-2 text-center min-w-[90px]">
                                                <span class="font-bold" style="color:#62656F;">{{ Carbon\Carbon::parse($hari['tanggal'])->translatedFormat('D') }}</span>
                                                <div class="text-[10px]" style="color:#AEB0B6;">{{ Carbon\Carbon::parse($hari['tanggal'])->format('d/m') }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $sectionOrder = ['Chat', 'Website', 'Informasi', 'Inbound'];
                                        $wizSections = $sections;
                                        $wizShifts = $shifts;
                                    @endphp
                                    @foreach ($wizSections as $sec)
                                        <tr style="background-color:#FFF8F7; border-top:2px solid #E9E9EB;">
                                            <td colspan="{{ count($hariList) + 1 }}" class="px-4 py-1.5">
                                                <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color:#E2231A;">{{ $sec->nama_section }}</span>
                                            </td>
                                        </tr>
                                        @foreach ($wizShifts as $shf)
                                            <tr class="border-b" style="border-color:#F0F0F0;"
                                                onmouseover="this.style.background='#FAFAFA'"
                                                onmouseout="this.style.background='#ffffff'">
                                                <td class="px-4 py-2 sticky left-0 font-semibold" style="color:#3F4249; background-color:inherit;">
                                                    {{ $shf->kode_shift }} [{{ Carbon\Carbon::parse($shf->jam_masuk)->format('H:i') }}-{{ Carbon\Carbon::parse($shf->jam_pulang)->format('H:i') }}]
                                                </td>
                                                @foreach ($hariList as $hari)
                                                    <td class="px-4 py-2 text-center"
                                                        x-text="(rows[{{ $hari['index'] }}] || []).filter(r => r.shift_id == '{{ $shf->id }}' && r.section_id == '{{ $sec->id }}').reduce((s, r) => s + (parseInt(r.jumlah_kebutuhan) || 0), 0) || '-'">
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        {{-- Total per section --}}
                                        <tr style="background-color:#F0F9FF; border-top:1px solid #BAE6FD;">
                                            <td class="px-4 py-1.5 sticky left-0 font-extrabold text-[10px] uppercase" style="color:#0369A1; background-color:#F0F9FF;">
                                                Total {{ $sec->nama_section }}
                                            </td>
                                            @foreach ($hariList as $hari)
                                                <td class="px-4 py-1.5 text-center font-extrabold text-xs" style="color:#0369A1;"
                                                    x-text="(rows[{{ $hari['index'] }}] || []).filter(r => r.section_id == '{{ $sec->id }}').reduce((s, r) => s + (parseInt(r.jumlah_kebutuhan) || 0), 0)">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ==================== PANEL PENUGASAN INBOUND ==================== --}}
                    <div id="inbound-panel" class="card p-6 mt-6"
                         x-data="inboundToggle()"
                         x-init="loadData()">

                        <div class="flex items-center gap-2 mb-5 pb-4 border-b" style="border-color:#E9E9EB;">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color:#EEF2FF;">
                                <svg class="w-4 h-4" fill="none" stroke="#4F46E5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-base" style="color:#1F2126;">Penugasan Inbound</h4>
                            </div>
                        </div>

                        {{-- Kartu Estimasi --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                            <div class="p-3 rounded-xl text-center" style="background-color:#F0F9FF;border:1px solid #BAE6FD;">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#0369A1;">Slot Inbound</p>
                                <p class="text-2xl font-extrabold" style="color:#0369A1;" x-text="estimasi.total_slot ?? '...'"></p>
                                <p class="text-[10px]" style="color:#7DD3FC;">per periode</p>
                            </div>
                            <div class="p-3 rounded-xl text-center" style="background-color:#FFF1F0;border:1px solid #FFE0DD;">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#E2231A;">Toggle ON (L2+L3)</p>
                                <p class="text-2xl font-extrabold" style="color:#E2231A;" x-text="toggleOnCount()"></p>
                                <p class="text-[10px]" style="color:#FCA5A5;" x-text="toggleOnCount() > (estimasi.total_slot ?? 0) ? 'melebihi slot Inbound!' : 'slot untuk SOP'"></p>
                            </div>
                            <div class="p-3 rounded-xl text-center" :style="slotUntukLevel1() < 0 ? 'background-color:#FEF2F2;border:1px solid #FCA5A5;' : 'background-color:#F0FDF4;border:1px solid #BBF7D0;'">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" :style="slotUntukLevel1() < 0 ? 'color:#DC2626;' : 'color:#16A34A;'">Sisa untuk Level 1</p>
                                <p class="text-2xl font-extrabold" :style="slotUntukLevel1() < 0 ? 'color:#DC2626;' : 'color:#16A34A;'" x-text="slotUntukLevel1()"></p>
                                <p class="text-[10px]" :style="slotUntukLevel1() < 0 ? 'color:#FCA5A5;' : 'color:#86EFAC;'" x-text="estimasi.jumlah_level1 ? '(' + estimasi.jumlah_level1 + ' agent)' : ''"></p>
                            </div>
                            <div class="p-3 rounded-xl text-center" :style="rataRataLevel1() < 2 ? 'background-color:#FEF2F2;border:1px solid #FCA5A5;' : 'background-color:#F6F6F7;border:1px solid #E9E9EB;'">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" :style="rataRataLevel1() < 2 ? 'color:#DC2626;' : 'color:#6B7280;'">Rata-rata Level 1</p>
                                <p class="text-2xl font-extrabold" :style="rataRataLevel1() < 2 ? 'color:#DC2626;' : 'color:#1F2126;'" x-text="rataRataLevel1() + ' hari'"></p>
                                <p class="text-[10px]" style="color:#AEB0B6;">per periode</p>
                            </div>
                        </div>

                        {{-- Warning rata-rata Level 1 terlalu rendah --}}
                        <div x-show="rataRataLevel1() < 2 && estimasi.jumlah_level1 > 0"
                             class="flex items-start gap-3 p-3 rounded-xl text-sm font-semibold mb-4"
                             style="background-color:#FEF2F2;color:#DC2626;border:1px solid #FCA5A5;display:none;">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Terlalu banyak agent yang di-toggle ON. Agent Level 1 diperkirakan mendapat kurang dari 2 hari kerja per periode.</span>
                        </div>

                        {{-- Warning SC6: agent eligible inbound melebihi slot (mustahil semua dapat 1x) --}}
                        <div x-show="toggleOnCount() > (estimasi.total_slot ?? 0)"
                             class="flex items-start gap-3 p-3 rounded-xl text-sm font-semibold mb-4"
                             style="background-color:#FEF2F2;color:#DC2626;border:1px solid #FCA5A5;display:none;">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>
                                Kelebihan <b x-text="toggleOnCount() - (estimasi.total_slot ?? 0)"></b> agent eligible:
                                hanya <b x-text="estimasi.total_slot ?? 0"></b> slot Inbound untuk
                                <b x-text="toggleOnCount()"></b> agent toggle ON.
                                Minimal <b x-text="toggleOnCount() - (estimasi.total_slot ?? 0)"></b> agent pasti tidak mendapat 1x Inbound.
                            </span>
                        </div>

                        {{-- Loading state --}}
                        <div x-show="loading" class="py-8 text-center text-sm" style="color:#82858E;">
                            <svg class="w-5 h-5 animate-spin mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Memuat data agent...
                        </div>

                        <div x-show="!loading">
                            {{-- Level 2 --}}
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#6B7280;">Level 2 — Toggle Inbound</p>
                                    <div class="flex gap-2">
                                        <button type="button" @click="selectAll(2)"
                                            class="text-xs px-2.5 py-1 rounded-lg font-bold transition-colors"
                                            style="background-color:#F0F9FF;color:#0369A1;border:1px solid #BAE6FD;">
                                            Pilih Semua
                                        </button>
                                        <button type="button" @click="deselectAll(2)"
                                            class="text-xs px-2.5 py-1 rounded-lg font-bold transition-colors"
                                            style="background-color:#F6F6F7;color:#6B7280;border:1px solid #E5E7EB;">
                                            Hapus Semua
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="agent in agentsLevel2" :key="agent.id">
                                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                               :style="agent.toggle_on ? 'background-color:#FFF1F0;border-color:#FFE0DD;' : 'background-color:#ffffff;border-color:#E9E9EB;'">
                                            {{-- Toggle switch --}}
                                            <div class="relative w-9 h-5 rounded-full flex-shrink-0 transition-colors duration-200"
                                                 :style="agent.toggle_on ? 'background-color:#16A34A;' : 'background-color:#D1D5DB;'"
                                                 @click="toggleAgent(2, agent.id)">
                                                <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                                      :class="agent.toggle_on ? 'translate-x-4' : 'translate-x-0'"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold truncate" :style="agent.toggle_on ? 'color:#1F2126;' : 'color:#6B7280;'" x-text="agent.nama_agent"></p>
                                                <p class="text-[10px] mt-0.5" :style="agent.sudah_dapat ? 'color:#059669;' : 'color:#AEB0B6;'"
                                                   x-text="agent.sudah_dapat ? 'Sudah dapat: ' + agent.nama_periode_dapat : 'Belum dapat Inbound bulan ini'"></p>
                                            </div>
                                            <span x-show="agent.toggle_on" class="text-[10px] px-1.5 py-0.5 rounded-full font-bold flex-shrink-0"
                                                  style="background-color:#DCFCE7;color:#16A34A;">ON</span>
                                        </label>
                                    </template>
                                    <p x-show="agentsLevel2.length === 0" class="text-sm col-span-2" style="color:#AEB0B6;">Tidak ada agent Level 2 aktif.</p>
                                </div>
                            </div>

                            {{-- Level 3 --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#6B7280;">Level 3 — Toggle Inbound</p>
                                    <div class="flex gap-2">
                                        <button type="button" @click="selectAll(3)"
                                            class="text-xs px-2.5 py-1 rounded-lg font-bold transition-colors"
                                            style="background-color:#F0F9FF;color:#0369A1;border:1px solid #BAE6FD;">
                                            Pilih Semua
                                        </button>
                                        <button type="button" @click="deselectAll(3)"
                                            class="text-xs px-2.5 py-1 rounded-lg font-bold transition-colors"
                                            style="background-color:#F6F6F7;color:#6B7280;border:1px solid #E5E7EB;">
                                            Hapus Semua
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="agent in agentsLevel3" :key="agent.id">
                                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                               :style="agent.toggle_on ? 'background-color:#FFF1F0;border-color:#FFE0DD;' : 'background-color:#ffffff;border-color:#E9E9EB;'">
                                            <div class="relative w-9 h-5 rounded-full flex-shrink-0 transition-colors duration-200"
                                                 :style="agent.toggle_on ? 'background-color:#16A34A;' : 'background-color:#D1D5DB;'"
                                                 @click="toggleAgent(3, agent.id)">
                                                <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                                                      :class="agent.toggle_on ? 'translate-x-4' : 'translate-x-0'"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold truncate" :style="agent.toggle_on ? 'color:#1F2126;' : 'color:#6B7280;'" x-text="agent.nama_agent"></p>
                                                <p class="text-[10px] mt-0.5" :style="agent.sudah_dapat ? 'color:#059669;' : 'color:#AEB0B6;'"
                                                   x-text="agent.sudah_dapat ? 'Sudah dapat: ' + agent.nama_periode_dapat : 'Belum dapat Inbound bulan ini'"></p>
                                            </div>
                                            <span x-show="agent.toggle_on" class="text-[10px] px-1.5 py-0.5 rounded-full font-bold flex-shrink-0"
                                                  style="background-color:#DCFCE7;color:#16A34A;">ON</span>
                                        </label>
                                    </template>
                                    <p x-show="agentsLevel3.length === 0" class="text-sm col-span-2" style="color:#AEB0B6;">Tidak ada agent Level 3 aktif.</p>
                                </div>
                            </div>

                            {{-- Status Auto-save --}}
                            <div class="flex items-center justify-between pt-4 mt-4 border-t" style="border-color:#E9E9EB;">
                                <p class="text-xs font-semibold" style="color:#82858E;">
                                    Perubahan toggle tersimpan otomatis.
                                </p>
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full"
                                      :style="saving ? 'background-color:#F0FDF4;color:#16A34A;' : 'background-color:#ECFDF5;color:#059669;'">
                                    <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="saving ? 'Menyimpan...' : 'Tersimpan otomatis'"></span>
                                </span>
                            </div>
                        </div>

                        {{-- ==================== POP-UP KONFIRMASI INBOUND SOP ==================== --}}
                        <div x-show="showPopup"
                             x-cloak
                             @keydown.escape.window="showPopup = false"
                             class="fixed inset-0 z-50 flex items-center justify-center p-4"
                             style="display:none;">

                            {{-- Backdrop blur --}}
                            <div @click="showPopup = false"
                                 class="fixed inset-0 transition-opacity"
                                 style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0">
                            </div>

                            {{-- Dialog --}}
                            <div class="relative bg-white rounded-2xl shadow-2xl w-full z-10"
                                 :style="agentBelumToggleOn.length > 6 ? 'max-width:42rem;' : agentBelumToggleOn.length > 3 ? 'max-width:32rem;' : 'max-width:28rem;'"
                                 x-transition:enter="ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="ease-in duration-200"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.stop>

                                {{-- Header pop-up --}}
                                <div class="px-6 pt-6 pb-4 border-b" style="border-color:#E9E9EB;">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                             :style="isPeriodeTerakhir ? 'background-color:#FEF2F2;' : 'background-color:#FFFBEB;'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                                 :style="isPeriodeTerakhir ? 'color:#DC2626;' : 'color:#D97706;'">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-extrabold text-base" style="color:#1F2126;"
                                                x-text="isPeriodeTerakhir ? 'Peringatan: Periode Terakhir Bulan Ini' : 'Beberapa Agent Belum Mendapat Inbound'"></h3>
                                            <p class="text-xs mt-0.5" style="color:#82858E;" x-text="bulanLabel"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Body pop-up --}}
                                <div class="px-6 py-4">
                                    <p class="text-sm mb-3" style="color:#3F4249;">
                                        <span x-text="agentBelumToggleOn.length"></span> agent berikut belum mendapat Inbound bulan ini
                                        <span x-show="isPeriodeTerakhir" class="font-bold" style="color:#DC2626;"> dan toggle-nya masih OFF, padahal ini adalah periode terakhir bulan ini</span>
                                        <span x-show="!isPeriodeTerakhir" style="color:#6B7280;"> (toggle masih OFF)</span>:
                                    </p>
                                    <div class="max-h-52 overflow-y-auto mb-4 pr-1"
                                         :style="agentBelumToggleOn.length > 6
                                             ? 'display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;'
                                             : agentBelumToggleOn.length > 3
                                                 ? 'display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px;'
                                                 : 'display:flex;flex-direction:column;gap:6px;'">
                                        <template x-for="agent in agentBelumToggleOn" :key="agent.id">
                                            <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg"
                                                 :style="isPeriodeTerakhir ? 'background-color:#FEF2F2;' : 'background-color:#FFFBEB;'">
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded flex-shrink-0"
                                                      :style="agent.level == 2 ? 'background-color:#EEF2FF;color:#4F46E5;' : 'background-color:#F3F4F6;color:#6B7280;'"
                                                      x-text="'L' + agent.level"></span>
                                                <span class="text-xs font-semibold truncate" style="color:#1F2126;" x-text="agent.nama_agent"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="!isPeriodeTerakhir" class="text-sm p-3 rounded-xl mb-1" style="background-color:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0;">
                                        Masih ada periode berikutnya di bulan ini. Mereka bisa dijadwalkan di periode selanjutnya.
                                    </div>
                                    <div x-show="isPeriodeTerakhir" class="text-sm p-3 rounded-xl mb-1" style="background-color:#FEF2F2;color:#DC2626;border:1px solid #FCA5A5;">
                                        Jika Anda lanjut tanpa mengubah toggle mereka menjadi ON, SOP Inbound bulan ini akan dilanggar untuk agent di atas.
                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="px-6 pt-3 pb-5 flex flex-col sm:flex-row gap-2.5 border-t" style="border-color:#E9E9EB;">
                                    <button type="button" @click="kembaliKeToggle()"
                                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold transition-all text-center"
                                        style="background-color:#E2231A;color:#fff;">
                                        &larr; Kembali &amp; Atur Toggle
                                    </button>
                                    <button type="button" @click="konfirmasiLanjut()"
                                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-bold transition-all text-center"
                                        :style="isPeriodeTerakhir ? 'background-color:#F6F6F7;color:#6B7280;border:1px solid #E9E9EB;' : 'background-color:#1F2126;color:#fff;'"
                                        x-text="isPeriodeTerakhir ? 'Lanjut & Abaikan SOP' : 'Lanjut Tetap'">
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- ==================== END POP-UP ==================== --}}

                        {{-- Toast inbound --}}
                        <div x-show="toastInbound"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed bottom-20 right-6 z-50 px-4 py-3 rounded-xl text-sm font-bold shadow-lg"
                             :style="toastInboundType === 'success' ? 'background:#16A34A;color:#fff;' : 'background:#DC2626;color:#fff;'"
                             style="display:none;">
                            <span x-text="toastInbound"></span>
                        </div>
                    </div>
                    {{-- ==================== END PANEL INBOUND ==================== --}}

                    {{-- Tombol Lanjut ke Generate --}}
                    <div class="card p-6 mt-6 flex flex-col md:flex-row md:items-center justify-between gap-4" style="background-color:#F6F6F7;">
                        <p class="text-sm font-semibold" :class="allDaysFilled() ? 'text-success-700' : 'text-gold-700'">
                            <span class="font-extrabold text-lg" x-text="filledCount()"></span> dari 7 hari sudah diisi.
                            <br class="md:hidden">
                            <span x-show="!allDaysFilled()" class="text-xs font-normal" style="color:#82858E;">Lengkapi semua hari sebelum generate.</span>
                        </p>
                        <button type="button"
                            :disabled="!allDaysFilled()"
                            class="px-6 py-3 rounded-lg text-sm font-bold text-white transition-all"
                            :class="allDaysFilled() ? '' : 'opacity-50 cursor-not-allowed'"
                            :style="allDaysFilled() ? 'background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);' : 'background-color:#AEB0B6;'"
                            @click="allDaysFilled() && lanjutKeGenerate()">
                            Lanjut ke Generate &rarr;
                        </button>
                    </div>

                </div>

                <script>
                    function kebutuhanWizard() {
                        return {
                            activeDay: 0,
                            selectedTemplate: {},
                            rows: @json($requirementsByHari),
                            templatesData: @json($templatesData),
                            filledDays: @json(array_map('strval', $filledDays)),
                            namaHari: ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'],
                            // Urutan baris: Section (Chat > Website > Informasi > Inbound), lalu Shift (jam masuk).
                            sectionOrder: ['Chat', 'Website', 'Informasi', 'Inbound'],
                            sectionNames: @json($sections->pluck('nama_section', 'id')),
                            shiftOrder: @json($shifts->pluck('id')),
                            sectionColors: @json($sectionColorsData),
                            saving: false,
                            savedDay: null,
                            // applied[hari] = true => template sudah diterapkan tapi belum disimpan
                            applied: {},
                            // dirty[hari] = true => ada perubahan yang belum disimpan
                            dirty: {},
                            toastMsg: '',
                            toastType: '',

                            init() {
                                // Pastikan semua hari sudah terurut (server juga sudah mengurutkan saat load).
                                Object.keys(this.rows).forEach(day => this.sortRows(day));
                            },

                            rowSortKey(row) {
                                const secName = this.sectionNames[row.section_id] ?? '';
                                const secIdx = this.sectionOrder.indexOf(secName);
                                const shiftIdx = this.shiftOrder.indexOf(String(row.shift_id));
                                return (secIdx === -1 ? 99 : secIdx) * 1000 + (shiftIdx === -1 ? 999 : shiftIdx);
                            },

                            sortRows(day) {
                                this.rows[day].sort((a, b) => this.rowSortKey(a) - this.rowSortKey(b));
                            },

                            sectionWarna(id) {
                                return this.sectionColors[this.sectionNames[id]] ?? { bg: '#F6F6F7', text: '#62656F', accent: '#82858E' };
                            },

                            getTotalSlotInbound() {
                                const sid = '{{ $inboundSectionId }}';
                                if (!sid) return 0;
                                let total = 0;
                                Object.values(this.rows).forEach(dayRows => {
                                    dayRows.forEach(r => {
                                        if (String(r.section_id) === sid && parseInt(r.jumlah_kebutuhan) > 0) {
                                            total += parseInt(r.jumlah_kebutuhan) || 0;
                                        }
                                    });
                                });
                                return total;
                            },

                            addRow(day) {
                                this.rows[day].push({ shift_id: '', section_id: '', jumlah_kebutuhan: 1 });
                                this.sortRows(day);
                                this.markDirty(day);
                            },

                            removeRow(day, index) {
                                if (this.rows[day].length > 1) {
                                    this.rows[day].splice(index, 1);
                                    this.markDirty(day);
                                }
                            },

                            markDirty(day) {
                                // Setiap perubahan (tambah/hapus baris, edit baris, ganti template)
                                // mengembalikan semua kondisi ke default:
                                // Terapkan abu-abu, Simpan merah (ada perubahan belum disimpan).
                                this.applied[day] = false;
                                this.dirty[day] = true;
                                this.$dispatch('inbound-slot-changed');
                            },

                            applyTemplate(day) {
                                const templateId = this.selectedTemplate[day];
                                if (!templateId) return;
                                const template = this.templatesData.find(t => t.id == templateId);
                                if (template) {
                                    this.rows[day] = JSON.parse(JSON.stringify(template.details));
                                    this.sortRows(day);
                                    // Template sudah masuk ke kebutuhan operasional tapi belum disimpan:
                                    // Terapkan berubah hijau, Simpan tetap merah.
                                    this.applied[day] = true;
                                    this.dirty[day] = true;
                                    this.$dispatch('inbound-slot-changed');
                                }
                            },

                            async saveDay(day) {
                                // Urutkan dulu (Section lalu Shift) sebelum disimpan & ditampilkan.
                                this.sortRows(day);
                                const validRows = this.rows[day].filter(r => r.shift_id && r.section_id && r.jumlah_kebutuhan > 0);
                                if (validRows.length === 0) {
                                    this.showToast('Lengkapi minimal satu baris shift dan section sebelum menyimpan.', 'error');
                                    return;
                                }

                                this.saving = true;
                                this.savedDay = null;

                                try {
                                    const response = await fetch('{{ route('schedules.requirements.save', $period) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        },
                                        body: JSON.stringify({ hari_ke: this.namaHari[day], details: validRows }),
                                    });

                                    if (response.ok) {
                                        if (!this.filledDays.includes(this.namaHari[day])) {
                                            this.filledDays.push(this.namaHari[day]);
                                        }
                                        this.savedDay = day;
                                        // Berhasil disimpan: Simpan berubah hijau, Terapkan kembali abu-abu.
                                        this.applied[day] = false;
                                        this.dirty[day] = false;
                                        this.$dispatch('inbound-slot-changed');
                                        this.showToast('Hari ' + this.namaHari[day].charAt(0).toUpperCase() + this.namaHari[day].slice(1) + ' berhasil disimpan!', 'success');
                                    } else {
                                        this.showToast('Gagal menyimpan. Periksa kembali isian Anda.', 'error');
                                    }
                                } finally {
                                    this.saving = false;
                                }
                            },

                            showToast(msg, type) {
                                this.toastMsg = msg;
                                this.toastType = type;
                                setTimeout(() => { this.toastMsg = ''; }, 3000);
                            },

                            isDayFilled(day) {
                                return this.filledDays.includes(this.namaHari[day]);
                            },

                            // Hari dianggap "sudah tersimpan" jika ada di filledDays
                            // DAN tidak ada perubahan yang belum disimpan.
                            isDaySaved(day) {
                                return this.isDayFilled(day) && !this.dirty[day];
                            },

                            // Gaya tab hari: aktif = merah; sudah disimpan = hijau; belum = netral.
                            tabStyle(day) {
                                if (this.activeDay === day) {
                                    return 'background-color:#E2231A;color:#fff;border-color:#E2231A;';
                                }
                                if (this.isDaySaved(day)) {
                                    return 'background-color:#F0FDF4;color:#047857;border-color:#34D399;';
                                }
                                return 'background-color:#ffffff;color:#62656F;border-color:#D2D3D6;';
                            },

                            filledCount() {
                                return this.filledDays.length;
                            },

                            allDaysFilled() {
                                return this.filledDays.length === 7;
                            },

                            lanjutKeGenerate() {
                                // Ambil state dari Alpine component inbound-panel
                                const inboundEl = document.getElementById('inbound-panel');
                                if (!inboundEl) {
                                    window.location.href = '{{ route('schedules.generate.run', $period) }}';
                                    return;
                                }
                                const inbound = Alpine.$data(inboundEl);
                                inbound.cekSebelumLanjut('{{ route('schedules.generate.run', $period) }}');
                            },
                        };
                    }

                    function inboundToggle() {
                        return {
                            agentsLevel2: [],
                            agentsLevel3: [],
                            estimasi: {},
                            saving: false,
                            loading: true,
                            toastInbound: '',
                            toastInboundType: '',
                            showPopup: false,
                            isPeriodeTerakhir: false,
                            bulanLabel: '',
                            agentBelumToggleOn: [],
                            nextUrl: '',
                            autosaveTimer: null,

                            async loadData() {
                                this.loading = true;
                                try {
                                    const res = await fetch('{{ $inboundDataUrl }}');
                                    const data = await res.json();

                                    this.agentsLevel2 = data.agents.filter(a => a.level == 2);
                                    this.agentsLevel3 = data.agents.filter(a => a.level == 3);
                                    this.isPeriodeTerakhir = !data.ada_periode_berikutnya;
                                    this.bulanLabel = data.bulan_label;

                                    // Ambil dari wizard jika tersedia, fallback ke nilai server
                                    const slotFromWizard = this.getSlotFromWizard();
                                    this.estimasi = {
                                        total_slot: slotFromWizard > 0 ? slotFromWizard : data.total_slot_inbound,
                                        jumlah_level1: data.jumlah_level1,
                                    };
                                } finally {
                                    this.loading = false;
                                }

                                // Sinkronkan total_slot setiap kali kebutuhanWizard mengubah rows
                                window.addEventListener('inbound-slot-changed', () => {
                                    this.estimasi = {
                                        ...this.estimasi,
                                        total_slot: this.getSlotFromWizard(),
                                    };
                                });
                            },

                            getSlotFromWizard() {
                                // Ambil total_slot_inbound langsung dari kebutuhanWizard (selalu up-to-date)
                                const wizardEl = document.querySelector('[x-data^="kebutuhanWizard"]');
                                if (!wizardEl) return 0;
                                try {
                                    const wizard = Alpine.$data(wizardEl);
                                    return wizard.getTotalSlotInbound ? wizard.getTotalSlotInbound() : 0;
                                } catch (e) {
                                    return 0;
                                }
                            },

                            toggleOnCount() {
                                return [...this.agentsLevel2, ...this.agentsLevel3]
                                    .filter(a => a.toggle_on).length;
                            },

                            slotUntukLevel1() {
                                return (this.estimasi.total_slot ?? 0) - this.toggleOnCount();
                            },

                            rataRataLevel1() {
                                const level1 = this.estimasi.jumlah_level1 ?? 0;
                                if (level1 === 0) return 0;
                                return Math.round((this.slotUntukLevel1() / level1) * 10) / 10;
                            },

                            toggleAgent(level, id) {
                                const list = level === 2 ? this.agentsLevel2 : this.agentsLevel3;
                                const idx = list.findIndex(a => a.id === id);
                                if (idx === -1) return;
                                // Replace object agar Alpine mendeteksi perubahan reactive
                                list.splice(idx, 1, { ...list[idx], toggle_on: !list[idx].toggle_on });
                                this.scheduleAutosave();
                            },

                            selectAll(level) {
                                if (level === 2) {
                                    this.agentsLevel2 = this.agentsLevel2.map(a => ({ ...a, toggle_on: true }));
                                } else {
                                    this.agentsLevel3 = this.agentsLevel3.map(a => ({ ...a, toggle_on: true }));
                                }
                                this.scheduleAutosave();
                            },

                            deselectAll(level) {
                                if (level === 2) {
                                    this.agentsLevel2 = this.agentsLevel2.map(a => ({ ...a, toggle_on: false }));
                                } else {
                                    this.agentsLevel3 = this.agentsLevel3.map(a => ({ ...a, toggle_on: false }));
                                }
                                this.scheduleAutosave();
                            },

                            // Simpan otomatis 400ms setelah toggle terakhir berubah,
                            // supaya tidak mengirim request berulang saat toggle cepat.
                            scheduleAutosave() {
                                clearTimeout(this.autosaveTimer);
                                this.autosaveTimer = setTimeout(() => {
                                    this.autosaveTimer = null;
                                    this.saveAssignments(false);
                                }, 400);
                            },

                            async saveAssignments(showToast = true) {
                                this.saving = true;
                                const onIds = [...this.agentsLevel2, ...this.agentsLevel3]
                                    .filter(a => a.toggle_on)
                                    .map(a => a.id);

                                try {
                                    const res = await fetch('{{ $inboundSaveUrl }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        },
                                        body: JSON.stringify({ agent_ids: onIds }),
                                    });

                                    if (res.ok) {
                                        if (showToast) this.showToastInbound('Penugasan Inbound berhasil disimpan!', 'success');
                                    } else {
                                        if (showToast) this.showToastInbound('Gagal menyimpan penugasan.', 'error');
                                    }
                                } catch (e) {
                                    if (showToast) this.showToastInbound('Terjadi kesalahan.', 'error');
                                } finally {
                                    this.saving = false;
                                }
                            },

                            showToastInbound(msg, type) {
                                this.toastInbound = msg;
                                this.toastInboundType = type;
                                setTimeout(() => { this.toastInbound = ''; }, 3000);
                            },

                            /**
                             * Dipanggil dari kebutuhanWizard.lanjutKeGenerate() sebelum pindah step.
                             * Cek apakah ada agent yang belum dapat Inbound bulan ini tapi toggle OFF.
                             */
                            async cekSebelumLanjut(url) {
                                this.nextUrl = url;

                                // Pastikan perubahan toggle terakhir ikut tersimpan
                                // sebelum pindah ke halaman berikutnya.
                                if (this.autosaveTimer) {
                                    clearTimeout(this.autosaveTimer);
                                    this.autosaveTimer = null;
                                    await this.saveAssignments(false);
                                }

                                // Kumpulkan agent yang belum dapat inbound bulan ini DAN toggle OFF
                                this.agentBelumToggleOn = [...this.agentsLevel2, ...this.agentsLevel3]
                                    .filter(a => !a.sudah_dapat && !a.toggle_on);

                                if (this.agentBelumToggleOn.length === 0) {
                                    // Tidak ada masalah, langsung lanjut
                                    window.location.href = url;
                                    return;
                                }

                                // Ada yang belum — tampilkan pop-up
                                this.showPopup = true;
                            },

                            kembaliKeToggle() {
                                this.showPopup = false;
                                // Scroll ke panel inbound
                                document.getElementById('inbound-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            },

                            konfirmasiLanjut() {
                                this.showPopup = false;
                                window.location.href = this.nextUrl;
                            },
                        };
                    }
                </script>
            @endif
        </div>
    </div>
</x-app-layout>
