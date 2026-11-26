<x-guest-layout>
    <x-slot name="title">Lupa Password</x-slot>

    <h1 class="page-title mb-6">Lupa Password</h1>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" :value="old('email')" required autofocus class="input-field" placeholder="nama@ptxyz.co.id" />
            @if ($errors->get('email'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('email')) }}</p>
            @endif
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white transition-all"
            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
            onmouseover="this.style.background='#C01A12';"
            onmouseout="this.style.background='#E2231A';">
            {{ __('Kirim Link Reset Password') }}
        </button>
    </form>
</x-guest-layout>
