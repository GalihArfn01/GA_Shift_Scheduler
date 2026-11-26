@props(['wrapperClass' => ''])

<div class="relative {{ $wrapperClass }}">
    <input type="date"
        {{ $attributes->merge(['class' => 'input-field pr-11 [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-0 [&::-webkit-calendar-picker-indicator]:w-11 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:cursor-pointer']) }}>

    <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none"
        style="color:#E2231A;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
    </svg>
</div>
