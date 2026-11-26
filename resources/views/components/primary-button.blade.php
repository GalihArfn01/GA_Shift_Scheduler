<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg font-bold text-sm text-white transition-all disabled:opacity-50']) }}
    style="background-color:#E2231A;box-shadow:0 4px 14px -2px rgba(226,35,26,0.35);"
    onmouseover="this.style.background='#C01A12';"
    onmouseout="this.style.background='#E2231A';">
    {{ $slot }}
</button>
