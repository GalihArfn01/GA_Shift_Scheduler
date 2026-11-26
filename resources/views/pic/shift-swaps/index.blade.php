<x-app-layout>
    <x-slot name="header">Persetujuan Tukar Jadwal</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
                <div>
                    <h1 class="page-title">Persetujuan Tukar Jadwal</h1>
                </div>
            </div>

            {{-- Card ringkasan status --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @php
                    $summary = [
                        ['key' => '',           'label' => 'Total',       'count' => $counts['total'],     'bg' => '#F6F6F7', 'border' => '#E9E9EB', 'text' => '#1F2126'],
                        ['key' => 'menunggu',   'label' => 'Menunggu',     'count' => $counts['menunggu'],  'bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#B45309'],
                        ['key' => 'disetujui',  'label' => 'Disetujui',    'count' => $counts['disetujui'], 'bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#047857'],
                        ['key' => 'ditolak',    'label' => 'Ditolak',      'count' => $counts['ditolak'],   'bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C'],
                    ];
                @endphp
                @foreach ($summary as $card)
                    <a href="{{ route('pic.shift-swaps.index', array_filter(['status' => $card['key'] ?: null, 'q' => $q ?: null], fn ($v) => $v !== null)) }}"
                        class="rounded-xl p-4 transition-all"
                        style="background-color:{{ $card['bg'] }};border:1px solid {{ $card['border'] }};{{ $status === $card['key'] ? 'box-shadow:0 0 0 2px ' . $card['border'] . ';' : '' }}"
                        onmouseover="this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.transform='';">
                        <p class="text-[10px] font-bold uppercase tracking-wider" style="color:{{ $card['text'] }};">{{ $card['label'] }}</p>
                        <p class="text-2xl font-extrabold mt-0.5" style="color:{{ $card['text'] }};">{{ $card['count'] }}</p>
                    </a>
                @endforeach
            </div>

            {{-- Filter status + pencarian --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                    @php
                        $tabs = [
                            ''          => ['label' => 'Semua',     'count' => $counts['total']],
                            'menunggu'  => ['label' => 'Menunggu',  'count' => $counts['menunggu']],
                            'disetujui' => ['label' => 'Disetujui', 'count' => $counts['disetujui']],
                            'ditolak'   => ['label' => 'Ditolak',   'count' => $counts['ditolak']],
                        ];
                    @endphp
                    @foreach ($tabs as $key => $tab)
                        <a href="{{ route('pic.shift-swaps.index', array_filter(['status' => $key ?: null, 'q' => $q ?: null], fn ($v) => $v !== null)) }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold transition-all border"
                            style="{{ $status === $key ? 'background-color:#E2231A;color:#fff;border-color:#E2231A;' : 'background-color:#fff;color:#62656F;border-color:#D2D3D6;' }}">
                            {{ $tab['label'] }}
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full text-[10px] font-black"
                                  style="{{ $status === $key ? 'background-color:rgba(255,255,255,0.25);color:#fff;' : 'background-color:#F6F6F7;color:#62656F;' }}">
                                {{ $tab['count'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
                <form method="GET" action="{{ route('pic.shift-swaps.index') }}" class="flex items-center gap-2">
                    @if ($status !== '')
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama agent..."
                           class="input-field py-2 w-full sm:w-56">
                    <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-bold text-white transition-all whitespace-nowrap"
                        style="background-color:#E2231A;"
                        onmouseover="this.style.background='#C01A12';"
                        onmouseout="this.style.background='#E2231A';">Cari</button>
                    @if ($q !== '' || $status !== '')
                        <a href="{{ route('pic.shift-swaps.index') }}"
                            class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors whitespace-nowrap"
                            style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                            onmouseover="this.style.background='#F6F6F7';"
                            onmouseout="this.style.background='#fff';">Reset</a>
                    @endif
                </form>
            </div>

            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                    <h2 class="text-sm font-bold" style="color:#1F2126;">Daftar Permintaan Tukar Jadwal</h2>
                </div>

                <div class="divide-y" style="border-color:#E9E9EB;">
                    @forelse($swaps as $swap)
                        <div class="p-6 transition-colors hover:bg-ink-50" style="background-color:#ffffff;"
                             onmouseover="this.style.background='#F6F6F7';"
                             onmouseout="this.style.background='#ffffff';">
                            <div class="flex flex-col md:flex-row items-start justify-between gap-6">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 flex-1">
                                    {{-- Agent A (Pengaju) --}}
                                    <div class="p-4 rounded-xl" style="background-color:#FFF1F0;border:1px solid #FFE0DD;">
                                        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#C01A12;">Pihak 1 (Pengaju)</p>
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="font-bold text-sm" style="color:#1F2126;">{{ $swap->requester->nama_agent }}</p>
                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" style="background-color:#ffffff;color:#62656F;border:1px solid #D2D3D6;">
                                                Level {{ $swap->requester->level }}
                                            </span>
                                        </div>
                                        <p class="text-xs leading-relaxed" style="color:#3F4249;">
                                            Melepaskan: <strong style="color:#1F2126;">{{ \Carbon\Carbon::parse($swap->requesterSchedule->tanggal)->translatedFormat('d M Y') }}</strong><br>
                                            Shift: <strong style="color:#1F2126;">{{ $swap->requesterSchedule->shift->kode_shift }}</strong> ({{ $swap->requesterSchedule->section->nama_section }})
                                        </p>
                                    </div>

                                    {{-- Agent B (Rekan) --}}
                                    <div class="p-4 rounded-xl" style="background-color:#F6F6F7;border:1px solid #E9E9EB;">
                                        <p class="text-[10px] font-bold uppercase tracking-wider mb-2" style="color:#62656F;">Pihak 2 (Rekan)</p>
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="font-bold text-sm" style="color:#1F2126;">{{ $swap->responder->nama_agent }}</p>
                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" style="background-color:#ffffff;color:#62656F;border:1px solid #D2D3D6;">
                                                Level {{ $swap->responder->level }}
                                            </span>
                                        </div>
                                        <p class="text-xs leading-relaxed" style="color:#3F4249;">
                                            Melepaskan: <strong style="color:#1F2126;">{{ \Carbon\Carbon::parse($swap->responderSchedule->tanggal)->translatedFormat('d M Y') }}</strong><br>
                                            Shift: <strong style="color:#1F2126;">{{ $swap->responderSchedule->shift->kode_shift }}</strong> ({{ $swap->responderSchedule->section->nama_section }})
                                        </p>
                                    </div>
                                </div>

                                {{-- Status & Actions --}}
                                <div class="md:w-48 shrink-0 flex flex-col md:items-end">
                                    @include('partials.shift-swap-status', ['status' => $swap->status])

                                    <p class="text-[10px] font-semibold mt-2 mb-3" style="color:#AEB0B6;">
                                        Diajukan {{ $swap->created_at->diffForHumans() }}
                                    </p>

                                    @if($swap->status === 'menunggu_pic' && !empty($swap->sc5_details))
                                        <div class="text-left md:text-right text-[10px] p-2 rounded-lg mb-3 leading-relaxed w-full"
                                             style="background-color:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">
                                            <strong style="color:#B45309;">Peringatan SC5:</strong>
                                            @foreach ($swap->sc5_details as $sc5)
                                                <div class="mt-0.5">
                                                    {{ $sc5['nama'] }}: istirahat hanya {{ $sc5['jam'] }} jam
                                                    (hari {{ $sc5['arah'] === 'sebelumnya' ? 'sebelum' : 'sesudah' }}nya)
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($swap->status === 'menunggu_pic')
                                        <div x-data="{ open: false, approvalAction: 'disetujui_pic', approvalCatatan: '' }">
                                            <button @click="open = true"
                                                    class="px-4 py-2 text-white text-xs font-bold rounded-lg transition-colors w-full md:w-auto"
                                                    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                                    onmouseover="this.style.background='#C01A12';"
                                                    onmouseout="this.style.background='#E2231A';">
                                                Proses Keputusan
                                            </button>

                                            {{-- Modal Proses --}}
                                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 text-left">
                                                <div @click="open = false" class="fixed inset-0 transition-opacity"
                                                     style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                                                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                                                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

                                                <div @click.outside="open = false"
                                                     class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-lift"
                                                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
                                                     x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                                                    <h3 class="text-xl font-extrabold mb-4" style="color:#1F2126;">Proses Pertukaran Jadwal</h3>
                                                    <form action="{{ route('pic.shift-swaps.update-approval', $swap) }}" method="POST">
                                                        @csrf
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Keputusan</label>
                                                                <select name="status" x-model="approvalAction" required class="input-field">
                                                                    <option value="disetujui_pic">Setujui Pertukaran</option>
                                                                    <option value="ditolak_pic">Tolak Pertukaran</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Catatan (opsional)</label>
                                                                <textarea name="catatan_pic" x-model="approvalCatatan" rows="3" class="input-field" placeholder="Tambahkan catatan jika perlu..."></textarea>
                                                            </div>
                                                            <div class="flex justify-end gap-3 pt-4">
                                                                <button type="button" @click="open = false"
                                                                        class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
                                                                        style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                                                                        onmouseover="this.style.background='#F6F6F7';"
                                                                        onmouseout="this.style.background='#fff';">
                                                                    Batal
                                                                </button>
                                                                <button type="submit"
                                                                        class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                                                                        :style="approvalAction === 'disetujui_pic' ? 'background-color:#10B981;' : 'background-color:#EF4444;'"
                                                                        x-bind:style="approvalAction === 'disetujui_pic' ? 'background-color:#10B981;' : 'background-color:#EF4444;'">
                                                                    <span x-text="approvalAction === 'disetujui_pic' ? 'Simpan Keputusan' : 'Tolak'"></span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            </div>
                            <div class="mt-4 pt-4 border-t" style="border-color:#E9E9EB;">
                                <p class="text-xs" style="color:#62656F;"><strong style="color:#3F4249;">Alasan Pengajuan:</strong> {{ $swap->alasan }}</p>
                                @if($swap->catatan_pic)
                                    <p class="text-xs mt-1" style="color:#DC2626;"><strong>Catatan Anda:</strong> {{ $swap->catatan_pic }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold" style="color:#62656F;">
                                @if ($q !== '' || $status !== '')
                                    Tidak ada permintaan yang cocok.
                                @else
                                    Tidak ada pengajuan tukar jadwal.
                                @endif
                            </p>
                        </div>
                    @endforelse
                </div>

                @if($swaps->hasPages())
                    <div class="px-6 py-4 border-t" style="border-color:#E9E9EB;">
                        {{ $swaps->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
