@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-bold focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out';
$activeStyle = ($active ?? false)
    ? 'border-color:#E2231A;color:#E2231A;background-color:#FFF1F0;'
    : 'color:#62656F;';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ $activeStyle }}"
   onmouseover="if(!this.style.borderLeftColor.includes('226')) { this.style.background='#F6F6F7';this.style.color='#1F2126'; }"
   onmouseout="if(!this.style.borderLeftColor.includes('226')) { this.style.background='';this.style.color='#62656F'; }">
    {{ $slot }}
</a>
