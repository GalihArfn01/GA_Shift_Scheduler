<x-app-layout>
    <x-slot name="header">Off Request Saya</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="mb-6">
                <h1 class="page-title">Off Request Saya</h1>
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

            @if (session('error'))
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FEE2E2;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @if (!$agent)
                <div class="flex items-center gap-2 p-4 mb-4 rounded-xl text-sm font-semibold"
                     style="background-color:#FFFBEB;color:#D97706;border:1px solid #FDE68A;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Akun kamu belum terhubung dengan data Agent. Hubungi PIC untuk menyelesaikan masalah ini.
                </div>
            @else
                <div x-data="{
                    activeTab: 'form-request',
                    createForm: { tanggal: [''] },
                    prioritasForm: { tanggal: '', keterangan: '' },
                    addTanggal() { this.createForm.tanggal.push(''); },
                    removeTanggal(index) {
                        if (this.createForm.tanggal.length > 1) {
                            this.createForm.tanggal.splice(index, 1);
                        }
                    }
                }">
                    {{-- Tab Switcher --}}
                    <div class="card overflow-hidden">
                        <div class="flex border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                            <button @click="activeTab = 'form-request'"
                                :class="activeTab === 'form-request' ? 'border-brand-500 text-brand-700 bg-white' : 'border-transparent text-ink-500 hover:text-ink-900 hover:bg-ink-50'"
                                class="flex-1 px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                                :style="activeTab === 'form-request' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;'">
                                Form Request Reguler
                            </button>
                            <button @click="activeTab = 'prioritas-tinggi'"
                                :class="activeTab === 'prioritas-tinggi' ? 'border-brand-500 text-brand-700 bg-white' : 'border-transparent text-ink-500 hover:text-ink-900 hover:bg-ink-50'"
                                class="flex-1 px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                                :style="activeTab === 'prioritas-tinggi' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;'">
                                Prioritas Tinggi
                            </button>
                        </div>

                        {{-- ===== TAB: FORM REQUEST ===== --}}
                        <div x-show="activeTab === 'form-request'" class="p-6 space-y-6">
                            <div class="flex items-start gap-3 p-4 rounded-xl" style="background-color:#F6F6F7;">
                                <svg class="w-5 h-5 shrink-0 mt-0.5" style="color:#62656F;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-xs leading-relaxed font-semibold" style="color:#3F4249;">
                                    Ajukan off tanpa perlu alasan. Kuota terbatas 3 slot/hari per kelompok section,
                                    siapa cepat dapat. Bisa pilih beberapa tanggal sekaligus.
                                </p>
                            </div>

                            @if (!$window)
                                <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                                     style="background-color:#F6F6F7;color:#3F4249;border:1px solid #E9E9EB;">
                                    <svg class="w-5 h-5 shrink-0" style="color:#82858E;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Form Request sedang tidak dibuka. Pantau Dashboard untuk info pembukaan berikutnya.
                                </div>
                            @endif

                            {{-- Tabel Preview Slot --}}
                            <div class="space-y-5">
                                @foreach (['inbound_informasi' => 'Inbound / Informasi', 'website_chat' => 'Website / Chat'] as $key => $label)
                                    <div>
                                        <p class="text-sm font-extrabold mb-3 text-center" style="color:#1F2126;">{{ $label }}</p>
                                        <div class="grid grid-cols-7 gap-2">
                                            @foreach ($slotPreview[$key] as $kolom)
                                                <div class="relative">
                                                    <p class="text-[10px] font-bold text-center mb-1.5" style="color:#82858E;">{{ $kolom['tanggal_label'] }}</p>
                                                    <div class="border rounded-lg overflow-hidden relative flex flex-col h-[100px] shadow-soft"
                                                         style="border-color:#E9E9EB;background-color:#ffffff;">
                                                        @if (!$kolom['aktif'])
                                                            <div class="absolute inset-0 flex flex-col items-center justify-center p-1 z-10"
                                                                 style="background-color:rgba(31,33,38,0.85);">
                                                                <svg class="w-4 h-4 mb-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                                </svg>
                                                                <span class="text-[10px] font-bold text-white uppercase tracking-wider text-center leading-tight">Tutup</span>
                                                            </div>
                                                        @else
                                                            @for ($s = 0; $s < 3; $s++)
                                                                <div class="flex-1 flex items-center justify-center text-[10px] font-bold border-b last:border-b-0 px-1 text-center leading-tight truncate transition-colors"
                                                                     style="{{ $kolom['pengisi']->has($s) ? 'background-color:#FFF1F0;color:#C01A12;' : 'color:#AEB0B6;border-color:#E9E9EB;' }}">
                                                                    {{ $kolom['pengisi']->get($s, '-') }}
                                                                </div>
                                                            @endfor
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t pt-6 mt-6" style="border-color:#E9E9EB;">
                                <form action="{{ route('agent.off-requests.form-request.store') }}" method="POST" class="space-y-4">
                                    @csrf

                                    <input type="hidden" name="kelompok_section" value="{{ $kelompokSaya }}">
                                    <p class="text-xs font-semibold" style="color:#62656F;">
                                        Kelompok kamu: <span class="font-bold text-black">{{ $kelompokSaya === 'inbound_informasi' ? 'Inbound / Informasi' : 'Website / Chat' }}</span>
                                        (otomatis sesuai level)
                                    </p>

                                    <div>
                                        <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Pilih Tanggal Libur</label>
                                        <div class="space-y-2">
                                            <template x-for="(tgl, index) in createForm.tanggal" :key="index">
                                                <div class="flex items-center gap-2">
                                                    <input type="date" x-bind:name="`tanggal[${index}]`" x-model="createForm.tanggal[index]" required class="input-field flex-1" />
                                                    <button type="button" x-show="createForm.tanggal.length > 1" @click="removeTanggal(index)"
                                                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors"
                                                        style="color:#DC2626;background-color:#FEF2F2;"
                                                        onmouseover="this.style.background='#FEE2E2';"
                                                        onmouseout="this.style.background='#FEF2F2';" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" @click="addTanggal()"
                                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold transition-colors"
                                            style="color:#E2231A;"
                                            onmouseover="this.style.color='#C01A12';"
                                            onmouseout="this.style.color='#E2231A';">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Tambah Tanggal
                                        </button>
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit"
                                            class="px-6 py-3 rounded-lg text-sm font-bold text-white transition-all w-full sm:w-auto"
                                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                            onmouseover="this.style.background='#C01A12';"
                                            onmouseout="this.style.background='#E2231A';">
                                            Ajukan Form Request
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="border-t pt-6" style="border-color:#E9E9EB;">
                                <h4 class="text-base font-extrabold mb-4" style="color:#1F2126;">Riwayat Form Request</h4>

                                @if ($formRequests->isEmpty())
                                    <div class="p-8 text-center border-dashed rounded-xl" style="border-color:#D2D3D6;border-width:2px;">
                                        <p class="text-sm font-semibold" style="color:#82858E;">Belum ada Form Request yang diajukan.</p>
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        @foreach ($formRequests as $item)
                                            <div class="flex items-center justify-between px-5 py-4 rounded-xl transition-colors hover:bg-ink-50"
                                                 style="background-color:#F6F6F7;border:1px solid #E9E9EB;">
                                                <div>
                                                    <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                                    </p>
                                                    <p class="text-xs font-semibold mt-1" style="color:#62656F;">
                                                        {{ $item->kelompok_section === 'inbound_informasi' ? 'Inbound / Informasi' : 'Website / Chat' }}
                                                    </p>
                                                </div>
                                                <form action="{{ route('agent.off-requests.form-request.destroy', $item) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                        style="color:#AEB0B6;background-color:#ffffff;border:1px solid #D2D3D6;"
                                                        onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#F87171';"
                                                        onmouseout="this.style.background='#ffffff';this.style.color='#AEB0B6';this.style.borderColor='#D2D3D6';"
                                                        title="Hapus Pengajuan">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ===== TAB: PRIORITAS TINGGI ===== --}}
                        <div x-show="activeTab === 'prioritas-tinggi'" x-cloak class="p-6 space-y-6">
                            <div class="flex items-start gap-3 p-4 rounded-xl" style="background-color:#FFFBEB;border:1px solid #FDE68A;">
                                <svg class="w-5 h-5 shrink-0 mt-0.5" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-xs leading-relaxed font-semibold" style="color:#B45309;">
                                    Untuk kebutuhan mendesak. Wajib menyertakan alasan yang jelas dan logis.
                                    Akan ditinjau dan diputuskan oleh PIC.
                                </p>
                            </div>

                            <form action="{{ route('agent.off-requests.prioritas-tinggi.store') }}" method="POST" class="space-y-4">
                                @csrf

                                <div>
                                    <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Tanggal</label>
                                    <input type="date" name="tanggal" x-model="prioritasForm.tanggal" required class="input-field" />
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Alasan Pengajuan</label>
                                    <textarea name="keterangan" x-model="prioritasForm.keterangan" required rows="3" minlength="10" maxlength="500"
                                        placeholder="Jelaskan alasan dengan jelas dan logis..."
                                        class="input-field"></textarea>
                                </div>

                                <div class="pt-2">
                                    <button type="submit"
                                        class="px-6 py-3 rounded-lg text-sm font-bold text-white transition-all w-full sm:w-auto"
                                        style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                        onmouseover="this.style.background='#C01A12';"
                                        onmouseout="this.style.background='#E2231A';">
                                        Ajukan Prioritas Tinggi
                                    </button>
                                </div>
                            </form>

                            <div class="border-t pt-6" style="border-color:#E9E9EB;">
                                <h4 class="text-base font-extrabold mb-4" style="color:#1F2126;">Riwayat Pengajuan Prioritas</h4>

                                @if ($prioritasTinggi->isEmpty())
                                    <div class="p-8 text-center border-dashed rounded-xl" style="border-color:#D2D3D6;border-width:2px;">
                                        <p class="text-sm font-semibold" style="color:#82858E;">Belum ada pengajuan prioritas tinggi.</p>
                                    </div>
                                @else
                                    <div class="space-y-4">
                                        @foreach ($prioritasTinggi as $item)
                                            <div class="p-5 rounded-xl transition-colors hover:bg-ink-50"
                                                 style="background-color:#F6F6F7;border:1px solid #E9E9EB;">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                                                    <p class="text-sm font-extrabold" style="color:#1F2126;">
                                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                                    </p>
                                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider"
                                                        style="{{ match($item->status_approval) {
                                                            'diterima' => 'background-color:#ECFDF5;color:#047857;',
                                                            'ditolak' => 'background-color:#FEF2F2;color:#B91C1C;',
                                                            default => 'background-color:#FFFBEB;color:#D97706;',
                                                        } }}">
                                                        <span class="w-1.5 h-1.5 rounded-full"
                                                            style="{{ match($item->status_approval) {
                                                                'diterima' => 'background-color:#059669;',
                                                                'ditolak' => 'background-color:#DC2626;',
                                                                default => 'background-color:#D97706;',
                                                            } }}"></span>
                                                        {{ ucfirst($item->status_approval) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm font-semibold mb-3" style="color:#3F4249;">{{ $item->keterangan }}</p>

                                                @if ($item->status_approval !== 'pending')
                                                    <div class="mt-3 pt-3 border-t" style="border-color:#D2D3D6;">
                                                        <p class="text-xs font-semibold" style="color:#82858E;">
                                                            {{ $item->status_approval === 'diterima' ? 'Disetujui' : 'Ditolak' }} PIC
                                                            pada {{ $item->approved_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                                                        </p>
                                                        @if ($item->catatan_pic)
                                                            <div class="mt-2 p-3 rounded-lg" style="background-color:#ffffff;border:1px solid #E9E9EB;">
                                                                <p class="text-xs font-semibold italic" style="color:#3F4249;">
                                                                    "{{ $item->catatan_pic }}"
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
