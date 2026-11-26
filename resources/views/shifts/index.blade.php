<x-app-layout>
    <x-slot name="header">Manajemen Shift</x-slot>

    <div class="py-8 animate-fade-in" x-data="{
        showModal: false,
        mode: 'create',
        form: { id: null, kode_shift: '', jam_masuk: '', jam_pulang: '' },
        openCreate() {
            this.mode = 'create';
            this.form = { id: null, kode_shift: '', jam_masuk: '', jam_pulang: '' };
            this.showModal = true;
        },
        openEdit(shift) {
            this.mode = 'edit';
            this.form = { id: shift.id, kode_shift: shift.kode_shift, jam_masuk: shift.jam_masuk.slice(0, 5), jam_pulang: shift.jam_pulang.slice(0, 5) };
            this.showModal = true;
        },
        timeKeydown(e) {
            // Cegah delete/backspace menghapus titik dua
            const pos = e.target.selectionStart;
            const val = e.target.value;
            if ((e.key === 'Backspace' && pos === 3) || (e.key === 'Delete' && pos === 2)) {
                e.preventDefault();
            }
        },
        timeInput(field, e) {
            let v = e.target.value.replace(/[^0-9]/g, '').slice(0, 4);
            if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2);
            this.form[field] = v;
            e.target.value = v;
            // Posisikan kursor setelah digit yang baru diketik
            const pos = e.target.selectionStart;
            if (pos === 3) e.target.setSelectionRange(4, 4);
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
                     style="background-color:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="page-title">Daftar Shift</h1>
                </div>
                <button @click="openCreate()" type="button"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                    onmouseover="this.style.background='#C01A12';"
                    onmouseout="this.style.background='#E2231A';">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Shift
                </button>
            </div>

            {{-- Table Card --}}
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr style="border-bottom:1px solid #E9E9EB;background-color:#FCFAF7;">
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Kode Shift</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Jam Masuk</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Jam Pulang</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Durasi</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Keterangan</span>
                                </th>
                                <th class="px-5 py-3 sm:px-6 text-right">
                                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color:#82858E;">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#E9E9EB;">
                            @forelse ($shifts as $shift)
                                @php
                                    // Shift malam (HC7): pulang setelah 22:00,
                                    // atau melewati tengah malam (pulang <= masuk).
                                    $malam = $shift->jam_pulang > '22:00:00'
                                        || $shift->jam_pulang <= $shift->jam_masuk;
                                    // Durasi memperhitungkan shift overnight
                                    // (17:00 -> 01:00 = 8 jam, bukan -16).
                                    $durasiJam = intdiv(
                                        ((strtotime($shift->jam_pulang) - strtotime($shift->jam_masuk)) + 86400) % 86400,
                                        3600
                                    );
                                    $terpakai = $shift->schedules_count > 0
                                        || $shift->operational_requirements_count > 0
                                        || $shift->operational_template_details_count > 0;
                                @endphp
                                <tr class="transition-colors hover:bg-ink-50" style="background-color:#ffffff;"
                                    onmouseover="this.style.background='#F6F6F7';"
                                    onmouseout="this.style.background='#ffffff';">
                                    <td class="px-5 py-4 sm:px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            style="{{ $malam ? 'background-color:#1F2126;color:#FFFFFF;' : 'background-color:#EEF2FF;color:#4F46E5;' }}">
                                            {{ $shift->kode_shift }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-sm font-semibold" style="color:#1F2126;">
                                        {{ substr($shift->jam_masuk, 0, 5) }}
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-sm font-semibold" style="color:#1F2126;">
                                        {{ substr($shift->jam_pulang, 0, 5) }}
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-sm" style="color:#62656F;">
                                        {{ $durasiJam }} jam
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        @if ($malam)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                                style="background-color:#FFF7ED;color:#C2410C;"
                                                title="Hard Constraint: hanya agent laki-laki yang boleh dijadwalkan di shift ini">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-10.293a1 1 0 00-1.414-1.414L10 8.586 7.707 6.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293z" clip-rule="evenodd"></path></svg>
                                                Laki-laki saja (HC7)
                                            </span>
                                        @else
                                            <span class="text-xs" style="color:#AEB0B6;">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                @click="openEdit({{ Illuminate\Support\Js::from($shift->only('id', 'kode_shift', 'jam_masuk', 'jam_pulang')) }})"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                                style="color:#62656F;"
                                                onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                                onmouseout="this.style.background='';this.style.color='#62656F';"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            @if ($terpakai)
                                                <button type="button" disabled
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center cursor-not-allowed opacity-40"
                                                    style="color:#DC2626;background-color:#F6F6F7;"
                                                    title="Tidak bisa dihapus karena masih dipakai jadwal/template/kebutuhan operasional">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                <form action="{{ route('shifts.destroy', $shift) }}" method="POST"
                                                    onsubmit="return confirm('Hapus shift {{ $shift->kode_shift }}? Tindakan ini tidak bisa dibatalkan.');">
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
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color:#F6F6F7;">
                                            <svg class="w-6 h-6" style="color:#AEB0B6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold" style="color:#62656F;">Belum ada data shift.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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

                <h3 class="text-xl font-extrabold mb-5" style="color:#1F2126;" x-text="mode === 'create' ? 'Tambah Shift' : 'Edit Shift'"></h3>

                <form :action="mode === 'create' ? '{{ route('shifts.store') }}' : `/shifts/${form.id}`" method="POST">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PATCH">
                    </template>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Kode Shift</label>
                            <input type="text" name="kode_shift" x-model="form.kode_shift" required maxlength="10"
                                   placeholder="misalnya A7, B, G" class="input-field uppercase">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Jam Masuk</label>
                                <input type="text" name="jam_masuk" x-model="form.jam_masuk" required
                                       maxlength="5" placeholder="HH:MM"
                                       @keydown="timeKeydown($event)"
                                       @input="timeInput('jam_masuk', $event)"
                                       class="input-field" style="letter-spacing:0.05em;">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">Jam Pulang</label>
                                <input type="text" name="jam_pulang" x-model="form.jam_pulang" required
                                       maxlength="5" placeholder="HH:MM"
                                       @keydown="timeKeydown($event)"
                                       @input="timeInput('jam_pulang', $event)"
                                       class="input-field" style="letter-spacing:0.05em;">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <button type="button" @click="showModal = false"
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
                            Simpan Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
