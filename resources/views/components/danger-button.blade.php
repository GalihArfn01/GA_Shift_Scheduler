<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg font-bold text-sm text-white transition-all disabled:opacity-50']) }}
    style="background-color:#DC2626;box-shadow:0 1px 2px 0 rgba(31,33,38,0.04);"
    onmouseover="this.style.background='#B91C1C';"
    onmouseout="this.style.background='#DC2626';">
    {{ $slot }}
</button>
