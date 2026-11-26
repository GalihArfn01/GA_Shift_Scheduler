<x-guest-layout>
    <x-slot name="title">Konfirmasi Password</x-slot>

    <h1 class="page-title mb-6">Konfirmasi Password</h1>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="input-field" placeholder="••••••••" />
            @if ($errors->get('password'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('password')) }}</p>
            @endif
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white transition-all"
            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
            onmouseover="this.style.background='#C01A12';"
            onmouseout="this.style.background='#E2231A';">
            {{ __('Konfirmasi') }}
        </button>
    </form>
</x-guest-layout>
