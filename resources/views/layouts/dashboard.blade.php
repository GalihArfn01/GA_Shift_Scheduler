<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Ringkasan singkat --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total Agent</p>
                    <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $totalAgent }}</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Agent Aktif</p>
                    <p class="text-3xl font-semibold text-green-600 mt-1">{{ $agentAktif }}</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Agent Skorsing</p>
                    <p class="text-3xl font-semibold text-red-500 mt-1">{{ $agentSkorsing }}</p>
                </div>
            </div>

            {{-- Status periode jadwal terbaru --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-2">Periode Jadwal Terbaru</p>
                @if ($periodeTerbaru)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-medium text-gray-900">{{ $periodeTerbaru->nama_periode }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $periodeTerbaru->tanggal_mulai->translatedFormat('d M Y') }}
                            &ndash;
                            {{ $periodeTerbaru->tanggal_selesai->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <span
                        class="px-3 py-1 rounded-full text-sm font-medium
                            {{ $periodeTerbaru->status === 'final' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ ucfirst($periodeTerbaru->status) }}
                    </span>
                </div>
                @else
                <p class="text-gray-400">Belum ada periode jadwal yang dibuat.</p>
                @endif
            </div>

            {{-- Menu navigasi utama --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Menu</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                    <a href="{{ url('/agents') }}"
                        class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition flex items-start gap-4">
                        <div>
                            <p class="font-medium text-gray-900">Manajemen Agent</p>
                            <p class="text-sm text-gray-500 mt-1">Kelola data, level, dan status agent</p>
                        </div>
                    </a>

                    <a href="{{ url('/schedules/generate') }}"
                        class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition flex items-start gap-4">
                        <div>
                            <p class="font-medium text-gray-900">Generate Jadwal</p>
                            <p class="text-sm text-gray-500 mt-1">Buat periode baru dan jadwalkan otomatis</p>
                        </div>
                    </a>

                    <a href="{{ url('/schedules/history') }}"
                        class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition flex items-start gap-4">
                        <div>
                            <p class="font-medium text-gray-900">History Jadwal</p>
                            <p class="text-sm text-gray-500 mt-1">Lihat jadwal yang sudah final</p>
                        </div>
                    </a>

                    <a href="{{ url('/operational-templates') }}"
                        class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition flex items-start gap-4">
                        <div>
                            <p class="font-medium text-gray-900">Template Operasional</p>
                            <p class="text-sm text-gray-500 mt-1">Kelola template kebutuhan shift</p>
                        </div>
                    </a>

                    <a href="{{ url('/off-requests') }}"
                        class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition flex items-start gap-4">
                        <div>
                            <p class="font-medium text-gray-900">Off Request</p>
                            <p class="text-sm text-gray-500 mt-1">Kelola permintaan off agent</p>
                        </div>
                    </a>

                    <a href="{{ route('profile.edit') }}"
                        class="bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition flex items-start gap-4">
                        <div>
                            <p class="font-medium text-gray-900">Setting Akun</p>
                            <p class="text-sm text-gray-500 mt-1">Ubah password dan profil</p>
                        </div>
                    </a>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>