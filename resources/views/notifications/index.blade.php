<x-app-layout>
    <x-slot name="header">Notifikasi</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
                <div>
                    <h1 class="page-title">Notifikasi</h1>
                </div>
                @if($unreadCount > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold transition-colors"
                            style="color:#E2231A;border:1px solid #F1C1BF;background:#FFF6F5;"
                            onmouseover="this.style.background='#FFEDEB';"
                            onmouseout="this.style.background='#FFF6F5';">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
            </div>

            {{-- Kartu ringkasan (sekaligus filter) --}}
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('notifications.index') }}"
                    class="rounded-xl p-4 flex items-center gap-3 transition-all"
                    style="background:#ffffff;border:1px solid {{ $filter === 'semua' ? '#E2231A' : '#E9E9EB' }};{{ $filter === 'semua' ? 'box-shadow:0 0 0 2px rgba(226,35,26,0.15);' : '' }}"
                    onmouseover="this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.transform='';">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background:#FFF1F0;color:#E2231A;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Semua Notifikasi</p>
                        <p class="text-xl font-extrabold leading-tight" style="color:#1F2126;">{{ $totalCount }}</p>
                    </div>
                </a>

                <a href="{{ route('notifications.index', ['filter' => 'belum_dibaca']) }}"
                    class="rounded-xl p-4 flex items-center gap-3 transition-all"
                    style="background:#ffffff;border:1px solid {{ $filter === 'belum_dibaca' ? '#E2231A' : '#E9E9EB' }};{{ $filter === 'belum_dibaca' ? 'box-shadow:0 0 0 2px rgba(226,35,26,0.15);' : '' }}"
                    onmouseover="this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.transform='';">
                    <div class="relative w-10 h-10 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                        style="{{ $filter === 'belum_dibaca' ? 'background:#E2231A;color:#fff;' : 'background:#FFF1F0;color:#E2231A;' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2" style="background:#E2231A;border-color:#fff;"></span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Belum Dibaca</p>
                        <p class="text-xl font-extrabold leading-tight" style="color:#1F2126;">{{ $unreadCount }}</p>
                    </div>
                </a>
            </div>

            {{-- List notifikasi, dikelompokkan per tanggal --}}
            @forelse ($groups as $group => $items)
                <div class="card overflow-hidden" style="border-color:#E9E9EB;">
                    <div class="flex items-center justify-between px-5 py-2.5" style="background-color:#FAFAFB;border-bottom:1px solid #E9E9EB;">
                        <span class="text-[11px] font-black uppercase tracking-wider" style="color:#82858E;">{{ $group }}</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full" style="background:#EDEDEF;color:#82858E;">{{ count($items) }}</span>
                    </div>

                    <div class="divide-y" style="border-color:#E9E9EB;">
                        @foreach ($items as $notif)
                            @php
                                $data   = $notif->data;
                                $type   = $data['type'] ?? '';
                                $icon   = $data['icon'] ?? 'bell';
                                $isRead = !is_null($notif->read_at);

                                // Kategori (dari tipe notifikasi) → label & warna
                                if (str_starts_with($type, 'off_request')) {
                                    $category      = 'off_request';
                                    $categoryLabel = 'Off Request';
                                } elseif (str_starts_with($type, 'tukar_jadwal')) {
                                    $category      = 'tukar_jadwal';
                                    $categoryLabel = 'Tukar Jadwal';
                                } elseif (str_starts_with($type, 'jadwal')) {
                                    $category      = 'jadwal';
                                    $categoryLabel = 'Jadwal';
                                } else {
                                    $category      = 'sistem';
                                    $categoryLabel = 'Sistem';
                                }

                                $categoryColor = match($category) {
                                    'off_request'  => '#D97706',
                                    'tukar_jadwal' => '#2563EB',
                                    'jadwal'       => '#E2231A',
                                    default        => '#62656F',
                                };
                                $categoryBg = match($category) {
                                    'off_request'  => '#FFFBEB',
                                    'tukar_jadwal' => '#EFF6FF',
                                    'jadwal'       => '#FFF1F0',
                                    default        => '#F6F6F7',
                                };

                                // Warna ikon: check/x tetap semantik, sisanya mengikuti kategori
                                $iconColor = match($icon) {
                                    'check'    => '#059669',
                                    'x'        => '#DC2626',
                                    'calendar' => $categoryColor,
                                    'inbox'    => $category === 'off_request' ? '#D97706' : '#2563EB',
                                    'swap'     => '#2563EB',
                                    default    => '#62656F',
                                };
                                $iconBg = match($icon) {
                                    'check'    => '#ECFDF5',
                                    'x'        => '#FEF2F2',
                                    'calendar' => '#FFF1F0',
                                    'inbox'    => $category === 'off_request' ? '#FFFBEB' : '#EFF6FF',
                                    'swap'     => '#EFF6FF',
                                    default    => '#F6F6F7',
                                };

                                $icons = [
                                    'check'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                                    'x'        => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
                                    'calendar' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                                    'inbox'    => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>',
                                    'swap'     => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>',
                                    'bell'     => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                                ];
                                $iconSvg = $icons[$icon] ?? $icons['bell'];
                            @endphp

                            <div class="relative group transition-colors"
                                style="background-color:{{ !$isRead ? '#FFF9F8' : '#ffffff' }};"
                                onmouseover="this.style.background='#F6F6F7';"
                                onmouseout="this.style.background='{{ !$isRead ? '#FFF9F8' : '#ffffff' }}';">

                                @if(!$isRead)
                                    <span class="absolute left-0 top-0 bottom-0 w-[3px]" style="background-color:#E2231A;"></span>
                                @endif

                                {{-- Seluruh baris bisa diklik (buka notifikasi + tandai dibaca).
                                     Pakai form POST, bukan link GET, agar aksi ini tidak
                                     ter-trigger oleh prefetch browser. --}}
                                <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="absolute inset-0 z-0">
                                    @csrf
                                    <button type="submit" class="w-full h-full cursor-pointer" aria-label="Buka notifikasi: {{ $data['title'] ?? 'Notifikasi' }}"></button>
                                </form>

                                <div class="relative flex items-start gap-3.5 px-4 sm:px-6 py-4 pointer-events-none">
                                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" style="background-color:{{ $iconBg }};color:{{ $iconColor }};">
                                        {!! $iconSvg !!}
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold leading-snug" style="color:#1F2126;">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                        <p class="text-sm mt-0.5 leading-relaxed" style="color:#3F4249;">{{ $data['message'] ?? '' }}</p>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-2">
                                            <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded" style="color:{{ $categoryColor }};background-color:{{ $categoryBg }};">{{ $categoryLabel }}</span>
                                            <span class="text-xs font-semibold" style="color:#AEB0B6;" title="{{ $notif->created_at->translatedFormat('d M Y, H:i') }}">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tandai sudah dibaca (tanpa pindah halaman) --}}
                                @if(!$isRead)
                                    <form action="{{ route('notifications.read-one', $notif->id) }}" method="POST" class="absolute top-4 right-3 sm:right-5 z-10">
                                        @csrf
                                        <button type="submit" title="Tandai sudah dibaca"
                                            class="w-8 h-8 rounded-full flex items-center justify-center transition-all opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                                            style="background:#F6F6F7;color:#62656F;"
                                            onmouseover="this.style.background='#ECFDF5';this.style.color='#059669';"
                                            onmouseout="this.style.background='#F6F6F7';this.style.color='#62656F';">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card overflow-hidden" style="border-color:#E9E9EB;">
                    <div class="px-6 py-16 text-center">
                        @if($filter === 'belum_dibaca')
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full mb-4" style="background-color:#ECFDF5;color:#059669;">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-sm font-bold" style="color:#1F2126;">Semua sudah dibaca</p>
                            <p class="text-sm font-semibold mt-1" style="color:#AEB0B6;">Tidak ada notifikasi yang belum dibaca.</p>
                        @else
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full mb-4" style="background-color:#F6F6F7;color:#AEB0B6;">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <p class="text-sm font-bold" style="color:#1F2126;">Tidak ada notifikasi</p>
                            <p class="text-sm font-semibold mt-1" style="color:#AEB0B6;">Notifikasi baru akan muncul di sini.</p>
                        @endif
                    </div>
                </div>
            @endforelse

            {{-- Pagination --}}
            @if($notifications->hasPages())
                <div class="mt-4">{{ $notifications->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
