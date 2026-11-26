<x-app-layout>
    <x-slot name="header">Tukar Jadwal</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="page-title">Tukar Jadwal</h1>
                </div>
                <a href="{{ route('agent.shift-swaps.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                    onmouseover="this.style.background='#C01A12';"
                    onmouseout="this.style.background='#E2231A';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Ajukan Tukar Jadwal
                </a>
            </div>

            @if (session('success'))
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#ECFDF5;color:#047857;border:1px solid #D1FAE5;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div x-data="{ tab: 'masuk' }" class="card overflow-hidden">
                {{-- Tabs --}}
                <div class="flex border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                    <button @click="tab = 'masuk'"
                        :class="tab === 'masuk' ? 'border-brand-500 text-brand-700 bg-white' : 'border-transparent text-ink-500 hover:text-ink-900 hover:bg-ink-50'"
                        class="flex-1 px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                        :style="tab === 'masuk' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;'">
                        Permintaan Masuk
                        @if($masukMenunggu > 0)
                            <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black"
                                  style="background-color:#E2231A;color:#fff;">
                                {{ $masukMenunggu }}
                            </span>
                        @endif
                    </button>
                    <button @click="tab = 'diajukan'"
                        :class="tab === 'diajukan' ? 'border-brand-500 text-brand-700 bg-white' : 'border-transparent text-ink-500 hover:text-ink-900 hover:bg-ink-50'"
                        class="flex-1 px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                        :style="tab === 'diajukan' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;'">
                        Riwayat Pengajuan
                        @if($diajukanAktif > 0)
                            <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black"
                                  style="background-color:#E2231A;color:#fff;">
                                {{ $diajukanAktif }}
                            </span>
                        @endif
                    </button>
                </div>

                <div class="p-6">
                    {{-- Tab: Masuk --}}
                    <div x-show="tab === 'masuk'" class="space-y-4">
                        @forelse($swapsMasuk as $swap)
                            <div x-data="{ confirmAccept: false }" class="p-5 rounded-xl border transition-colors hover:bg-ink-50"
                                 style="background-color:#ffffff;border-color:#E9E9EB;">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold" style="color:#3F4249;">
                                            <span class="font-extrabold text-black">{{ $swap->requester->nama_agent }}</span> ingin menukar jadwal:
                                            <span class="text-xs font-semibold" style="color:#AEB0B6;">&middot; {{ $swap->created_at->diffForHumans() }}</span>
                                        </p>
                                        
                                        <div class="mt-3 p-4 rounded-xl flex flex-col sm:flex-row gap-4" style="background-color:#F6F6F7;">
                                            <div class="flex-1">
                                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#82858E;">Jadwal Anda</p>
                                                <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                    {{ \Carbon\Carbon::parse($swap->responderSchedule->tanggal)->translatedFormat('d M Y') }}
                                                </p>
                                                <span class="inline-block mt-1 px-2.5 py-1 rounded text-xs font-black" style="background-color:#ffffff;color:#E2231A;border:1px solid #D2D3D6;">
                                                    {{ $swap->responderSchedule->shift->kode_shift }}
                                                </span>
                                                @php $warnaResp = \App\Models\Section::warna($swap->responderSchedule->section->nama_section ?? ''); @endphp
                                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-bold" style="background-color:{{ $warnaResp['bg'] }};color:{{ $warnaResp['text'] }};">
                                                    {{ $swap->responderSchedule->section->nama_section }}
                                                </span>
                                            </div>
                                            
                                            <div class="hidden sm:flex items-center justify-center px-2">
                                                <svg class="w-5 h-5" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                </svg>
                                            </div>
                                            
                                            <div class="flex-1">
                                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#82858E;">Jadwal Dia</p>
                                                <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                    {{ \Carbon\Carbon::parse($swap->requesterSchedule->tanggal)->translatedFormat('d M Y') }}
                                                </p>
                                                <span class="inline-block mt-1 px-2.5 py-1 rounded text-xs font-black" style="background-color:#ffffff;color:#1F2126;border:1px solid #D2D3D6;">
                                                    {{ $swap->requesterSchedule->shift->kode_shift }}
                                                </span>
                                                @php $warnaReq = \App\Models\Section::warna($swap->requesterSchedule->section->nama_section ?? ''); @endphp
                                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-bold" style="background-color:{{ $warnaReq['bg'] }};color:{{ $warnaReq['text'] }};">
                                                    {{ $swap->requesterSchedule->section->nama_section }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 p-3 rounded-lg border-l-2" style="background-color:#FCFAF7;border-left-color:#D97706;">
                                            <p class="text-xs font-semibold italic" style="color:#62656F;">"{{ $swap->alasan }}"</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end gap-3 md:min-w-[150px]">
                                        @include('partials.shift-swap-status', ['status' => $swap->status])

                                        @if($swap->status === 'menunggu_rekan')
                                            <div class="flex flex-col w-full gap-2 mt-2">
                                                <button type="button" @click="confirmAccept = true"
                                                    class="w-full px-4 py-2 text-xs font-bold text-white rounded-lg transition-all"
                                                    style="background-color:#10B981;box-shadow:0 4px 14px -2px rgba(16,185,129,0.35);"
                                                    onmouseover="this.style.background='#059669';"
                                                    onmouseout="this.style.background='#10B981';">Setuju</button>
                                                <form action="{{ route('agent.shift-swaps.respond', $swap) }}" method="POST" class="w-full">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="w-full px-4 py-2 text-xs font-bold rounded-lg transition-colors"
                                                        style="background-color:#ffffff;color:#3F4249;border:1px solid #D2D3D6;"
                                                        onmouseover="this.style.background='#F6F6F7';"
                                                        onmouseout="this.style.background='#ffffff';">Tolak</button>
                                                </form>
                                            </div>

                                            {{-- Modal konfirmasi Setuju --}}
                                            <div x-show="confirmAccept" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                                <div @click="confirmAccept = false" class="fixed inset-0 transition-opacity"
                                                     style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"></div>
                                                <div @click.outside="confirmAccept = false"
                                                     class="relative w-full max-w-sm bg-white rounded-2xl p-6 shadow-lift">
                                                    <h3 class="text-lg font-extrabold mb-2" style="color:#1F2126;">Setujui Pertukaran Jadwal?</h3>
                                                    <p class="text-sm mb-5" style="color:#62656F;">
                                                        Anda akan menyetujui pertukaran dengan <strong style="color:#1F2126;">{{ $swap->requester->nama_agent }}</strong>.
                                                        Permintaan akan diteruskan ke persetujuan PIC.
                                                    </p>
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" @click="confirmAccept = false"
                                                            class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
                                                            style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                                                            onmouseover="this.style.background='#F6F6F7';"
                                                            onmouseout="this.style.background='#fff';">Batal</button>
                                                        <form action="{{ route('agent.shift-swaps.respond', $swap) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="action" value="accept">
                                                            <button type="submit"
                                                                class="px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                                                                style="background-color:#10B981;"
                                                                onmouseover="this.style.background='#059669';"
                                                                onmouseout="this.style.background='#10B981';">Ya, Setujui</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center border-dashed rounded-xl" style="border-color:#D2D3D6;border-width:2px;">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                    <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold" style="color:#82858E;">Belum ada permintaan masuk.</p>
                            </div>
                        @endforelse

                        @if ($swapsMasuk->hasPages())
                            <div class="pt-2">
                                {{ $swapsMasuk->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- Tab: Diajukan --}}
                    <div x-show="tab === 'diajukan'" x-cloak class="space-y-4">
                        @forelse($swapsDiajukan as $swap)
                            <div class="p-5 rounded-xl border transition-colors hover:bg-ink-50"
                                 style="background-color:#ffffff;border-color:#E9E9EB;">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold" style="color:#3F4249;">
                                            Anda mengajukan tukar jadwal dengan <span class="font-extrabold text-black">{{ $swap->responder->nama_agent }}</span>
                                            <span class="text-xs font-semibold" style="color:#AEB0B6;">&middot; {{ $swap->created_at->diffForHumans() }}</span>
                                        </p>
                                        
                                        <div class="mt-3 p-4 rounded-xl flex flex-col sm:flex-row gap-4" style="background-color:#FCFAF7;">
                                            <div class="flex-1">
                                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#82858E;">Jadwal Anda</p>
                                                <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                    {{ \Carbon\Carbon::parse($swap->requesterSchedule->tanggal)->translatedFormat('d M Y') }}
                                                </p>
                                                <span class="inline-block mt-1 px-2.5 py-1 rounded text-xs font-black" style="background-color:#ffffff;color:#E2231A;border:1px solid #D2D3D6;">
                                                    {{ $swap->requesterSchedule->shift->kode_shift }}
                                                </span>
                                                @php $warnaReq = \App\Models\Section::warna($swap->requesterSchedule->section->nama_section ?? ''); @endphp
                                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-bold" style="background-color:{{ $warnaReq['bg'] }};color:{{ $warnaReq['text'] }};">
                                                    {{ $swap->requesterSchedule->section->nama_section }}
                                                </span>
                                            </div>
                                            
                                            <div class="hidden sm:flex items-center justify-center px-2">
                                                <svg class="w-5 h-5" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                </svg>
                                            </div>
                                            
                                            <div class="flex-1">
                                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#82858E;">Jadwal Dia</p>
                                                <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                    {{ \Carbon\Carbon::parse($swap->responderSchedule->tanggal)->translatedFormat('d M Y') }}
                                                </p>
                                                <span class="inline-block mt-1 px-2.5 py-1 rounded text-xs font-black" style="background-color:#ffffff;color:#1F2126;border:1px solid #D2D3D6;">
                                                    {{ $swap->responderSchedule->shift->kode_shift }}
                                                </span>
                                                @php $warnaResp = \App\Models\Section::warna($swap->responderSchedule->section->nama_section ?? ''); @endphp
                                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-bold" style="background-color:{{ $warnaResp['bg'] }};color:{{ $warnaResp['text'] }};">
                                                    {{ $swap->responderSchedule->section->nama_section }}
                                                </span>
                                            </div>
                                        </div>

                                        @if($swap->catatan_pic)
                                            <div class="mt-4 p-3 rounded-lg border-l-2" style="background-color:#FEF2F2;border-left-color:#DC2626;">
                                                <p class="text-xs font-semibold" style="color:#B91C1C;">Catatan PIC: <span class="italic">"{{ $swap->catatan_pic }}"</span></p>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="flex flex-col items-end md:min-w-[150px]">
                                        @include('partials.shift-swap-status', ['status' => $swap->status])
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center border-dashed rounded-xl" style="border-color:#D2D3D6;border-width:2px;">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                    <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold" style="color:#82858E;">Anda belum pernah mengajukan tukar jadwal.</p>
                            </div>
                        @endforelse

                        @if ($swapsDiajukan->hasPages())
                            <div class="pt-2">
                                {{ $swapsDiajukan->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
