<section>
    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Password Saat Ini') }}</label>
            <input type="password" id="update_password_current_password" name="current_password" autocomplete="current-password" class="input-field" />
            @if ($errors->updatePassword->get('current_password'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->updatePassword->get('current_password')) }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Password Baru') }}</label>
            <input type="password" id="update_password_password" name="password" autocomplete="new-password" class="input-field" />
            @if ($errors->updatePassword->get('password'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->updatePassword->get('password')) }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Konfirmasi Password') }}</label>
            <input type="password" id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password" class="input-field" />
            @if ($errors->updatePassword->get('password_confirmation'))
                <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->updatePassword->get('password_confirmation')) }}</p>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit"
                class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
                onmouseover="this.style.background='#C01A12';"
                onmouseout="this.style.background='#E2231A';">
                {{ __('Simpan Password') }}
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-bold" style="color:#059669;">{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>
</section>
