<x-app-layout>
    <x-slot name="header">Ubah Grid Jadwal</x-slot>

    <script>
        function gridEditor() {
            return {
                agents: @json($agentsJson),
                hariList: @json($hariList),
                grid: @json($gridJson),
                dragging: null,
                loading: false,
                toast: { show: false, type: 'success', message: '' },

                // Auto-scroll saat drag: supaya bisa tukar cell yang lokasinya
                // jauh (mis. agent nomor 1 dengan agent paling bawah) tanpa
                // harus lepas drag dulu buat scroll manual.
                pointer: { x: 0, y: 0 },
                autoScrollRAF: null,

                gridDataUrl: '{{ route("pic.schedule-grid.data", $period) }}',
                swapUrl: '{{ route("pic.schedule-grid.swap", $period) }}',
                csrfToken: '{{ csrf_token() }}',

                init() {},

                getCell(agentId, hariIndex) {
                    if (!this.grid[agentId]) return null;
                    return this.grid[agentId][hariIndex] || null;
                },

                isValidDropTarget(agentId, hariIndex) {
                    if (!this.dragging) return false;
                    if (this.dragging.sourceAgentId == agentId && this.dragging.sourceHariIndex == hariIndex) return false;
                    const cell = this.getCell(agentId, hariIndex);
                    if (!cell) return false;
                    const sourceCell = this.getCell(this.dragging.sourceAgentId, this.dragging.sourceHariIndex);
                    if (!sourceCell) return false;
                    if (sourceCell.status !== 'kerja' && cell.status !== 'kerja') return false;
                    return true;
                },

                onDragStart(event, agentId, hariIndex) {
                    const cell = this.getCell(agentId, hariIndex);
                    if (!cell) { event.preventDefault(); return; }

                    this.dragging = {
                        sourceId: cell.id,
                        sourceAgentId: agentId,
                        sourceHariIndex: hariIndex,
                        sourceStatus: cell.status,
                        targetAgentId: null,
                        targetHariIndex: null,
                    };

                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', cell.id);

                    this.pointer.x = event.clientX;
                    this.pointer.y = event.clientY;
                    this.startAutoScroll();
                },

                // Dipanggil terus-menerus selama drag berlangsung (event
                // dragover ikut bubble ke window), dipakai buat tahu posisi
                // kursor terkini tanpa peduli lagi di atas cell mana.
                onWindowDragOver(event) {
                    if (!this.dragging) return;
                    this.pointer.x = event.clientX;
                    this.pointer.y = event.clientY;
                },

                // Auto-scroll halaman (vertikal) & tabel grid (horizontal)
                // saat kursor mendekati tepi layar selagi membawa jadwal.
                startAutoScroll() {
                    const EDGE = 90;      // px dari tepi yang memicu auto-scroll
                    const MAX_SPEED = 24; // px per frame di kecepatan maksimum

                    const step = () => {
                        if (!this.dragging) { this.autoScrollRAF = null; return; }

                        const { x, y } = this.pointer;
                        const vw = window.innerWidth;
                        const vh = window.innerHeight;

                        // Vertikal: scroll halaman
                        if (y > 0 && y < EDGE) {
                            window.scrollBy(0, -Math.ceil((EDGE - y) / EDGE * MAX_SPEED));
                        } else if (y > vh - EDGE && y < vh) {
                            window.scrollBy(0, Math.ceil((y - (vh - EDGE)) / EDGE * MAX_SPEED));
                        }

                        // Horizontal: scroll wadah tabel (kolom hari)
                        const scroller = this.$refs.gridScroller;
                        if (scroller && x > 0 && x < vw) {
                            if (x < EDGE) {
                                scroller.scrollLeft -= Math.ceil((EDGE - x) / EDGE * MAX_SPEED);
                            } else if (x > vw - EDGE) {
                                scroller.scrollLeft += Math.ceil((x - (vw - EDGE)) / EDGE * MAX_SPEED);
                            }
                        }

                        this.autoScrollRAF = requestAnimationFrame(step);
                    };

                    this.autoScrollRAF = requestAnimationFrame(step);
                },

                stopAutoScroll() {
                    if (this.autoScrollRAF) {
                        cancelAnimationFrame(this.autoScrollRAF);
                        this.autoScrollRAF = null;
                    }
                },

                onDragOver(agentId, hariIndex) {
                    if (!this.dragging) return;
                    if (!this.isValidDropTarget(agentId, hariIndex)) return;
                    this.dragging.targetAgentId = agentId;
                    this.dragging.targetHariIndex = hariIndex;
                },

                onDrop(targetAgentId, targetHariIndex) {
                    if (!this.dragging) return;
                    if (!this.isValidDropTarget(targetAgentId, targetHariIndex)) { this.onDragEnd(); return; }
                    const targetCell = this.getCell(targetAgentId, targetHariIndex);
                    if (!targetCell || this.dragging.sourceId === targetCell.id) { this.onDragEnd(); return; }

                    // Peringatan ringan: kalau cell yang akan JADI kerja itu
                    // sebelumnya off request yang sudah disetujui, konfirmasi
                    // dulu -- supaya PIC tidak tanpa sadar membatalkan off
                    // request orang lewat drag cepat.
                    const sourceCell = this.getCell(this.dragging.sourceAgentId, this.dragging.sourceHariIndex);
                    const calonKerja = sourceCell.status === 'kerja' ? targetCell : sourceCell;
                    if (calonKerja.status !== 'kerja' && calonKerja.is_off_request) {
                        if (!confirm('Cell ini adalah off request yang sudah disetujui. Lanjutkan menugaskan kerja?')) {
                            this.onDragEnd();
                            return;
                        }
                    }

                    this.executeSwap(this.dragging.sourceId, targetCell.id);
                    this.onDragEnd();
                },

                onDragEnd() {
                    this.dragging = null;
                    this.stopAutoScroll();
                },

                async executeSwap(sourceId, targetId) {
                    this.loading = true;

                    try {
                        const response = await fetch(this.swapUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                source_schedule_id: sourceId,
                                target_schedule_id: targetId,
                            }),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.showToast('success', data.message || 'Jadwal berhasil ditukar.');
                            await this.refreshGrid();
                        } else {
                            this.showToast('error', data.error || 'Terjadi kesalahan saat menukar jadwal.');
                        }
                    } catch (err) {
                        this.showToast('error', 'Gagal menghubungi server. Silakan coba lagi.');
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshGrid() {
                    this.loading = true;

                    try {
                        const response = await fetch(this.gridDataUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                        });

                        const data = await response.json();

                        if (response.ok) {
                            this.agents = data.agents;
                            this.hariList = data.hariList;
                            this.grid = data.grid;
                        }
                    } catch (err) {
                        this.showToast('error', 'Gagal memuat ulang grid.');
                    } finally {
                        this.loading = false;
                    }
                },

                showToast(type, message) {
                    this.toast = { show: true, type, message };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 4000);
                },
            };
        }
    </script>

    <div class="py-8 animate-fade-in" x-data="gridEditor()" x-init="init()" @dragover.window="onWindowDragOver($event)">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Navigasi --}}
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <a href="{{ route('schedules.generate.run', $period) }}"
                    class="text-sm font-bold transition-colors inline-flex items-center gap-1.5"
                    style="color:#62656F;"
                    onmouseover="this.style.color='#1F2126';"
                    onmouseout="this.style.color='#62656F';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Generate
                </a>
                <div class="flex items-center gap-2">
                    <button @click="refreshGrid()"
                        :disabled="loading"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold transition-colors"
                        style="background-color:#ffffff;color:#3F4249;border:1px solid #D2D3D6;"
                        onmouseover="this.style.backgroundColor='#F6F6F7';"
                        onmouseout="this.style.backgroundColor='#ffffff';">
                        <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Muat Ulang
                    </button>

                    @if ($period->status !== 'final')
                    <form action="{{ route('schedules.finalize', $period) }}" method="POST"
                        onsubmit="return confirm('Setelah difinalisasi, jadwal tidak bisa di-generate ulang. Lanjutkan?');">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Finalisasi
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Info periode --}}
            <div class="card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="page-title">{{ $period->nama_periode }}</h3>
                    <p class="text-sm mt-1" style="color:#82858E;">
                        {{ \Carbon\Carbon::parse($period->tanggal_mulai)->translatedFormat('d M Y') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($period->tanggal_selesai)->translatedFormat('d M Y') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider"
                    style="{{ $period->status === 'final' ? 'background-color:#ECFDF5;color:#047857;' : 'background-color:#FFFBEB;color:#D97706;' }}">
                    <span class="w-1.5 h-1.5 rounded-full" style="background-color:{{ $period->status === 'final' ? '#10B981' : '#F59E0B' }};"></span>
                    {{ $period->status === 'final' ? 'FINAL' : 'DRAFT' }}
                </span>
            </div>

            {{-- Instruksi --}}
            <div class="flex items-start gap-3 p-4 rounded-xl" style="background-color:#F0F9FF;border:1px solid #BAE6FD;">
                <svg class="w-5 h-5 shrink-0 mt-0.5" style="color:#0369A1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-sm font-bold" style="color:#0369A1;">Cara Mengubah Jadwal</p>
                    <p class="text-xs mt-1 leading-relaxed" style="color:#0C4A6E;">
                        Geser (drag & drop) satu cell ke cell lain untuk menukar atau memindahkan jadwal.
                    </p>
                </div>
            </div>

            {{-- Toast notifikasi --}}
            <div x-show="toast.show" x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="fixed top-4 right-4 z-50 max-w-sm p-4 rounded-xl shadow-lg"
                :style="toast.type === 'success' ? 'background-color:#F0FDF4;border:1px solid #BBF7D0;' : 'background-color:#FEF2F2;border:1px solid #FECACA;'">
                <div class="flex items-start gap-3">
                    <svg x-show="toast.type === 'success'" class="w-5 h-5 shrink-0" style="color:#16A34A;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg x-show="toast.type === 'error'" class="w-5 h-5 shrink-0" style="color:#DC2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm font-semibold" :style="toast.type === 'success' ? 'color:#166534;' : 'color:#991B1B;'" x-text="toast.message"></p>
                </div>
            </div>

            {{-- Grid Mingguan --}}
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                    <h3 class="text-sm font-bold" style="color:#1F2126;">Grid Mingguan</h3>
                    <span class="text-xs font-semibold" style="color:#82858E;" x-text="loading ? 'Memuat...' : 'Geser cell untuk menukar'"></span>
                </div>

                <div class="overflow-x-auto" x-ref="gridScroller">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr style="border-bottom:1px solid #E9E9EB;background-color:#F6F6F7;">
                                <th class="px-5 py-3 sticky left-0 z-10" style="background-color:#F6F6F7;min-width:200px;">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Agent</span>
                                </th>
                                <template x-for="hari in hariList" :key="hari.index">
                                    <th class="px-5 py-3 text-center min-w-[110px]">
                                        <span class="text-xs font-bold" style="color:#3F4249;" x-text="hari.label"></span>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E9E9EB;">
                            <template x-for="agent in agents" :key="agent.id">
                                <tr class="transition-colors" style="background-color:#ffffff;"
                                    onmouseover="this.style.backgroundColor='#F6F6F7';"
                                    onmouseout="this.style.backgroundColor='#ffffff';">
                                    <td class="px-5 py-3 sticky left-0 z-10" style="background-color:inherit;">
                                        <div class="font-bold text-sm" style="color:#1F2126;" x-text="agent.nama"></div>
                                        <div class="text-xs mt-0.5" style="color:#82858E;" x-text="agent.level"></div>
                                    </td>
                                    <template x-for="hari in hariList" :key="hari.index">
                                        <td class="text-center px-3 py-2 relative"
                                            :data-agent-id="agent.id"
                                            :data-hari-index="hari.index">
                                            <template x-if="getCell(agent.id, hari.index) && getCell(agent.id, hari.index).status === 'kerja'">
                                                <div class="schedule-cell cursor-grab inline-flex flex-col items-center justify-center p-2 rounded-lg transition-all select-none"
                                                     style="background-color:#FCFAF7;border:1px solid #E9E9EB;width:100%;"
                                                     :class="{
                                                         'ring-2 ring-blue-400 ring-offset-1': dragging && dragging.sourceId === getCell(agent.id, hari.index).id,
                                                         'ring-2 ring-green-400 ring-offset-1 bg-green-50': dragging && isValidDropTarget(agent.id, hari.index),
                                                         'opacity-40': dragging && dragging.sourceId !== getCell(agent.id, hari.index).id && !isValidDropTarget(agent.id, hari.index)
                                                     }"
                                                     draggable="true"
                                                     @dragstart="onDragStart($event, agent.id, hari.index)"
                                                     @dragend="onDragEnd()"
                                                     @dragover.prevent="onDragOver(agent.id, hari.index)"
                                                     @drop.prevent="onDrop(agent.id, hari.index)">
                                                    <span class="font-extrabold text-sm" style="color:#E2231A;" x-text="getCell(agent.id, hari.index).shift_kode"></span>
                                                    <span class="text-[10px] font-semibold mt-0.5" style="color:#62656F;" x-text="getCell(agent.id, hari.index).section_name"></span>
                                                </div>
                                            </template>
                                            <template x-if="getCell(agent.id, hari.index) && getCell(agent.id, hari.index).status !== 'kerja'">
                                                <div class="schedule-cell cursor-grab inline-flex items-center justify-center p-2 rounded-lg transition-all select-none"
                                                     :style="getCell(agent.id, hari.index).is_off_request ? 'width:100%;border:1.5px dashed #EF4444;background-color:#FEF2F2;' : 'width:100%;border:1px dashed #D2D3D6;'"
                                                     :class="{
                                                         'ring-2 ring-blue-400 ring-offset-1': dragging && dragging.sourceId === getCell(agent.id, hari.index).id,
                                                         'ring-2 ring-green-400 ring-offset-1 bg-green-50': dragging && isValidDropTarget(agent.id, hari.index),
                                                         'opacity-40': dragging && dragging.sourceId !== getCell(agent.id, hari.index).id && !isValidDropTarget(agent.id, hari.index)
                                                     }"
                                                     draggable="true"
                                                     @dragstart="onDragStart($event, agent.id, hari.index)"
                                                     @dragend="onDragEnd()"
                                                     @dragover.prevent="onDragOver(agent.id, hari.index)"
                                                     @drop.prevent="onDrop(agent.id, hari.index)">
                                                    <span class="text-xs font-bold" :style="getCell(agent.id, hari.index).is_off_request ? 'color:#EF4444;' : 'color:#AEB0B6;'" x-text="getCell(agent.id, hari.index).is_off_request ? 'OFF (Request)' : 'OFF'"></span>
                                                </div>
                                            </template>
                                            <template x-if="!getCell(agent.id, hari.index)">
                                                <div class="inline-flex items-center justify-center p-2 rounded-lg transition-all select-none"
                                                     style="width:100%;border:1px dashed #D2D3D6;">
                                                    <span class="text-xs font-bold" style="color:#AEB0B6;">OFF</span>
                                                </div>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Loading overlay --}}
            <div x-show="loading" x-cloak
                class="fixed inset-0 z-40 flex items-center justify-center"
                style="background:rgba(255,255,255,0.7);backdrop-filter:blur(2px);">
                <div class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg" style="background-color:#ffffff;border:1px solid #E9E9EB;">
                    <svg class="w-5 h-5 animate-spin" style="color:#E2231A;" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold" style="color:#3F4249;">Memproses pertukaran...</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
