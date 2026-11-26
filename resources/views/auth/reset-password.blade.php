<x-guest-layout>
    <x-slot name="title">Reset Password</x-slot>

    <h1 class="page-title mb-6">Reset Password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Email') }}</label>
            <input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" class="input-field" />
            @if ($errors->get('email'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('email')) }}</p>
            @endif
        </div>

        <div>
            <label for="password" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Password Baru') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="input-field" placeholder="••••••••" />
            @if ($errors->get('password'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('password')) }}</p>
            @endif
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Konfirmasi Password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input-field" placeholder="••••••••" />
            @if ($errors->get('password_confirmation'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('password_confirmation')) }}</p>
            @endif
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white transition-all"
            style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
            onmouseover="this.style.background='#C01A12';"
            onmouseout="this.style.background='#E2231A';">
            {{ __('Reset Password') }}
        </button>
    </form>
</x-guest-layout>
