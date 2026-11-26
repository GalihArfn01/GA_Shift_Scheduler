<x-app-layout>
    <x-slot name="header">Template Operasional</x-slot>

    @php
        // Warna identitas per section (border baris preview detail template).
        $sectionColorsData = collect(['Chat', 'Website', 'Informasi', 'Inbound'])
            ->mapWithKeys(fn ($s) => [$s => \App\Models\Section::warna($s)])
            ->all();
    @endphp

    <div class="py-8 animate-fade-in" x-data="{ expanded: null, toggle(id) { this.expanded = this.expanded === id ? null : id; } }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#ECFDF5;color:#047857;border:1px solid #D1FAE5;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Template Operasional</h1>
                </div>
                <a href="{{ route('operational-templates.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all whitespace-nowrap"
                    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                    onmouseover="this.style.background='#C01A12';"
                    onmouseout="this.style.background='#E2231A';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Buat Template
                </a>
            </div>

            {{-- Filter & pencarian --}}
            <form method="GET" action="{{ route('operational-templates.index') }}"
                class="card p-4 flex flex-col md:flex-row gap-3 md:items-end">
                <div class="flex-1">
                    <label class="block text-xs font-bold mb-1.5" style="color:#62656F;">Cari Template</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="Nama template atau keterangan..."
                        class="input-field py-2">
                </div>
                <div class="w-full md:w-56">
                    <label class="block text-xs font-bold mb-1.5" style="color:#62656F;">Section</label>
                    <select name="section_id" class="input-field py-2 bg-white">
                        <option value="">Semua Section</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>
                                {{ $section->nama_section }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                        style="background-color:#E2231A;"
                        onmouseover="this.style.background='#C01A12';"
                        onmouseout="this.style.background='#E2231A';">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Cari
                    </button>
                    @if (request()->has('q') || request()->has('section_id'))
                        <a href="{{ route('operational-templates.index') }}"
                            class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
                            style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                            onmouseover="this.style.background='#F6F6F7';"
                            onmouseout="this.style.background='#fff';">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Template</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Keterangan</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Jumlah Baris</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Total Kebutuhan</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6 text-right">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E9E9EB;">
                            @forelse ($templates as $template)
                                <tr class="transition-colors hover:bg-ink-50" style="background-color:#ffffff;"
                                    onmouseover="this.style.background='#F6F6F7';"
                                    onmouseout="this.style.background='#ffffff';">
                                    <td class="px-5 py-4 sm:px-6">
                                        <button type="button"
                                            @click="toggle({{ $template->id }})"
                                            class="flex items-center gap-2 text-left group"
                                            title="Lihat detail kebutuhan">
                                            <svg class="w-4 h-4 shrink-0 transition-transform duration-200" style="color:#AEB0B6;"
                                                :style="expanded === {{ $template->id }} ? 'transform:rotate(90deg);color:#E2231A;' : ''"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                            <span class="font-bold text-sm" style="color:#1F2126;">{{ $template->nama_template }}</span>
                                        </button>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-sm" style="color:#62656F;">
                                        {{ $template->keterangan ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                              style="background-color:#F6F6F7;color:#3F4249;border:1px solid #E9E9EB;">
                                            {{ $template->details_count }} baris
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                              style="background-color:#FFF1F0;color:#C01A12;border:1px solid #FFC5C0;">
                                            {{ $template->details_sum_jumlah_kebutuhan ?? 0 }} slot
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('operational-templates.edit', $template) }}"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                style="color:#62656F;"
                                                onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                                onmouseout="this.style.background='';this.style.color='#62656F';"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <form action="{{ route('operational-templates.duplicate', $template) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                    style="color:#1D4ED8;"
                                                    onmouseover="this.style.background='#EFF6FF';this.style.color='#1E40AF';"
                                                    onmouseout="this.style.background='';this.style.color='#1D4ED8';"
                                                    title="Duplikat template">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form action="{{ route('operational-templates.destroy', $template) }}" method="POST"
                                                onsubmit="return confirm('Hapus template {{ $template->nama_template }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                    style="color:#DC2626;"
                                                    onmouseover="this.style.background='#FEF2F2';this.style.color='#B91C1C';"
                                                    onmouseout="this.style.background='';this.style.color='#DC2626';"
                                                    title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                {{-- Baris preview detail (expandable) --}}
                                <tr x-show="expanded === {{ $template->id }}" x-cloak
                                    style="background-color:#FAFAFB;border-top:1px solid #E9E9EB;">
                                    <td colspan="5" class="px-5 py-4 sm:px-6">
                                        <div class="mb-3 text-xs font-semibold" style="color:#AEB0B6;">
                                            {{ $template->details_count }} baris &middot; {{ $template->details_sum_jumlah_kebutuhan ?? 0 }} slot
                                        </div>
                                        @if ($template->details->isEmpty())
                                            <p class="text-sm" style="color:#AEB0B6;">Template ini tidak memiliki detail kebutuhan.</p>
                                        @else
                                            <div class="overflow-hidden rounded-lg border" style="border-color:#E9E9EB;">
                                                <table class="w-full text-left text-xs border-collapse">
                                                    <thead>
                                                        <tr style="background-color:#F6F6F7;border-bottom:1px solid #E9E9EB;">
                                                            <th class="px-4 py-2 font-bold" style="color:#62656F;">Section</th>
                                                            <th class="px-4 py-2 font-bold" style="color:#62656F;">Shift</th>
                                                            <th class="px-4 py-2 text-center font-bold" style="color:#62656F;">Jumlah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y" style="border-color:#F0F0F0;">
                                                        @foreach ($template->details as $detail)
                                                            @php
                                                                $secName = $detail->section->nama_section ?? '';
                                                                $warna = $sectionColorsData[$secName] ?? ['bg' => '#F6F6F7', 'text' => '#62656F', 'accent' => '#82858E'];
                                                            @endphp
                                                            <tr style="background-color:#ffffff;border-left:4px solid {{ $warna['accent'] }};">
                                                                <td class="px-4 py-2 font-semibold" style="color:{{ $warna['text'] }};">
                                                                    {{ $secName ?: '—' }}
                                                                </td>
                                                                <td class="px-4 py-2" style="color:#3F4249;">
                                                                    @if ($detail->shift)
                                                                        <span class="font-bold">{{ $detail->shift->kode_shift }}</span>
                                                                        <span class="ml-1" style="color:#82858E;">
                                                                            [{{ \Carbon\Carbon::parse($detail->shift->jam_masuk)->format('H:i') }}–{{ \Carbon\Carbon::parse($detail->shift->jam_pulang)->format('H:i') }}]
                                                                        </span>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-2 text-center font-bold" style="color:#1F2126;">
                                                                    {{ $detail->jumlah_kebutuhan }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                            <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        @if (request()->has('q') || request()->has('section_id'))
                                            <p class="text-sm font-semibold" style="color:#62656F;">Tidak ada template yang cocok.</p>
                                        @else
                                            <p class="text-sm font-semibold" style="color:#62656F;">Belum ada template operasional.</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($templates->hasPages())
                <div>
                    {{ $templates->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
