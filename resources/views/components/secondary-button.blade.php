<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white rounded-lg font-semibold text-sm transition-all disabled:opacity-50']) }}
    style="border:1px solid #D2D3D6;color:#3F4249;box-shadow:0 1px 2px 0 rgba(31,33,38,0.04),0 1px 3px 0 rgba(31,33,38,0.06);"
    onmouseover="this.style.background='#F6F6F7';"
    onmouseout="this.style.background='#ffffff';">
    {{ $slot }}
</button>
