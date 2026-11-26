<x-guest-layout>
    <x-slot name="title">Daftar</x-slot>

    <h1 class="page-title mb-6">Daftar</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Nama Lengkap') }}</label>
            <input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name"
                class="input-field" placeholder="Nama lengkap kamu" />
            @if ($errors->get('name'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('name')) }}</p>
            @endif
        </div>

        <div>
            <label for="email" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" :value="old('email')" required autocomplete="username"
                class="input-field" placeholder="nama@ptxyz.co.id" />
            @if ($errors->get('email'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('email')) }}</p>
            @endif
        </div>

        <div>
            <label for="password" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="input-field" placeholder="••••••••" />
            @if ($errors->get('password'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('password')) }}</p>
            @endif
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Konfirmasi Password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="input-field" placeholder="••••••••" />
            @if ($errors->get('password_confirmation'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('password_confirmation')) }}</p>
            @endif
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}"
                class="text-sm font-semibold transition-colors"
                style="color:#E2231A;"
                onmouseover="this.style.color='#C01A12';"
                onmouseout="this.style.color='#E2231A';">
                {{ __('Sudah punya akun?') }}
            </a>
            <button type="submit"
                class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                onmouseover="this.style.background='#C01A12';"
                onmouseout="this.style.background='#E2231A';">
                {{ __('Daftar') }}
            </button>
        </div>
    </form>
</x-guest-layout>
