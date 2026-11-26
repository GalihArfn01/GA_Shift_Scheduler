<x-app-layout>
    <x-slot name="header">Off Request</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="{
            tab: '{{ $tab }}',
            showAddModal: false,
            addForm: { agent_id: '', tanggal: '', keterangan: '' },
            showApprovalModal: false,
            approvalAction: null,
            approvalTargetId: null,
            approvalCatatan: '',
            openApproval(offRequestId, action) {
                this.approvalTargetId = offRequestId;
                this.approvalAction = action;
                this.approvalCatatan = '';
                this.showApprovalModal = true;
            },
            loadTab(tab) {
                if (tab === this.tab) return;
                this.tab = tab;
                // Bersihkan semua parameter (filter tab lain, pagination),
                // lalu set tab tujuan supaya pagination tidak membawa filter tab lain.
                const url = new URL(window.location.href);
                url.search = '';
                url.searchParams.set('tab', tab);
                this.loadBody(url.toString());
            },
            loadBody(url) {
                const container = document.querySelector('#off-request-tab-body');
                if (!container) return;
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const fresh = doc.querySelector('#off-request-tab-body');
                        if (!fresh) return;
                    container.innerHTML = fresh.innerHTML;
                    // Init Alpine segera untuk konten baru. Observer mutasi Alpine bisa
                    // berjalan telat/ganda sehingga binding @click/@submit/@change pada
                    // konten hasil fetch kadang mati atau memakai scope basi saat
                    // interaksi pertama. Init di sini memastikan binding aktif segera
                    // dengan scope yang benar (observer akan skip karena sudah di-marker).
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
                // Delegasi: pagination & link Reset di dalam body dimuat via AJAX.
                const link = event.target.closest('a');
                if (!link) return;
                const url = new URL(link.href);
                if (url.origin !== window.location.origin) return;
                event.preventDefault();
                this.loadBody(url.toString());
            }
        }">

            <form x-ref="approvalForm"
                :action="approvalTargetId ? '{{ url('off-requests') }}/' + approvalTargetId + '/approval' : ''"
                method="POST" class="hidden">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status_approval" :value="approvalAction">
                <input type="hidden" name="catatan_pic" x-model="approvalCatatan">
            </form>

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
                    <h1 class="page-title">Persetujuan Off Request</h1>
                </div>
            </div>

            <div class="card overflow-hidden">
                {{-- Tab Switcher --}}
                <div class="flex border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                    <a href="{{ route('off-requests.index', ['tab' => 'rendah']) }}"
                        @click.prevent="loadTab('rendah')"
                        class="flex-1 sm:flex-none px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                        :style="tab === 'rendah' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;'"
                        style="{{ $tab === 'rendah' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;' }}">
                        Prioritas Rendah (Form)
                    </a>
                    <a href="{{ route('off-requests.index', ['tab' => 'tinggi']) }}"
                        @click.prevent="loadTab('tinggi')"
                        class="flex-1 sm:flex-none px-6 py-4 text-sm font-bold border-b-2 transition-all text-center"
                        :style="tab === 'tinggi' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;'"
                        style="{{ $tab === 'tinggi' ? 'color:#C01A12; border-bottom-color:#E2231A; background-color:#ffffff;' : 'color:#62656F; border-bottom-color:transparent;' }}">
                        Prioritas Tinggi
                    </a>
                </div>

                <div id="off-request-tab-body" @click="handleBodyClick($event)">
                @if ($tab === 'rendah')
                {{-- ===== TAB: PRIORITAS RENDAH (Form Request) ===== --}}
                <div x-data="{
                    showFormModal: false,
                    formData: { agent_id: '', tanggal: [''] },
                    addTanggal() { this.formData.tanggal.push(''); },
                    removeTanggal(index) {
                        if (this.formData.tanggal.length > 1) {
                            this.formData.tanggal.splice(index, 1);
                        }
                    }
                }">
                    {{-- Toolbar --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:#E9E9EB;">
                        <p class="text-xs font-semibold" style="color:#62656F;">

                        </p>
                        <button @click="showFormModal = true" type="button"
                            class="ml-4 shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Form Request
                        </button>
                    </div>

                    {{-- Slot Preview Kuota --}}
                    @if ($window)
                        <div class="px-6 py-5 border-b" style="border-color:#E9E9EB;background-color:#FCFAF7;">
                            <p class="text-xs font-extrabold uppercase tracking-wider mb-4" style="color:#82858E;">
                                Preview Slot Kuota — Window Aktif
                                (Tutup: {{ $window->closes_at->translatedFormat('d M Y, H:i') }})
                            </p>
                            <div class="space-y-5">
                                @foreach (['inbound_informasi' => 'Inbound / Informasi', 'website_chat' => 'Website / Chat'] as $key => $labelKelompok)
                                    <div>
                                        <p class="text-sm font-extrabold mb-3 text-center" style="color:#1F2126;">{{ $labelKelompok }}</p>
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
                                                                    {{ $kolom['pengisi']->get($s, '—') }}
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
                        </div>
                    @else
                        <div class="mx-6 my-4 flex items-center gap-2 p-4 rounded-xl text-sm font-semibold"
                             style="background-color:#F6F6F7;color:#3F4249;border:1px solid #E9E9EB;">
                            <svg class="w-5 h-5 shrink-0" style="color:#82858E;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Tidak ada window Form Request aktif. Slot preview tidak tersedia.
                        </div>
                    @endif

                    {{-- Tabel Riwayat Form Request --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                    <th class="px-6 py-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Agent</span>
                                    </th>
                                    <th class="px-6 py-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Tanggal</span>
                                    </th>
                                    <th class="px-6 py-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Kelompok</span>
                                    </th>
                                    <th class="px-6 py-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Status</span>
                                    </th>
                                    <th class="px-6 py-3 text-right">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color:#E9E9EB;">
                                @forelse ($formRequests as $item)
                                    <tr class="transition-colors" style="background-color:#ffffff;"
                                        onmouseover="this.style.background='#F6F6F7';"
                                        onmouseout="this.style.background='#ffffff';">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
                                                     style="background-color:#FFF1F0;color:#E2231A;">
                                                    {{ strtoupper(substr($item->agent->nama_agent ?? 'A', 0, 1)) }}
                                                </div>
                                                <span class="font-bold text-sm" style="color:#1F2126;">{{ $item->agent->nama_agent ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold" style="color:#3F4249;">
                                                {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm" style="color:#62656F;">
                                            {{ $item->kelompok_section === 'inbound_informasi' ? 'Inbound / Informasi' : 'Website / Chat' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                                  style="background-color:#ECFDF5;color:#047857;">
                                                <span class="w-1.5 h-1.5 rounded-full" style="background-color:#059669;"></span>
                                                Diterima
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <form action="{{ route('off-requests.form-request.destroy', $item->id) }}"
                                                  method="POST" class="inline"
                                                  onsubmit="return confirm('Hapus form request ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors ml-auto"
                                                    style="color:#AEB0B6;background-color:#ffffff;border:1px solid #D2D3D6;"
                                                    onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#F87171';"
                                                    onmouseout="this.style.background='#ffffff';this.style.color='#AEB0B6';this.style.borderColor='#D2D3D6';"
                                                    title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                                <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold" style="color:#62656F;">Belum ada Form Request.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($formRequests->hasPages())
                        <div class="px-6 py-4 border-t" style="border-color:#E9E9EB;">
                            {{ $formRequests->links() }}
                        </div>
                    @endif

                    {{-- Modal Tambah Form Request (PIC) --}}
                    <div x-show="showFormModal" x-cloak @keydown.escape.window="showFormModal = false"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4">

                        <div @click="showFormModal = false" class="fixed inset-0 transition-opacity"
                             style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

                        <div @click.stop x-show="showFormModal"
                             class="relative w-full max-w-md rounded-2xl p-6 shadow-lift bg-white"
                             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                            <button @click="showFormModal = false"
                                    class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                                    style="color:#82858E;"
                                    onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                    onmouseout="this.style.background='';this.style.color='#82858E';">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>

                            <h3 class="text-xl font-extrabold mb-1" style="color:#1F2126;">Tambah Form Request</h3>
                            <p class="text-xs mb-5 leading-relaxed" style="color:#62656F;">
                                Tambah form request prioritas rendah mewakili agent. Kuota 3 slot/hari per kelompok section tetap berlaku.
                                Kelompok section ditentukan otomatis dari level agent.
                            </p>

                            <form action="{{ route('off-requests.form-request.store') }}" method="POST" class="space-y-4">
                                @csrf

                                <div>
                                    <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Agent</label>
                                    <select name="agent_id" x-model="formData.agent_id" required
                                            class="input-field w-full">
                                        <option value="">— Pilih Agent —</option>
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}">
                                                {{ $agent->nama_agent }}
                                                (Lv.{{ $agent->level }} —
                                                {{ $agent->level == 3 ? 'Website/Chat' : 'Inbound/Info' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Tanggal</label>
                                    <div class="space-y-2">
                                        <template x-for="(tgl, index) in formData.tanggal" :key="index">
                                            <div class="flex items-center gap-2">
                                                <input type="date" x-bind:name="`tanggal[${index}]`"
                                                       x-model="formData.tanggal[index]"
                                                       required class="input-field flex-1" />
                                                <button type="button"
                                                        x-show="formData.tanggal.length > 1"
                                                        @click="removeTanggal(index)"
                                                        class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors"
                                                        style="color:#DC2626;background-color:#FEF2F2;"
                                                        onmouseover="this.style.background='#FEE2E2';"
                                                        onmouseout="this.style.background='#FEF2F2';"
                                                        title="Hapus">
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
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Tambah Tanggal
                                    </button>
                                </div>

                                <div class="flex justify-end gap-3 pt-4">
                                    <button type="button" @click="showFormModal = false"
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
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @else
                {{-- ===== TAB: PRIORITAS TINGGI ===== --}}
                <div x-data="{
                    selectedIds: [],
                    get allSelected() {
                        let pageIds = [{{ $prioritasTinggiGabungan->pluck('id')->join(',') }}];
                        if (pageIds.length === 0) return false;
                        return pageIds.every(id => this.selectedIds.includes(id));
                    },
                    toggleAll() {
                        let pageIds = [{{ $prioritasTinggiGabungan->pluck('id')->join(',') }}];
                        if (this.allSelected) {
                            this.selectedIds = this.selectedIds.filter(id => !pageIds.includes(id));
                        } else {
                            pageIds.forEach(id => {
                                if (!this.selectedIds.includes(id)) this.selectedIds.push(id);
                            });
                        }
                    },
                    submitBulkDelete() {
                        if (confirm('Yakin ingin menghapus ' + this.selectedIds.length + ' off request terpilih?')) {
                            this.$refs.bulkDeleteForm.submit();
                        }
                    }
                }">
                    <div class="flex justify-between items-center px-6 py-4 border-b" style="border-color:#E9E9EB;">
                        <div>
                            <form x-ref="bulkDeleteForm" action="{{ route('off-requests.bulk-destroy') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <template x-for="id in selectedIds">
                                    <input type="hidden" name="ids[]" :value="id">
                                </template>
                                <button type="button" x-show="selectedIds.length > 0" @click="submitBulkDelete()" x-transition
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-colors"
                                    style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FEE2E2;"
                                    onmouseover="this.style.background='#FEE2E2';"
                                    onmouseout="this.style.background='#FEF2F2';">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Hapus Terpilih (<span x-text="selectedIds.length"></span>)
                                </button>
                            </form>
                        </div>
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('offreq:add-manual'))"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Manual
                        </button>
                    </div>

                    {{-- Filter --}}
                    <form method="GET" action="{{ route('off-requests.index') }}"
                        @submit.prevent="submitFilter($event)"
                        class="flex flex-col lg:flex-row lg:items-end gap-3 px-6 py-4 border-b flex-wrap"
                        style="border-color:#E9E9EB;background-color:#FCFAF7;">
                        <input type="hidden" name="tab" value="tinggi">

                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Cari Agent</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama agent atau keterangan..."
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
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="diterima" {{ request('status') === 'diterima' ? 'selected' : '' }}>Diterima</option>
                                <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>

                        <div class="w-full sm:w-44">
                            <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Sumber</label>
                            <select name="sumber"
                                    class="w-full rounded-lg border px-3 py-2 text-sm transition-all outline-none"
                                    style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                                    onfocus="this.style.borderColor='#E2231A'"
                                    onblur="this.style.borderColor='#E9E9EB'">
                                <option value="">Semua Sumber</option>
                                <option value="prioritas_tinggi" {{ request('sumber') === 'prioritas_tinggi' ? 'selected' : '' }}>Dari Agent</option>
                                <option value="pic_manual" {{ request('sumber') === 'pic_manual' ? 'selected' : '' }}>Input PIC</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Dari Tanggal</label>
                            <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                                   class="rounded-lg border px-3 py-2 text-sm transition-all outline-none"
                                   style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                                   onfocus="this.style.borderColor='#E2231A'"
                                   onblur="this.style.borderColor='#E9E9EB'">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color:#82858E;">Sampai Tanggal</label>
                            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                                   class="rounded-lg border px-3 py-2 text-sm transition-all outline-none"
                                   style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                                   onfocus="this.style.borderColor='#E2231A'"
                                   onblur="this.style.borderColor='#E9E9EB'">
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="px-5 py-2 rounded-lg text-sm font-bold transition-all"
                                style="background-color:#F6F6F7; color:#1F2126; border:1px solid #E9E9EB;"
                                onmouseover="this.style.background='#E9E9EB'"
                                onmouseout="this.style.background='#F6F6F7'">
                                Cari
                            </button>
                            @if (request()->hasAny(['search', 'status', 'sumber', 'tanggal_dari', 'tanggal_sampai']))
                                <a href="{{ route('off-requests.index', ['tab' => 'tinggi']) }}"
                                    class="px-4 py-2 rounded-lg text-sm font-bold transition-all"
                                    style="background-color:#ffffff; color:#62656F; border:1px solid #D2D3D6;"
                                    onmouseover="this.style.background='#F6F6F7';"
                                    onmouseout="this.style.background='#ffffff';">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                    <th class="px-4 py-2.5 w-10">
                                        <input type="checkbox" @change="toggleAll()" :checked="allSelected"
                                               class="w-4 h-4 rounded border transition-all cursor-pointer"
                                               style="border-color:#D2D3D6;accent-color:#E2231A;">
                                    </th>
                                    <th class="px-4 py-2.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Agent</span>
                                    </th>
                                    <th class="px-4 py-2.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Tanggal</span>
                                    </th>
                                    <th class="px-4 py-2.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Sumber</span>
                                    </th>
                                    <th class="px-4 py-2.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Keterangan</span>
                                    </th>
                                    <th class="px-4 py-2.5">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Status</span>
                                    </th>
                                    <th class="px-4 py-2.5 text-right">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color:#E9E9EB;">
                                @forelse ($prioritasTinggiGabungan as $item)
                                    <tr class="transition-colors"
                                        :style="selectedIds.includes({{ $item->id }}) ? 'background-color:#FFF1F0;' : 'background-color:#ffffff;'"
                                        onmouseover="if(!this.style.backgroundColor.includes('FFF1F0')) this.style.background='#F6F6F7';"
                                        onmouseout="if(!this.style.backgroundColor.includes('FFF1F0')) this.style.background='#ffffff';">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" :value="{{ $item->id }}" x-model="selectedIds"
                                                   class="w-4 h-4 rounded border transition-all cursor-pointer"
                                                   style="border-color:#D2D3D6;accent-color:#E2231A;">
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
                                                     style="background-color:#FFF1F0;color:#E2231A;">
                                                    {{ strtoupper(substr($item->agent->nama_agent ?? 'A', 0, 1)) }}
                                                </div>
                                                <span class="font-bold text-sm" style="color:#1F2126;">{{ $item->agent->nama_agent ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="text-sm font-semibold" style="color:#3F4249;">
                                                {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                                  style="{{ $item->jenis === 'pic_manual' ? 'background-color:#FFF1F0;color:#C01A12;' : 'background-color:#FFFBEB;color:#D97706;' }}">
                                                {{ $item->jenis === 'pic_manual' ? 'Input PIC' : 'Dari Agent' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm" style="color:#62656F; max-width:220px;">
                                            <div class="truncate" title="{{ $item->keterangan }}">{{ $item->keterangan ?? '—' }}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                                  style="{{ match($item->status_approval) {
                                                      'diterima' => 'background-color:#ECFDF5;color:#047857;',
                                                      'ditolak' => 'background-color:#FEF2F2;color:#B91C1C;',
                                                      default => 'background-color:#F6F6F7;color:#3F4249;',
                                                  } }}">
                                                <span class="w-1.5 h-1.5 rounded-full"
                                                      style="background-color:{{ match($item->status_approval) {
                                                          'diterima' => '#059669',
                                                          'ditolak' => '#DC2626',
                                                          default => '#82858E',
                                                      } }}"></span>
                                                {{ ucfirst($item->status_approval) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($item->jenis === 'prioritas_tinggi' && $item->status_approval === 'pending')
                                                    <button type="button" @click="openApproval({{ $item->id }}, 'diterima')"
                                                        class="px-3 py-1 rounded-lg text-xs font-bold transition-colors"
                                                        style="background-color:#ECFDF5;color:#047857;"
                                                        onmouseover="this.style.background='#D1FAE5';"
                                                        onmouseout="this.style.background='#ECFDF5';">
                                                        Setuju
                                                    </button>
                                                    <button type="button" @click="openApproval({{ $item->id }}, 'ditolak')"
                                                        class="px-3 py-1 rounded-lg text-xs font-bold transition-colors"
                                                        style="background-color:#FEF2F2;color:#B91C1C;"
                                                        onmouseover="this.style.background='#FEE2E2';"
                                                        onmouseout="this.style.background='#FEF2F2';">
                                                        Tolak
                                                    </button>
                                                @endif
                                                <form action="{{ route('off-requests.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus request off ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
                                                        style="color:#AEB0B6;background-color:#ffffff;border:1px solid #D2D3D6;"
                                                        onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';this.style.borderColor='#F87171';"
                                                        onmouseout="this.style.background='#ffffff';this.style.color='#AEB0B6';this.style.borderColor='#D2D3D6';"
                                                        title="Hapus">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                                <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold" style="color:#62656F;">Belum ada off request prioritas tinggi.</p>
                                            @if (request()->hasAny(['search', 'status', 'sumber', 'tanggal_dari', 'tanggal_sampai']))
                                                <p class="text-xs mt-1" style="color:#82858E;">Tidak ada hasil yang cocok dengan filter saat ini.</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($prioritasTinggiGabungan->hasPages())
                        <div class="px-6 py-4 border-t" style="border-color:#E9E9EB;">
                            {{ $prioritasTinggiGabungan->links() }}
                        </div>
                    @endif
                </div>
                @endif
                </div>
            </div>

            {{-- Modal Tambah Off Request Manual --}}
            <div x-show="showAddModal" x-cloak
                 @offreq:add-manual.window="showAddModal = true"
                 @keydown.escape.window="showAddModal = false"
                class="fixed inset-0 z-50 flex items-center justify-center p-4">

                <div @click="showAddModal = false" class="fixed inset-0 transition-opacity"
                     style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

                <div @click.stop x-show="showAddModal"
                     class="relative w-full max-w-md rounded-2xl p-6 shadow-lift bg-white"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                    <button @click="showAddModal = false"
                            class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                            style="color:#82858E;"
                            onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                            onmouseout="this.style.background='';this.style.color='#82858E';">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <h3 class="text-xl font-extrabold mb-2" style="color:#1F2126;">Tambah Off Request</h3>

                    <form action="{{ route('off-requests.pic-manual.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Agent <span class="text-[10px] font-normal" style="color:#82858E;">(Bisa pilih >1)</span></label>
                            <div class="w-full max-h-48 overflow-y-auto px-4 py-2.5 rounded-xl border scrollbar-thin bg-white space-y-2"
                                 style="border-color:#D2D3D6;">
                                @foreach ($agents as $agent)
                                    <label class="flex items-center gap-2 cursor-pointer p-1.5 rounded hover:bg-ink-50 transition-colors">
                                        <input type="checkbox" name="agent_ids[]" value="{{ $agent->id }}"
                                               class="w-4 h-4 rounded border cursor-pointer transition-all"
                                               style="border-color:#D2D3D6;accent-color:#E2231A;">
                                        <span class="text-sm font-semibold" style="color:#1F2126;">{{ $agent->nama_agent }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-data="{ tanggals: [''] }">
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Tanggal</label>
                            <div class="space-y-2">
                                <template x-for="(t, index) in tanggals" :key="index">
                                    <div class="flex items-center gap-2">
                                        <input type="date" name="tanggal[]" x-model="tanggals[index]" required class="input-field flex-1" />
                                        <button type="button" @click="tanggals.splice(index, 1)" x-show="tanggals.length > 1"
                                                class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors"
                                                style="color:#DC2626;background-color:#FEF2F2;"
                                                onmouseover="this.style.background='#FEE2E2';"
                                                onmouseout="this.style.background='#FEF2F2';" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="tanggals.push('')"
                                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold transition-colors"
                                    style="color:#E2231A;"
                                    onmouseover="this.style.color='#C01A12';"
                                    onmouseout="this.style.color='#E2231A';">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Tambah Baris Tanggal
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Keterangan</label>
                            <textarea name="keterangan" required rows="3" minlength="5" maxlength="500"
                                placeholder="Misal: sudah mencapai batas 20 hari kerja bulan ini"
                                class="input-field"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showAddModal = false"
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
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Modal Approval --}}
            <div x-show="showApprovalModal" x-cloak @keydown.escape.window="showApprovalModal = false"
                class="fixed inset-0 z-50 flex items-center justify-center p-4">

                <div @click="showApprovalModal = false" class="fixed inset-0 transition-opacity"
                     style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

                <div @click.stop x-show="showApprovalModal"
                     class="relative w-full max-w-md rounded-2xl p-6 shadow-lift bg-white"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                    <button @click="showApprovalModal = false"
                            class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                            style="color:#82858E;"
                            onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                            onmouseout="this.style.background='';this.style.color='#82858E';">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <h3 class="text-xl font-extrabold mb-2" style="color:#1F2126;">
                        <span x-text="approvalAction === 'diterima' ? 'Setujui Off Request' : 'Tolak Off Request'"></span>
                    </h3>
                    <p class="text-xs mb-6 leading-relaxed" style="color:#62656F;">
                        Catatan ini opsional, akan ditampilkan ke Agent beserta waktu keputusan ini dibuat.
                    </p>

                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Catatan (opsional)</label>
                        <textarea x-model="approvalCatatan" rows="3" maxlength="500"
                            placeholder="Misal: kebutuhan operasional tinggi"
                            class="input-field"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-6">
                        <button type="button" @click="showApprovalModal = false"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
                            style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                            onmouseover="this.style.background='#F6F6F7';"
                            onmouseout="this.style.background='#fff';">
                            Batal
                        </button>
                        <button type="button" @click="$refs.approvalForm.submit()"
                            class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                            :style="approvalAction === 'diterima' ? 'background-color:#10B981;' : 'background-color:#EF4444;'"
                            x-bind:style="approvalAction === 'diterima' ? 'background-color:#10B981;' : 'background-color:#EF4444;'">
                            <span x-text="approvalAction === 'diterima' ? 'Setujui' : 'Tolak'"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
