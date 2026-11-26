<x-guest-layout>
    <x-slot name="title">Verifikasi Email</x-slot>

    <h1 class="page-title mb-6">Verifikasi Email</h1>

    <p class="text-sm mb-5" style="color:#62656F;">Periksa email kamu untuk link verifikasi.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-4 rounded-xl text-sm font-semibold" style="background-color:#ECFDF5;color:#047857;border:1px solid #D1FAE5;">
            {{ __('Link verifikasi baru telah dikirim ke alamat email kamu.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                onmouseover="this.style.background='#C01A12';"
                onmouseout="this.style.background='#E2231A';">
                {{ __('Kirim Ulang Email Verifikasi') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="text-sm font-semibold transition-colors"
                style="color:#82858E;"
                onmouseover="this.style.color='#1F2126';"
                onmouseout="this.style.color='#82858E';">
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</x-guest-layout>
