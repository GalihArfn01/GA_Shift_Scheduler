<x-app-layout>
    <x-slot name="header">History Jadwal</x-slot>

    <div class="py-8 animate-fade-in" x-data="{
        showEditModal: false,
        editForm: { id: null, nama_periode: '', tanggal_mulai: '', hasResult: false },
        openEdit(period, hasResult) {
            this.editForm = {
                id: period.id,
                nama_periode: period.nama_periode,
                tanggal_mulai: period.tanggal_mulai,
                hasResult: hasResult,
            };
            this.showEditModal = true;
        },
        loadBody(url) {
            const container = document.querySelector('#history-data');
            if (!container) return;
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.querySelector('#history-data');
                    if (!fresh) return;
                    container.innerHTML = fresh.innerHTML;
                    // Init Alpine segera untuk konten baru (observer bisa telat/ganda
                    // sehingga binding pada konten hasil fetch mati saat interaksi pertama).
                    Array.from(container.children).forEach((child) => Alpine.initTree(child));
                    window.history.pushState({}, '', url);
                })
                .catch(() => {});
        },
        submitFilter(event) {
            const form = event.target;
            const params = new URLSearchParams(new FormData(form));
            this.loadBody(form.action + '?' + params.toString());
        },
        handleBodyClick(event) {
            // Delegasi: hanya link yang menuju halaman yang sama (pagination &
            // Reset) dimuat via AJAX; link ke halaman lain (Lihat/Kelola) tetap navigasi penuh.
            const link = event.target.closest('a');
            if (!link) return;
            const url = new URL(link.href);
            if (url.origin !== window.location.origin) return;
            if (url.pathname !== window.location.pathname) return;
            event.preventDefault();
            this.loadBody(url.toString());
        }
    }">
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

            @if (session('error'))
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FEE2E2;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">History Jadwal</h1>
                </div>
                <a href="{{ route('schedules.wizard') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                    onmouseover="this.style.background='#C01A12';"
                    onmouseout="this.style.background='#E2231A';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Buat Periode Baru
                </a>
            </div>

            {{-- ============ DATA PERIODE (dimuat ulang via AJAX) ============ --}}
            <div id="history-data" @click="handleBodyClick($event)" class="space-y-5">

                {{-- Filter --}}
                <form method="GET" action="{{ route('schedules.history') }}"
                    @submit.prevent="submitFilter($event)"
                    class="card p-4 flex flex-col lg:flex-row lg:items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Cari Periode</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama periode..."
                               class="w-full rounded-lg border px-3 py-2 text-sm transition-all outline-none"
                               style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                               onfocus="this.style.borderColor='#E2231A'"
                               onblur="this.style.borderColor='#E9E9EB'">
                    </div>

                    <div class="w-full sm:w-44">
                        <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Status</label>
                        <select name="status"
                                class="w-full rounded-lg border px-3 py-2 text-sm transition-all outline-none"
                                style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                                onfocus="this.style.borderColor='#E2231A'"
                                onblur="this.style.borderColor='#E9E9EB'">
                            <option value="">Semua Status</option>
                            <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Final</option>
                            <option value="butuh_review" {{ request('status') === 'butuh_review' ? 'selected' : '' }}>Butuh Review</option>
                            <option value="belum_generate" {{ request('status') === 'belum_generate' ? 'selected' : '' }}>Belum Generate</option>
                        </select>
                    </div>

                    <div class="w-full sm:w-40">
                        <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Tahun</label>
                        <select name="tahun"
                                class="w-full rounded-lg border px-3 py-2 text-sm transition-all outline-none"
                                style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                                onfocus="this.style.borderColor='#E2231A'"
                                onblur="this.style.borderColor='#E9E9EB'">
                            <option value="">Semua Tahun</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ request('tahun') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="px-5 py-2 rounded-lg text-sm font-bold transition-all"
                            style="background-color:#F6F6F7; color:#1F2126; border:1px solid #E9E9EB;"
                            onmouseover="this.style.background='#E9E9EB'"
                            onmouseout="this.style.background='#F6F6F7'">
                            Cari
                        </button>
                        @if (request()->hasAny(['search', 'status', 'tahun']))
                            <a href="{{ route('schedules.history') }}"
                                class="px-4 py-2 rounded-lg text-sm font-bold transition-all"
                                style="background-color:#ffffff; color:#62656F; border:1px solid #D2D3D6;"
                                onmouseover="this.style.background='#F6F6F7';"
                                onmouseout="this.style.background='#ffffff';">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                @if ($totalPeriode > 0)
                    <p class="text-xs font-semibold" style="color:#62656F;">
                        Menampilkan {{ $totalPeriode }} periode
                    </p>

                    {{-- ===== 4 PERIODE TERBARU (card, satu baris) ===== --}}
                    @if ($cardPeriodsData->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            @foreach ($cardPeriodsData as $item)
                                @php $period = $item['period']; @endphp
                                <div class="card p-4 flex flex-col gap-3 card-hover relative overflow-hidden">
                                    @if ($period->status === 'final')
                                        <div class="absolute top-0 right-0 w-10 h-10 flex items-center justify-center" style="background-color:#ECFDF5;border-bottom-left-radius:0.875rem;">
                                            <svg class="w-4 h-4" style="color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                    @elseif ($item['hasResult'])
                                        <div class="absolute top-0 right-0 w-10 h-10 flex items-center justify-center" style="background-color:#FFFBEB;border-bottom-left-radius:0.875rem;">
                                            <span class="w-2 h-2 rounded-full animate-pulse" style="background-color:#D97706;"></span>
                                        </div>
                                    @endif

                                    <div>
                                        <h3 class="text-base font-extrabold pr-8" style="color:#1F2126;line-height:1.2;">
                                            {{ $period->nama_periode }}
                                        </h3>
                                        <p class="text-[11px] mt-1 font-semibold" style="color:#82858E;">
                                            {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                                            &ndash;
                                            {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if ($period->status === 'final')
                                            <span class="badge badge-approved text-[10px]">FINAL</span>
                                        @elseif ($item['hasResult'])
                                            <span class="badge badge-pending text-[10px]">BUTUH REVIEW</span>
                                        @else
                                            <span class="badge badge-info text-[10px]">BELUM GENERATE</span>
                                        @endif

                                        @if ($item['hasResult'])
                                            <span class="text-[10px] font-semibold" style="color:#059669;">
                                                &middot; {{ $item['totalSlotKerja'] }} slot terisi
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex gap-2 mt-auto">
                                        @if ($item['hasResult'])
                                            <a href="{{ route('schedules.history.show', $period) }}"
                                                class="flex-1 px-3 py-1.5 rounded-lg text-xs font-bold text-center transition-colors"
                                                style="background-color:#F6F6F7;color:#3F4249;border:1px solid #D2D3D6;"
                                                onmouseover="this.style.background='#E9E9EB';"
                                                onmouseout="this.style.background='#F6F6F7';">
                                                Lihat Jadwal
                                            </a>
                                            <a href="{{ route('pic.schedule-grid.edit', $period) }}"
                                                class="flex-1 px-3 py-1.5 rounded-lg text-xs font-bold text-center transition-colors"
                                                style="background-color:#F6F6F7;color:#3F4249;border:1px solid #D2D3D6;"
                                                onmouseover="this.style.background='#E9E9EB';"
                                                onmouseout="this.style.background='#F6F6F7';">
                                                Ubah Grid
                                            </a>
                                        @endif

                                        @if ($period->status !== 'final')
                                            <a href="{{ $item['requirementsLengkap'] ? route('schedules.generate.run', $period) : route('schedules.wizard', ['period_id' => $period->id]) }}"
                                                class="flex-1 px-3 py-1.5 rounded-lg text-xs font-bold text-center text-white transition-all"
                                                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                                onmouseover="this.style.background='#C01A12';"
                                                onmouseout="this.style.background='#E2231A';">
                                                {{ $item['hasResult'] ? 'Kelola Jadwal' : ($item['requirementsLengkap'] ? 'Generate' : 'Lanjutkan') }}
                                            </a>
                                        @endif
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex gap-2 border-t pt-2.5" style="border-color:#E9E9EB;">
                                        <button type="button"
                                            @click="openEdit({
                                                id: {{ $period->id }},
                                                nama_periode: @js($period->nama_periode),
                                                tanggal_mulai: '{{ \Carbon\Carbon::parse($period->tanggal_mulai)->format('Y-m-d') }}'
                                            }, {{ $item['hasResult'] ? 'true' : 'false' }})"
                                            class="flex-1 inline-flex items-center justify-center gap-1 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors"
                                            style="color:#62656F;"
                                            onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                            onmouseout="this.style.background='';this.style.color='#62656F';">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Edit
                                        </button>
                                        <form action="{{ route('schedules.periods.destroy', $period) }}" method="POST"
                                            onsubmit="return confirm('Periode &quot;{{ $period->nama_periode }}&quot; beserta seluruh data kebutuhan operasional dan jadwalnya akan dihapus permanen. Lanjutkan?');"
                                            class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-full inline-flex items-center justify-center gap-1 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-colors"
                                                style="color:#DC2626;"
                                                onmouseover="this.style.background='#FEF2F2';this.style.color='#B91C1C';"
                                                onmouseout="this.style.background='';this.style.color='#DC2626';">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- ===== SISANYA (list ringkas, paginated) ===== --}}
                    @if ($listPeriodsData->total() > 0)
                        <div class="card overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                            <th class="px-4 py-2.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Periode</span>
                                            </th>
                                            <th class="px-4 py-2.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Status</span>
                                            </th>
                                            <th class="px-4 py-2.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Informasi</span>
                                            </th>
                                            <th class="px-4 py-2.5 text-right">
                                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color:#E9E9EB;">
                                        @foreach ($listPeriodsData as $item)
                                            @php $period = $item['period']; @endphp
                                            <tr class="transition-colors" style="background-color:#ffffff;"
                                                onmouseover="this.style.background='#F6F6F7';"
                                                onmouseout="this.style.background='#ffffff';">
                                                <td class="px-4 py-2">
                                                    <div class="font-bold text-sm" style="color:#1F2126;">{{ $period->nama_periode }}</div>
                                                    <div class="text-xs mt-0.5" style="color:#82858E;">
                                                        {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                                                        &ndash;
                                                        {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-2">
                                                    @if ($period->status === 'final')
                                                        <span class="badge badge-approved text-[10px]">FINAL</span>
                                                    @elseif ($item['hasResult'])
                                                        <span class="badge badge-pending text-[10px]">BUTUH REVIEW</span>
                                                    @else
                                                        <span class="badge badge-info text-[10px]">BELUM GENERATE</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-sm" style="color:#62656F;">
                                                    @if ($item['hasResult'])
                                                        {{ $item['totalSlotKerja'] }} slot terisi
                                                    @else
                                                        Belum ada hasil
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-right">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        @if ($item['hasResult'])
                                                            <a href="{{ route('schedules.history.show', $period) }}"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition-colors"
                                                                style="background-color:#F6F6F7;color:#3F4249;border:1px solid #D2D3D6;"
                                                                onmouseover="this.style.background='#E9E9EB';"
                                                                onmouseout="this.style.background='#F6F6F7';">
                                                                Lihat
                                                            </a>
                                                            <a href="{{ route('pic.schedule-grid.edit', $period) }}"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition-colors"
                                                                style="background-color:#F6F6F7;color:#3F4249;border:1px solid #D2D3D6;"
                                                                onmouseover="this.style.background='#E9E9EB';"
                                                                onmouseout="this.style.background='#F6F6F7';">
                                                                Ubah Grid
                                                            </a>
                                                        @endif

                                                        @if ($period->status !== 'final')
                                                            <a href="{{ $item['requirementsLengkap'] ? route('schedules.generate.run', $period) : route('schedules.wizard', ['period_id' => $period->id]) }}"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-bold text-white transition-all"
                                                                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                                                onmouseover="this.style.background='#C01A12';"
                                                                onmouseout="this.style.background='#E2231A';">
                                                                {{ $item['hasResult'] ? 'Kelola' : ($item['requirementsLengkap'] ? 'Generate' : 'Lanjutkan') }}
                                                            </a>
                                                        @endif

                                                        <button type="button"
                                                            @click="openEdit({
                                                                id: {{ $period->id }},
                                                                nama_periode: @js($period->nama_periode),
                                                                tanggal_mulai: '{{ \Carbon\Carbon::parse($period->tanggal_mulai)->format('Y-m-d') }}'
                                                            }, {{ $item['hasResult'] ? 'true' : 'false' }})"
                                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold transition-colors"
                                                            style="color:#62656F;"
                                                            onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                                            onmouseout="this.style.background='';this.style.color='#62656F';"
                                                            title="Edit">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                            Edit
                                                        </button>
                                                        <form action="{{ route('schedules.periods.destroy', $period) }}" method="POST"
                                                            onsubmit="return confirm('Periode &quot;{{ $period->nama_periode }}&quot; beserta seluruh data kebutuhan operasional dan jadwalnya akan dihapus permanen. Lanjutkan?');"
                                                            class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold transition-colors"
                                                                style="color:#DC2626;"
                                                                onmouseover="this.style.background='#FEF2F2';this.style.color='#B91C1C';"
                                                                onmouseout="this.style.background='';this.style.color='#DC2626';"
                                                                title="Hapus">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if ($listPeriodsData->hasPages())
                                <div class="px-6 py-4 border-t" style="border-color:#E9E9EB;">
                                    {{ $listPeriodsData->links() }}
                                </div>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="card p-12 text-center border-dashed">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                            <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @if (request()->hasAny(['search', 'status', 'tahun']))
                            <p class="text-sm font-semibold" style="color:#62656F;">Tidak ada periode yang cocok dengan filter.</p>
                        @else
                            <p class="text-sm font-semibold" style="color:#62656F;">Belum ada periode jadwal yang dibuat.</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ==================== REKAP BULANAN ==================== --}}
            @if (!empty($rekapBulanan))
                <div class="space-y-4 pt-4 border-t" style="border-color:#E9E9EB;">
                    <div>
                        <h3 class="text-lg font-bold" style="color:#1F2126;">Rekap Bulanan</h3>
                        <p class="text-xs" style="color:#82858E;">
                            Total hari kerja per agent per bulan (berdasarkan tanggal mulai periode). Hanya menghitung periode dengan hasil generate.
                        </p>
                    </div>

                    @foreach ($rekapBulanan as $bulanIndex => $bulan)
                        <div x-data="{ open: {{ $bulanIndex === 0 ? 'true' : 'false' }} }"
                            class="card overflow-hidden">
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-5 py-4 transition-colors"
                                style="background-color:#FCFAF7;border-bottom:1px solid #E9E9EB;"
                                onmouseover="this.style.background='#F6F6F7';"
                                onmouseout="this.style.background='#FCFAF7';">
                                <span class="font-bold text-sm" style="color:#1F2126;">{{ $bulan['label'] }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''"
                                    style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-cloak class="overflow-x-auto">
                                <table class="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr style="border-bottom:1px solid #E9E9EB;background-color:#F6F6F7;">
                                            <th class="px-5 py-3 sticky left-0 z-10" style="background-color:#F6F6F7;min-w:180px;">
                                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Agent</span>
                                            </th>
                                            @foreach ($bulan['periods'] as $col)
                                                <th class="px-5 py-3 text-center min-w-[90px]">
                                                    <span class="text-xs font-bold" style="color:#3F4249;">{{ $col['label'] }}</span>
                                                    <div class="text-[10px]" style="color:#82858E;">{{ $col['sublabel'] }}</div>
                                                </th>
                                            @endforeach
                                            <th class="px-5 py-3 text-center min-w-[90px]" style="background-color:#FFF1F0;">
                                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#C01A12;">Total Bulan</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color:#E9E9EB;">
                                        @foreach ($bulan['rows'] as $row)
                                            <tr class="transition-colors hover:bg-ink-50"
                                                style="{{ $row['agent']->status !== 'aktif' ? 'background-color:#FEF2F2;' : 'background-color:#ffffff;' }}">
                                                <td class="px-5 py-3 sticky left-0 z-10"
                                                    style="{{ $row['agent']->status !== 'aktif' ? 'background-color:#FEF2F2;' : 'background-color:#ffffff;' }}">
                                                    <div class="font-bold text-sm" style="color:#1F2126;">{{ $row['agent']->nama_agent }}</div>
                                                    <div class="text-xs mt-0.5" style="color:#82858E;">
                                                        Level {{ $row['agent']->level }}
                                                    </div>
                                                </td>
                                                @foreach ($bulan['periods'] as $col)
                                                    <td class="px-5 py-3 text-center text-sm font-semibold" style="color:#3F4249;">
                                                        {{ $row['perPeriode'][$col['id']] }}
                                                    </td>
                                                @endforeach
                                                <td class="px-5 py-3 text-center text-sm font-bold" style="background-color:#FFF1F0;color:#C01A12;">
                                                    {{ $row['total'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Modal Edit Periode --}}
        <div x-show="showEditModal" x-cloak @keydown.escape.window="showEditModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4">

            <div @click="showEditModal = false" class="fixed inset-0 transition-opacity"
                 style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <div @click.stop x-show="showEditModal"
                 class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-lift"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                <button @click="showEditModal = false"
                        class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                        style="color:#82858E;"
                        onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                        onmouseout="this.style.background='';this.style.color='#82858E';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <h3 class="text-xl font-extrabold mb-5" style="color:#1F2126;">Edit Periode</h3>

                <form :action="`/schedules/periods/${editForm.id}`" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Nama Periode</label>
                            <input type="text" name="nama_periode" x-model="editForm.nama_periode" required class="input-field">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">
                                Tanggal Mulai
                                <span class="text-[10px] font-normal" style="color:#82858E;">(periode 7 hari otomatis)</span>
                            </label>
                            <input type="date" name="tanggal_mulai" x-model="editForm.tanggal_mulai"
                                class="input-field"
                                x-bind:disabled="editForm.hasResult"
                                x-bind:required="!editForm.hasResult" />
                            <p x-show="editForm.hasResult" class="text-xs font-semibold mt-1.5" style="color:#D97706;">
                                Tanggal tidak bisa diubah karena periode ini sudah memiliki hasil generate.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6">
                        <button type="button" @click="showEditModal = false"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
                            style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                            onmouseover="this.style.background='#F6F6F7';"
                            onmouseout="this.style.background='#fff';">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
