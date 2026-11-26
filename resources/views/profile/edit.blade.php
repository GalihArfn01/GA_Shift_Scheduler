<x-app-layout>
    <x-slot name="header">Setting Akun</x-slot>

    <div class="py-8 animate-fade-in">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="mb-2">
                <h1 class="page-title">Setting Akun</h1>
            </div>

            {{-- Profil header --}}
            <div class="card overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5 p-6 sm:p-8"
                     style="background:linear-gradient(135deg,#FFF1F0 0%,#ffffff 60%);border-bottom:1px solid #FFE0DD;">
                    <div class="shrink-0 w-16 h-16 rounded-2xl flex items-center justify-center text-xl font-black text-white"
                         style="background:linear-gradient(135deg,#E2231A,#C01A12);box-shadow:0 8px 20px -6px rgba(226,35,26,0.5);">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                            <h2 class="text-xl font-extrabold tracking-tight" style="color:#1F2126;">{{ $user->name }}</h2>
                            @if($user->isPic())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider" style="background-color:#E2231A;color:#fff;">
                                    PIC
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider" style="background-color:#EEF2FF;color:#4F46E5;">
                                    Agent
                                </span>
                            @endif
                            @if($user->agent)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold" style="background-color:#F6F6F7;color:#62656F;">
                                    Level {{ $user->agent->level }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm font-semibold mt-1.5" style="color:#62656F;">{{ $user->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Informasi Profil + Password (2 kolom di desktop) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card p-6 sm:p-8">
                    <h2 class="text-base font-extrabold mb-5" style="color:#1F2126;">{{ __('Informasi Profil') }}</h2>
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="card p-6 sm:p-8">
                    <h2 class="text-base font-extrabold mb-5" style="color:#1F2126;">{{ __('Ubah Password') }}</h2>
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Hapus Akun --}}
            <div class="card p-6 sm:p-8" style="border-color:#FCA5A5;">
                <h2 class="text-base font-extrabold mb-1" style="color:#B91C1C;">{{ __('Hapus Akun') }}</h2>
                <p class="text-sm font-semibold mt-1 mb-5" style="color:#82858E;">
                    {{ __('Tindakan ini permanen dan tidak dapat dibatalkan.') }}
                </p>
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
