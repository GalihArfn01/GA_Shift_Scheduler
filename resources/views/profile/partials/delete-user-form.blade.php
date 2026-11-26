<section class="space-y-5">
    <button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        type="button"
        class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
        style="background-color:#DC2626;box-shadow:0 4px 14px -2px rgba(220,38,38,0.35);"
        onmouseover="this.style.background='#B91C1C';"
        onmouseout="this.style.background='#DC2626';">
        {{ __('Hapus Akun') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-start gap-4 mb-5">
                <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0" style="background-color:#FEF2F2;">
                    <svg class="w-6 h-6" style="color:#DC2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-extrabold" style="color:#1F2126;">
                        {{ __('Apakah kamu yakin ingin menghapus akun ini?') }}
                    </h2>
                    <p class="mt-1 text-sm font-semibold" style="color:#62656F;">
                        {{ __('Setelah akun dihapus, semua data dan resource terkait akan dihapus secara permanen. Masukkan password untuk konfirmasi penghapusan akun.') }}
                    </p>
                </div>
            </div>

            <div class="mb-6">
                <label for="delete_password" class="block text-sm font-bold mb-1.5" style="color:#3F4249;">{{ __('Password') }}</label>
                <input type="password" id="delete_password" name="password" class="input-field" placeholder="{{ __('Masukkan password kamu') }}" />
                @if ($errors->userDeletion->get('password'))
                    <p class="text-xs font-semibold mt-1.5" style="color:#DC2626;">{{ implode(', ', $errors->userDeletion->get('password')) }}</p>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')"
                    class="px-4 py-2.5 text-sm font-semibold rounded-lg transition-colors"
                    style="color:#3F4249;border:1px solid #D2D3D6;background:#fff;"
                    onmouseover="this.style.background='#F6F6F7';"
                    onmouseout="this.style.background='#fff';">
                    {{ __('Batal') }}
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-lg text-sm font-bold text-white transition-all"
                    style="background-color:#DC2626;box-shadow:0 4px 14px -2px rgba(220,38,38,0.35);"
                    onmouseover="this.style.background='#B91C1C';"
                    onmouseout="this.style.background='#DC2626';">
                    {{ __('Hapus Akun Permanen') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
