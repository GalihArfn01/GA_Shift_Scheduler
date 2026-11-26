<section>
    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Nama') }}</label>
            <input type="text" id="name" name="name" required autofocus autocomplete="name"
                value="{{ old('name', $user->name) }}" class="input-field" />
            @if ($errors->get('name'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('name')) }}</p>
            @endif
        </div>

        <div>
            <label for="email" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" required autocomplete="username"
                value="{{ old('email', $user->email) }}" class="input-field" />
            @if ($errors->get('email'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->get('email')) }}</p>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit"
                class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                onmouseover="this.style.background='#C01A12';"
                onmouseout="this.style.background='#E2231A';">
                {{ __('Simpan') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-bold" style="color:#059669;">{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>
</section>
