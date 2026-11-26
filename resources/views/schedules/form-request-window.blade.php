<x-app-layout>
    <x-slot name="header">Buka Form Request</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="mb-6">
                <h1 class="page-title">Wizard Generate Jadwal</h1>
            </div>

            {{-- Indikator Step --}}
            <div class="flex items-center justify-center gap-1 mb-6">
                @foreach (['Buat Periode', 'Kebutuhan', 'Generate', 'Form Request'] as $i => $label)
                    @php $stepNum = $i + 1; @endphp
                    <div class="flex items-center gap-1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                            style="{{ $stepNum === 4 ? 'background-color:#E2231A;color:#ffffff;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);' : 'background-color:#ECFDF5;color:#047857;' }}">
                            {{ $stepNum }}
                        </div>
                        @if ($stepNum < 4)
                            <div class="w-8 h-1 rounded-full" style="background-color:#ECFDF5;"></div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="text-center text-sm font-bold" style="color:#E2231A;">Step 4: Buka Form Request</p>

            <div class="card p-8 max-w-2xl mx-auto"
                x-data="{
                    hariTerpilih: {{ $existingWindow ? json_encode($existingWindow->hari_aktif) : '[]' }},
                    semuaHari: {{ json_encode(array_column($hariList, 'key')) }},
                    toggleSemua(checked) {
                        this.hariTerpilih = checked ? [...this.semuaHari] : [];
                    },
                    get isSemuaTercentang() {
                        return this.semuaHari.every(h => this.hariTerpilih.includes(h));
                    }
                }">

                <div class="flex items-center gap-3 mb-6 p-4 rounded-xl" style="background-color:#ECFDF5;border:1px solid #D1FAE5;">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background-color:#D1FAE5;">
                        <svg class="w-5 h-5" style="color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold" style="color:#047857;">Jadwal periode ini sudah final</h3>
                    </div>
                </div>

                <form action="{{ route('schedules.form-request-window.store', $period) }}" method="POST" class="space-y-4">
                    @csrf

                    <label class="flex items-center gap-3 pb-4 border-b cursor-pointer rounded-lg px-3 py-3 transition-colors hover:bg-ink-50"
                           style="border-color:#E9E9EB;background-color:#FCFAF7;">
                        <div class="relative w-4 h-4 shrink-0">
                            <input type="checkbox"
                                :checked="isSemuaTercentang"
                                @change="toggleSemua($event.target.checked)"
                                class="w-4 h-4 rounded border-2 cursor-pointer transition-colors"
                                style="accent-color:#E2231A;border-color:#D2D3D6;">
                        </div>
                        <span class="text-sm font-bold" style="color:#1F2126;">Pilih Semua</span>
                    </label>

                    <div class="space-y-2">
                        @foreach ($hariList as $hari)
                            <label class="flex items-center justify-between px-4 py-3.5 rounded-xl cursor-pointer transition-colors border"
                                   style="background-color:#ffffff;border-color:#E9E9EB;"
                                   onmouseover="this.style.background='#F6F6F7';"
                                   onmouseout="this.style.background='#ffffff';">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox"
                                        name="hari_aktif[]"
                                        value="{{ $hari['key'] }}"
                                        x-model="hariTerpilih"
                                        class="w-4 h-4 rounded cursor-pointer"
                                        style="accent-color:#E2231A;">
                                    <span class="text-sm font-bold" style="color:#1F2126;">{{ $hari['label'] }}</span>
                                </div>
                                <span class="text-xs font-semibold" style="color:#82858E;">{{ $hari['tanggal'] }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t" style="border-color:#E9E9EB;">
                        <p class="text-sm font-semibold" style="color:#62656F;">
                            <span class="font-extrabold text-lg" style="color:#1F2126;" x-text="hariTerpilih.length"></span> hari dipilih
                        </p>
                        <button type="submit"
                            class="px-6 py-3 rounded-lg text-sm font-bold text-white transition-all"
                            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                            onmouseover="this.style.background='#C01A12';"
                            onmouseout="this.style.background='#E2231A';">
                            Selesai
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
