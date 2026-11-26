<x-app-layout>
    <x-slot name="header">Manajemen Agent</x-slot>

    <div class="py-8 animate-fade-in" x-data="{
        showModal: false,
        mode: 'create',
        form: { id: null, nama_agent: '', level: '3', status: 'aktif', jenis_kelamin: '', email: '' },
        saving: false,
        openCreate() {
            this.mode = 'create';
            this.form = { id: null, nama_agent: '', level: '3', status: 'aktif', jenis_kelamin: '', email: '' };
            this.showModal = true;
        },
        openEdit(agent) {
            this.mode = 'edit';
            this.form = { id: agent.id, nama_agent: agent.nama_agent, level: String(agent.level), status: agent.status, jenis_kelamin: agent.jenis_kelamin ?? '', email: '' };
            this.showModal = true;
        },
        submitAgent() {
            this.saving = true;
            const url = this.mode === 'create' ? '{{ route('agents.store') }}' : `/agents/${this.form.id}`;
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            if (this.mode === 'edit') fd.append('_method', 'PATCH');
            fd.append('nama_agent', this.form.nama_agent);
            fd.append('level', this.form.level);
            fd.append('status', this.form.status);
            fd.append('jenis_kelamin', this.form.jenis_kelamin);
            if (this.mode === 'create') fd.append('email', this.form.email);
            fetch(url, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(r => {
                    if (r.ok) { window.location.reload(); return; }
                    if (r.status === 422) return r.json().then(data => { throw { validation: data.errors || {} }; });
                    throw r;
                })
                .catch(err => {
                    this.saving = false;
                    if (err && err.validation) {
                        const msgs = Object.values(err.validation).flat().join('\n');
                        alert('Validasi gagal:\n' + msgs);
                    } else if (err && err.message !== undefined) {
                        alert('Gagal menyimpan agent. Periksa koneksi atau validasi data.');
                    }
                });
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

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Roster Agent</h1>
                </div>
                <button @click="openCreate()" type="button"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                    onmouseover="this.style.background='#C01A12';"
                    onmouseout="this.style.background='#E2231A';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Agent
                </button>
            </div>

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('agents.index') }}" class="card p-4 flex flex-col sm:flex-row gap-4"
                  x-ref="filterForm"
                  x-data="{
                      submitFilter() {
                          const form = this.$refs.filterForm;
                          const url = form.action + '?' + new URLSearchParams(new FormData(form)).toString();
                          // Push state so URL updates in browser
                          window.history.pushState({}, '', url);
                          fetch(url)
                              .then(r => r.text())
                              .then(html => {
                                  const doc = new DOMParser().parseFromString(html, 'text/html');
                                  document.querySelector('#agents-table-container').innerHTML = doc.querySelector('#agents-table-container').innerHTML;
                              });
                      }
                  }"
                  @submit.prevent="submitFilter()">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama agent..."
                           class="w-full rounded-lg border px-3.5 py-2 text-sm transition-all outline-none"
                           style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                           onfocus="this.style.borderColor='#E2231A'"
                           onblur="this.style.borderColor='#E9E9EB'">
                </div>
                <div class="w-full sm:w-48">
                    <select name="level" class="w-full rounded-lg border px-3.5 py-2 text-sm transition-all outline-none"
                            style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                            onfocus="this.style.borderColor='#E2231A'"
                            onblur="this.style.borderColor='#E9E9EB'"
                            @change="submitFilter()">
                        <option value="">Semua Level</option>
                        <option value="0" {{ request('level') == '0' ? 'selected' : '' }}>Training</option>
                        <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>Level 1</option>
                        <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>Level 2</option>
                        <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>Level 3</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select name="jenis_kelamin" class="w-full rounded-lg border px-3.5 py-2 text-sm transition-all outline-none"
                            style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                            onfocus="this.style.borderColor='#E2231A'"
                            onblur="this.style.borderColor='#E9E9EB'"
                            @change="submitFilter()">
                        <option value="">Semua Gender</option>
                        <option value="L" {{ request('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ request('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select name="status" class="w-full rounded-lg border px-3.5 py-2 text-sm transition-all outline-none"
                            style="border-color:#E9E9EB; background:#ffffff; color:#1F2126;"
                            onfocus="this.style.borderColor='#E2231A'"
                            onblur="this.style.borderColor='#E9E9EB'"
                            @change="submitFilter()">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="skorsing" {{ request('status') == 'skorsing' ? 'selected' : '' }}>Skorsing</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2 rounded-lg text-sm font-bold transition-all"
                            style="background-color:#F6F6F7; color:#1F2126; border:1px solid #E9E9EB;"
                            onmouseover="this.style.background='#E9E9EB'"
                            onmouseout="this.style.background='#F6F6F7'">
                        Cari
                    </button>
                </div>
            </form>

            {{-- Table Card --}}
            <div class="card overflow-hidden" id="agents-table-container">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Nama Agent</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Level</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Jenis Kelamin</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Status</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6 text-right">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E9E9EB;">
                            @forelse ($agents as $agent)
                                <tr class="transition-colors hover:bg-ink-50" style="background-color:#ffffff;"
                                    onmouseover="this.style.background='#F6F6F7';"
                                    onmouseout="this.style.background='#ffffff';">
                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
                                                 style="background-color:#FFF1F0;color:#E2231A;">
                                                {{ strtoupper(substr($agent->nama_agent, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm" style="color:#1F2126;">{{ $agent->nama_agent }}</div>
                                                <div class="text-xs mt-0.5" style="color:#82858E;">ID: {{ $agent->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                            style="{{ $agent->isTraining()
                                                ? 'background-color:#ECFDF5;color:#047857;'
                                                : match((int) $agent->level) {
                                                    1 => 'background-color:#E9E9EB;color:#4D505A;',
                                                    2 => 'background-color:#FFFBEB;color:#D97706;',
                                                    3 => 'background-color:#FFF1F0;color:#C01A12;',
                                                    default => 'background-color:#E9E9EB;color:#4D505A;',
                                                } }}">
                                            {{ $agent->labelLevel() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        @if ($agent->jenis_kelamin)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                                style="{{ $agent->jenis_kelamin === 'L'
                                                    ? 'background-color:#EFF6FF;color:#1D4ED8;'
                                                    : 'background-color:#FDF2F8;color:#BE185D;' }}">
                                                {{ $agent->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                            </span>
                                        @else
                                            <span class="text-xs" style="color:#AEB0B6;">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <div x-data="{ 
                                            isActive: {{ $agent->status === 'aktif' ? 'true' : 'false' }},
                                            isSubmitting: false,
                                            toggleStatus() {
                                                if (this.isSubmitting) return;
                                                this.isSubmitting = true;
                                                this.isActive = !this.isActive;

                                                fetch('{{ route('agents.toggle-status', $agent) }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                        'Content-Type': 'application/json',
                                                        'Accept': 'application/json'
                                                    },
                                                    body: JSON.stringify({ _method: 'PATCH' })
                                                })
                                                .then(res => res.json())
                                                .then(data => {
                                                    if(!data.success) this.isActive = !this.isActive;
                                                })
                                                .catch(() => {
                                                    this.isActive = !this.isActive;
                                                    alert('Gagal mengubah status');
                                                })
                                                .finally(() => {
                                                    this.isSubmitting = false;
                                                });
                                            }
                                        }">
                                            <button type="button"
                                                @click="toggleStatus"
                                                class="inline-flex items-center gap-2 group focus:outline-none"
                                                title="Klik untuk ubah status">
                                                
                                                <!-- Toggle Track & Thumb -->
                                                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 ease-in-out flex-shrink-0"
                                                     :style="isActive ? 'background-color:#059669;' : 'background-color:#D2D3D6;'">
                                                    <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-soft transition-transform duration-200 ease-in-out"
                                                          :class="isActive ? 'translate-x-4' : 'translate-x-0'"></span>
                                                </div>

                                                <!-- Status Text -->
                                                <span class="text-sm font-semibold transition-colors inline-block w-16"
                                                      :style="isActive ? 'color:#059669;' : 'color:#62656F;'"
                                                      x-text="isActive ? 'Aktif' : 'Skorsing'">
                                                    {{ $agent->status === 'aktif' ? 'Aktif' : 'Skorsing' }}
                                                </span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                @click="openEdit({{ Illuminate\Support\Js::from($agent->only('id', 'nama_agent', 'level', 'status', 'jenis_kelamin')) }})"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                style="color:#62656F;"
                                                onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                                onmouseout="this.style.background='';this.style.color='#62656F';"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <form action="{{ route('agents.destroy', $agent) }}" method="POST"
                                                onsubmit="return confirm('Hapus agent {{ $agent->nama_agent }}? Akun login miliknya juga akan dinonaktifkan. Tindakan ini tidak bisa dibatalkan.');">
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
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                            <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold" style="color:#62656F;">Belum ada data agent.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($agents->hasPages())
                    <div class="px-6 py-4 border-t" style="border-color:#E9E9EB;">
                        {{ $agents->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal Tambah/Edit --}}
        <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">

            {{-- Backdrop blur --}}
            <div @click="showModal = false" class="fixed inset-0 transition-opacity"
                 style="background:rgba(31,33,38,0.5);backdrop-filter:blur(4px);"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            {{-- Modal Box --}}
            <div @click.stop x-show="showModal"
                 class="relative w-full max-w-md rounded-2xl p-6 shadow-lift bg-white"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <button @click="showModal = false"
                        class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-full transition-colors"
                        style="color:#82858E;"
                        onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                        onmouseout="this.style.background='';this.style.color='#82858E';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <h3 class="text-xl font-extrabold mb-5" style="color:#1F2126;" x-text="mode === 'create' ? 'Tambah Agent' : 'Edit Agent'"></h3>

                <form @submit.prevent="submitAgent()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Nama Agent</label>
                            <input type="text" x-model="form.nama_agent" required
                                   class="input-field">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Level</label>
                            <select x-model="form.level" required class="input-field">
                                <option value="0">Training</option>
                                <option value="1">Level 1 — Inbound</option>
                                <option value="2">Level 2 — Inbound, Informasi</option>
                                <option value="3">Level 3 — Semua section</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Jenis Kelamin</label>
                            <select x-model="form.jenis_kelamin" class="input-field">
                                <option value="">— Belum diisi —</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Status</label>
                            <select x-model="form.status" required class="input-field">
                                <option value="aktif">Aktif</option>
                                <option value="skorsing">Skorsing</option>
                            </select>
                        </div>

                        <template x-if="mode === 'create'">
                            <div>
                                <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Email (Akun Login)</label>
                                <input type="email" x-model="form.email" required
                                       placeholder="nama@ptxyz.co.id" class="input-field">
                                <div class="mt-2 flex gap-2 p-3 rounded-lg" style="background-color:#F6F6F7;">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" style="color:#62656F;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-xs leading-relaxed" style="color:#62656F;">
                                        Akun otomatis dibuat dengan password default. Agent akan dianjurkan mengganti password setelah login.
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <button type="button" @click="showModal = false"
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
                                style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                                onmouseover="this.style.background='#F6F6F7';"
                                onmouseout="this.style.background='#fff';">
                            Batal
                        </button>
                        <button type="submit" :disabled="saving"
                                class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all disabled:opacity-50"
                                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                                onmouseover="this.style.background='#C01A12';"
                                onmouseout="this.style.background='#E2231A';">
                            <span x-show="!saving">Simpan Agent</span>
                            <span x-show="saving">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
