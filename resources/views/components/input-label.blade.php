@props(['value'])

<label {{ $attributes->merge(['class' => 'block mb-1.5 font-bold text-sm']) }} style="color:#3F4249;">
    {{ $value ?? $slot }}
</label>
