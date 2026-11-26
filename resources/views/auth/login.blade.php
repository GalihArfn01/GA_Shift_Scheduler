<x-guest-layout>
    <x-slot name="title">Masuk</x-slot>

    <h1 class="page-title mb-6">Masuk</h1>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4"
          x-data="{ loading: false }" @submit="loading = true">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-semibold mb-1.5" style="color:#3F4249;">
                Email
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus
                   autocomplete="email"
                   class="input-field"
                   placeholder="nama@ptxyz.co.id"/>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold" style="color:#3F4249;">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold transition-colors"
                       style="color:#E2231A;"
                       onmouseover="this.style.color='#C01A12';"
                       onmouseout="this.style.color='#E2231A';"
                       href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <input id="password" type="password" name="password"
                       required autocomplete="current-password"
                       class="input-field pr-10"
                       placeholder="••••••••"/>
                {{-- Toggle show/hide password --}}
                <button type="button" id="togglePassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center relative transition-colors"
                        style="color:#AEB0B6; background:none; border:none; cursor:pointer; padding:0; width:1.25rem; height:1.25rem;"
                        aria-label="Tampilkan/sembunyikan password"
                        onmouseover="this.style.color='#62656F';"
                        onmouseout="this.style.color='#AEB0B6';">
                    {{-- Eye icon (show state) --}}
                    <svg id="icon-eye" class="w-4 h-4 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{-- Eye-off icon (hide state) --}}
                    <svg id="icon-eye-off" class="w-4 h-4 absolute top-0 left-0 opacity-0 scale-75 transition-all duration-200"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        {{-- Remember me --}}
        <label class="flex items-center gap-2 text-sm cursor-pointer" style="color:#62656F;">
            <input id="remember_me" type="checkbox" name="remember"
                   class="w-4 h-4 rounded border transition-all"
                   style="border-color:#D2D3D6;accent-color:#E2231A;">
            <span>Ingat saya di perangkat ini</span>
        </label>

        {{-- Submit --}}
        <button type="submit"
                :disabled="loading"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-bold text-white transition-all active:scale-[0.98] disabled:opacity-80 disabled:cursor-wait"
                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                onmouseover="this.style.background='#C01A12';"
                onmouseout="this.style.background='#E2231A';">
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span x-text="loading ? 'Memproses...' : 'Masuk'">Masuk</span>
            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </button>
    </form>

    <script>
        (function () {
            var btn = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            var iconEye = document.getElementById('icon-eye');
            var iconEyeOff = document.getElementById('icon-eye-off');
            if (btn && input) {
                btn.addEventListener('click', function () {
                    var isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    iconEye.classList.toggle('opacity-0', isHidden);
                    iconEye.classList.toggle('scale-75', isHidden);
                    iconEyeOff.classList.toggle('opacity-0', !isHidden);
                    iconEyeOff.classList.toggle('scale-75', !isHidden);
                });
            }
        })();
    </script>
</x-guest-layout>
