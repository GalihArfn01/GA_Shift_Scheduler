<x-app-layout>
    <x-slot name="header">Pengajuan Tukar Jadwal</x-slot>

    @php
        // JSON data jadwal sendiri untuk live preview (dihitung di @php supaya
        // @json/arrow-function di dalam JS tidak memecah compiler Blade).
        $mySchedulesJson = $mySchedules->map(fn ($s) => [
            'id'      => $s->id,
            'tanggal' => \Carbon\Carbon::parse($s->tanggal)->translatedFormat('l, d M Y'),
            'shift'   => $s->shift->kode_shift,
            'section' => $s->section->nama_section,
        ])->values()->toJson();
    @endphp

    <div class="py-8 animate-fade-in" x-data="swapForm()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('agent.shift-swaps.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-bold transition-colors mb-4"
                    style="color:#62656F;"
                    onmouseover="this.style.color='#1F2126';"
                    onmouseout="this.style.color='#62656F';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Tukar Jadwal
                </a>
                <h1 class="page-title">Pengajuan Tukar Jadwal</h1>
            </div>

            <div class="card p-8 max-w-2xl mx-auto">
                <form action="{{ route('agent.shift-swaps.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Pilih Jadwal Sendiri --}}
                    <div>
                        <label class="block text-sm font-bold mb-2" style="color:#1F2126;">
                            Pilih Jadwal Anda (yang ingin ditukar)
                        </label>
                        <select name="requester_schedule_id"
                                x-model="myScheduleId"
                                @change="onMyScheduleChange()"
                                required
                                class="input-field py-3 text-sm">
                            <option value="">-- Pilih Jadwal --</option>
                            @foreach($mySchedules as $sched)
                                <option value="{{ $sched->id }}">
                                    {{ \Carbon\Carbon::parse($sched->tanggal)->translatedFormat('l, d M Y') }}
                                    - Shift {{ $sched->shift->kode_shift }} ({{ $sched->section->nama_section }})
                                </option>
                            @endforeach
                        </select>
                        @error('requester_schedule_id')
                            <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Pilih Rekan yang Dituju (dynamic) --}}
                    <div>
                        <label class="block text-sm font-bold mb-2" style="color:#1F2126;">
                            Pilih Rekan yang Dituju
                        </label>

                        {{-- Loading responders --}}
                        <div x-show="loadingResponders" class="flex items-center gap-2 py-2.5">
                            <svg class="animate-spin w-4 h-4" style="color:#E2231A;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-xs font-semibold" style="color:#E2231A;">Memuat daftar rekan...</p>
                        </div>

                        {{-- Dropdown rekan eligible --}}
                        <div x-show="!loadingResponders">
                            <select name="responder_id"
                                    x-model="responderId"
                                    @change="onResponderChange()"
                                    required
                                    :disabled="!myScheduleId || loadingResponders"
                                    class="input-field py-3 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="">-- Pilih Rekan --</option>
                                <template x-for="r in eligibleResponders" :key="r.id">
                                    <option :value="r.id" x-text="r.nama + ' (Level ' + r.level + ')'"></option>
                                </template>
                            </select>

                            {{-- Tidak ada rekan eligible --}}
                            <div x-show="myScheduleId && !loadingResponders && eligibleResponders.length === 0"
                                 x-cloak
                                 class="mt-2 p-3 rounded-lg flex items-start gap-2"
                                 style="background-color:#FFFBEB;border:1px solid #FDE68A;">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-xs font-semibold" style="color:#B45309;">
                                    Tidak ada rekan yang memenuhi syarat untuk jadwal ini (harus section sama, periode sama, dan tidak bentrok).
                                </p>
                            </div>
                        </div>

                        @error('responder_id')
                            <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Pilih Jadwal Rekan --}}
                    <div>
                        <label class="block text-sm font-bold mb-2" style="color:#1F2126;">Pilih Jadwal Rekan</label>
                        <select name="responder_schedule_id"
                                x-model="responderScheduleId"
                                required
                                :disabled="!schedulesLoaded || responderSchedules.length === 0"
                                class="input-field py-3 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">-- Pilih Jadwal Rekan --</option>
                            <template x-for="(list, date) in groupedResponderSchedules()" :key="date">
                                <optgroup :label="date">
                                    <template x-for="s in list" :key="s.id">
                                        <option :value="s.id"
                                                x-text="'Shift ' + s.shift.kode_shift + ' (' + s.section.nama_section + ')'">
                                        </option>
                                    </template>
                                </optgroup>
                            </template>
                        </select>

                        <div x-show="schedulesLoaded && responderSchedules.length === 0"
                             x-cloak
                             class="mt-2 p-3 rounded-lg flex items-start gap-2"
                             style="background-color:#FFFBEB;border:1px solid #FDE68A;">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" style="color:#D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-xs font-semibold" style="color:#B45309;">Tidak ada jadwal rekan yang cocok/tersedia.</p>
                        </div>

                        <div x-show="isLoading" class="mt-2 flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" style="color:#E2231A;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-xs font-semibold" style="color:#E2231A;">Memuat jadwal...</p>
                        </div>

                        @error('responder_schedule_id')
                            <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Ringkasan Pertukaran (live preview) --}}
                    <div x-show="myPreview()" x-cloak class="rounded-xl border p-4" style="background-color:#FCFAF7;border-color:#E9E9EB;">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-3" style="color:#82858E;">Ringkasan Pertukaran</p>
                        <div class="flex flex-col sm:flex-row items-stretch gap-3">
                            <div class="flex-1 rounded-lg p-3" style="background-color:#FFF1F0;border:1px solid #FFE0DD;">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#C01A12;">Anda lepas</p>
                                <p class="text-sm font-extrabold" style="color:#1F2126;" x-text="myPreview() ? myPreview().tanggal : '—'"></p>
                                <p class="text-xs font-semibold mt-1" style="color:#3F4249;">
                                    <span x-text="myPreview() ? 'Shift ' + myPreview().shift : '—'"></span>
                                    <span x-show="myPreview()" class="font-bold"
                                          :style="'color:' + (myPreview() ? sectionColor(myPreview().section) : '#62656F')"
                                          x-text="myPreview() ? ' · ' + myPreview().section : ''"></span>
                                </p>
                            </div>
                            <div class="flex items-center justify-center">
                                <svg class="w-5 h-5" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </div>
                            <div class="flex-1 rounded-lg p-3" style="background-color:#F0FDF4;border:1px solid #BBF7D0;">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:#16A34A;">Anda dapat</p>
                                <p class="text-sm font-extrabold" style="color:#1F2126;"
                                    x-text="responderPreview() ? formatDate(responderPreview().tanggal) : 'Pilih jadwal rekan'"></p>
                                <p class="text-xs font-semibold mt-1" style="color:#3F4249;">
                                    <span x-text="responderPreview() ? 'Shift ' + responderPreview().shift.kode_shift : '—'"></span>
                                    <span x-show="responderPreview()" class="font-bold"
                                          :style="'color:' + (responderPreview() ? sectionColor(responderPreview().section.nama_section) : '#62656F')"
                                          x-text="responderPreview() ? ' · ' + responderPreview().section.nama_section : ''"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Alasan --}}
                    <div>
                        <label class="block text-sm font-bold mb-2" style="color:#1F2126;">Alasan Pertukaran</label>
                        <div class="flex flex-wrap gap-2 mb-2.5">
                            <template x-for="c in alasanCepat" :key="c">
                                <button type="button" @click="alasan = c"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border"
                                    :style="alasan === c ? 'background-color:#FFF1F0;color:#C01A12;border-color:#FFC5C0;' : 'background-color:#fff;color:#62656F;border-color:#D2D3D6;'"
                                    x-text="c"></button>
                            </template>
                        </div>
                        <textarea name="alasan" required rows="4" class="input-field" x-model="alasan"
                                  placeholder="Jelaskan alasan singkat Anda..."></textarea>
                        @error('alasan')
                            <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ $message }}</p>
                        @enderror
                    </div>

                    @if(session('error'))
                        <div class="flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                             style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FEE2E2;">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="pt-6 border-t" style="border-color:#E9E9EB;">
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-3 rounded-lg text-sm font-bold text-white transition-all flex items-center justify-center gap-2"
                                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                onmouseover="this.style.background='#C01A12';"
                                onmouseout="this.style.background='#E2231A';">
                            Kirim Permintaan
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Didefinisikan di inline script agar tersedia sebelum Alpine.initTree()
        // dipanggil oleh Swup, menghindari ReferenceError "swapForm is not defined".
        window.swapForm = function () {
            return {
                myScheduleId:        '',
                responderId:         '',
                responderScheduleId: '',
                eligibleResponders:  [],
                responderSchedules:  [],
                loadingResponders:   false,
                isLoading:           false,
                schedulesLoaded:     false,
                alasan:              '',
                alasanCepat:         ['Urusan keluarga', 'Kesehatan', 'Acara pribadi', 'Keperluan mendadak'],
                mySchedules:         {!! $mySchedulesJson !!},

                // Step 1: jadwal sendiri berubah → reset semua, fetch eligible responders
                async onMyScheduleChange() {
                    this.responderId         = '';
                    this.responderScheduleId = '';
                    this.eligibleResponders  = [];
                    this.responderSchedules  = [];
                    this.schedulesLoaded     = false;

                    if (!this.myScheduleId) return;

                    this.loadingResponders = true;
                    try {
                        const res = await fetch(
                            `{{ route('agent.shift-swaps.get-eligible-responders') }}?my_schedule_id=${this.myScheduleId}`
                        );
                        if (res.ok) {
                            this.eligibleResponders = await res.json();
                        }
                    } catch (e) {
                        console.error('Gagal memuat daftar rekan:', e);
                    } finally {
                        this.loadingResponders = false;
                    }
                },

                // Step 2: rekan berubah → fetch jadwal eligible milik rekan tersebut
                async onResponderChange() {
                    this.responderScheduleId = '';
                    this.responderSchedules  = [];
                    this.schedulesLoaded     = false;

                    if (!this.myScheduleId || !this.responderId) return;

                    this.isLoading = true;
                    try {
                        const res = await fetch(
                            `{{ route('agent.shift-swaps.get-responder-schedules') }}?my_schedule_id=${this.myScheduleId}&responder_id=${this.responderId}`
                        );
                        if (res.ok) {
                            this.responderSchedules = await res.json();
                            this.schedulesLoaded    = true;
                        }
                    } catch (e) {
                        console.error('Gagal memuat jadwal rekan:', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                formatDate(dateString) {
                    return new Date(dateString).toLocaleDateString('id-ID', {
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                    });
                },

                formatDateShort(dateString) {
                    return new Date(dateString).toLocaleDateString('id-ID', {
                        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
                    });
                },

                // Preview pertukaran: jadwal sendiri yang dipilih
                myPreview() {
                    return this.mySchedules.find(s => String(s.id) === String(this.myScheduleId)) || null;
                },

                // Preview pertukaran: jadwal rekan yang dipilih
                responderPreview() {
                    return this.responderSchedules.find(s => String(s.id) === String(this.responderScheduleId)) || null;
                },

                // Jadwal rekan dikelompokkan per tanggal (optgroup)
                groupedResponderSchedules() {
                    const groups = {};
                    this.responderSchedules.forEach(s => {
                        const d = this.formatDateShort(s.tanggal);
                        (groups[d] = groups[d] || []).push(s);
                    });
                    return groups;
                },

                sectionColor(name) {
                    const map = { Chat: '#C01A12', Website: '#D97706', Informasi: '#1D4ED8', Inbound: '#4F46E5' };
                    return map[name] || '#62656F';
                },
            };
        };
    </script>
</x-app-layout>
