@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-bold leading-5 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
$activeStyle = ($active ?? false) ? 'border-color:#E2231A;color:#E2231A;' : 'color:#82858E;';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ $activeStyle }}"
   onmouseover="if(!this.classList.contains('border-brand-500')) { this.style.color='#1F2126'; }"
   onmouseout="if(!this.classList.contains('border-brand-500')) { this.style.color='#82858E'; }">
    {{ $slot }}
</a>
