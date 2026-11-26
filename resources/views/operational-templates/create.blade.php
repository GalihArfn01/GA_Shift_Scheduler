<x-app-layout>
    <x-slot name="header">Buat Template Operasional</x-slot>

    @php
        // Warna identitas per section (dipakai sebagai warna border baris detail template).
        // Dihitung di sini supaya @json hanya menerima variabel sederhana (tanpa koma).
        $sectionColorsData = collect(['Chat', 'Website', 'Informasi', 'Inbound'])
            ->mapWithKeys(fn ($s) => [$s => \App\Models\Section::warna($s)])
            ->all();
    @endphp

    <div class="py-8 animate-fade-in"
        x-data="{
            rows: [{ shift_id: '', section_id: '', jumlah_kebutuhan: 1 }],
            // Urutan baris: Section (Chat > Website > Informasi > Inbound), lalu Shift (jam masuk).
            sectionOrder: ['Chat', 'Website', 'Informasi', 'Inbound'],
            sectionNames: {{ Illuminate\Support\Js::from($sections->pluck('nama_section', 'id')) }},
            shiftOrder: {{ Illuminate\Support\Js::from($shifts->pluck('id')) }},
            sectionColors: {{ Illuminate\Support\Js::from($sectionColorsData) }},
            rowSortKey(row) {
                const secName = this.sectionNames[row.section_id] ?? '';
                const secIdx = this.sectionOrder.indexOf(secName);
                const shiftIdx = this.shiftOrder.indexOf(String(row.shift_id));
                return (secIdx === -1 ? 99 : secIdx) * 1000 + (shiftIdx === -1 ? 999 : shiftIdx);
            },
            sortRows() {
                this.rows.sort((a, b) => this.rowSortKey(a) - this.rowSortKey(b));
            },
            sectionWarna(id) {
                return this.sectionColors[this.sectionNames[id]] ?? { bg: '#F6F6F7', text: '#62656F', accent: '#82858E' };
            },
            addRow() {
                this.rows.push({ shift_id: '', section_id: '', jumlah_kebutuhan: 1 });
                this.sortRows();
            },
            duplicateRow(index) {
                const src = this.rows[index];
                if (!src) return;
                this.rows.splice(index + 1, 0, {
                    shift_id: src.shift_id,
                    section_id: src.section_id,
                    jumlah_kebutuhan: src.jumlah_kebutuhan,
                });
                this.sortRows();
            },
            removeRow(index) {
                if (this.rows.length > 1) {
                    this.rows.splice(index, 1);
                }
            }
        }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 mb-6">
                <a href="{{ route('operational-templates.index') }}"
                   class="w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                   style="background-color:#F6F6F7;color:#62656F;"
                   onmouseover="this.style.background='#E9E9EB';this.style.color='#1F2126';"
                   onmouseout="this.style.background='#F6F6F7';this.style.color='#62656F';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="page-title">Template Baru</h1>
                </div>
            </div>

            <form action="{{ route('operational-templates.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="card p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Nama Template</label>
                        <input type="text" name="nama_template" value="{{ old('nama_template') }}" required
                            placeholder="contoh: Hari Biasa, Jumat, Tahun Baru"
                            class="input-field">
                        @error('nama_template')
                            <p class="text-xs mt-1.5 font-semibold" style="color:#DC2626;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Keterangan</label>
                        <textarea name="keterangan" rows="2"
                            class="input-field">{{ old('keterangan') }}</textarea>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                        <h3 class="text-base font-bold" style="color:#1F2126;">Detail Kebutuhan</h3>
                        <button type="button" @click="addRow()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                            style="background-color:#FFF1F0;color:#E2231A;"
                            onmouseover="this.style.background='#FFE0DD';"
                            onmouseout="this.style.background='#FFF1F0';">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Baris
                        </button>
                    </div>

                    @error('details')
                        <div class="p-3 rounded-lg mb-4 text-xs font-semibold" style="background-color:#FEF2F2;color:#B91C1C;">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="space-y-3">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex gap-3 items-start animate-fade-in p-3 rounded-xl"
                                 :style="'background-color:#F6F6F7;border:1px solid #E9E9EB;border-left:4px solid ' + (row.section_id ? sectionWarna(row.section_id).accent : '#D2D3D6') + ';'"
                                 style="background-color:#F6F6F7;border:1px solid #E9E9EB;border-left:4px solid #D2D3D6;">
                                <div class="flex-1">
                                    <label class="block text-xs font-bold mb-1" style="color:#62656F;">Shift</label>
                                    <select :name="`details[${index}][shift_id]`" x-model="row.shift_id" required
                                        @change="row.shift_id = $event.target.value; sortRows()"
                                        class="input-field py-2 text-sm bg-white">
                                        <option value="">Pilih Shift</option>
                                        @foreach ($shifts as $shift)
                                            <option value="{{ $shift->id }}">
                                                {{ $shift->kode_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-xs font-bold mb-1" style="color:#62656F;">Section</label>
                                    <select :name="`details[${index}][section_id]`" x-model="row.section_id" required
                                        @change="row.section_id = $event.target.value; sortRows()"
                                        class="input-field py-2 text-sm bg-white">
                                        <option value="">Pilih Section</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}">{{ $section->nama_section }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="w-24">
                                    <label class="block text-xs font-bold mb-1" style="color:#62656F;">Jumlah</label>
                                    <input type="number" :name="`details[${index}][jumlah_kebutuhan]`"
                                        x-model="row.jumlah_kebutuhan" min="1" required
                                        class="input-field py-2 text-sm bg-white text-center">
                                </div>

                                <div class="pt-5 flex gap-1.5">
                                    <button type="button" @click="duplicateRow(index)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                        style="color:#1D4ED8;background-color:#ffffff;border:1px solid #D2D3D6;"
                                        onmouseover="this.style.background='#EFF6FF';this.style.color='#1E40AF';this.style.borderColor='#BFDBFE';"
                                        onmouseout="this.style.background='#ffffff';this.style.color='#1D4ED8';this.style.borderColor='#D2D3D6';"
                                        title="Duplikat baris">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                        </svg>
                                    </button>
                                    <button type="button" @click="removeRow(index)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                        style="color:#AEB0B6;background-color:#ffffff;border:1px solid #D2D3D6;"
                                        onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#FCA5A5';"
                                        onmouseout="this.style.background='#ffffff';this.style.color='#AEB0B6';this.style.borderColor='#D2D3D6';"
                                        title="Hapus baris">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-5 flex gap-2 p-3 rounded-lg" style="background-color:#FFFBEB;">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-xs leading-relaxed" style="color:#B45309;">Shift A7 &amp; A khusus section Chat.</p>
                    </div>
                </div>

                {{-- Ringkasan Grid Kebutuhan (Reaktif) --}}
                <div class="card overflow-hidden">
                    <div class="px-6 py-3 flex items-center gap-2 border-b" style="border-color:#E9E9EB; background-color:#FCFAF7;">
                        <svg class="w-4 h-4" fill="none" stroke="#E2231A" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span class="text-xs font-bold uppercase tracking-wider" style="color:#E2231A;">Ringkasan Kebutuhan</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr style="background-color:#F6F6F7; border-bottom:1px solid #E9E9EB;">
                                    <th class="px-4 py-2 sticky left-0 z-10" style="background-color:#F6F6F7; min-width:200px;">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Section / Shift</span>
                                    </th>
                                    <th class="px-3 py-2 text-center font-bold" style="color:#62656F;">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sections as $sec)
                                    <tr style="background-color:#FFF8F7; border-top:2px solid #E9E9EB;">
                                        <td colspan="2" class="px-4 py-1.5">
                                            <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color:#E2231A;">{{ $sec->nama_section }}</span>
                                        </td>
                                    </tr>
                                    @foreach ($shifts as $shf)
                                        <tr class="border-b" style="border-color:#F0F0F0;"
                                            onmouseover="this.style.background='#FAFAFA'"
                                            onmouseout="this.style.background='#ffffff'">
                                            <td class="px-4 py-2 sticky left-0 font-semibold" style="color:#3F4249; background-color:inherit;">
                                                {{ $shf->kode_shift }} [{{ \Carbon\Carbon::parse($shf->jam_masuk)->format('H:i') }}-{{ \Carbon\Carbon::parse($shf->jam_pulang)->format('H:i') }}]
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold text-xs" style="color:#3F4249;"
                                                x-text="rows.filter(r => r.shift_id == '{{ $shf->id }}' && r.section_id == '{{ $sec->id }}').reduce((s, r) => s + (parseInt(r.jumlah_kebutuhan) || 0), 0) || '-'">
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr style="background-color:#F0F9FF; border-top:1px solid #BAE6FD;">
                                        <td class="px-4 py-1.5 sticky left-0 font-extrabold text-[10px] uppercase" style="color:#0369A1; background-color:#F0F9FF;">Total {{ $sec->nama_section }}</td>
                                        <td class="px-3 py-1.5 text-center font-extrabold text-xs" style="color:#0369A1;"
                                            x-text="rows.filter(r => r.section_id == '{{ $sec->id }}').reduce((s, r) => s + (parseInt(r.jumlah_kebutuhan) || 0), 0)">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('operational-templates.index') }}"
                        class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
                        style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                        onmouseover="this.style.background='#F6F6F7';"
                        onmouseout="this.style.background='#fff';">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                        style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                        onmouseover="this.style.background='#C01A12';"
                        onmouseout="this.style.background='#E2231A';">
                        Simpan Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
