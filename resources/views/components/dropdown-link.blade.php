<a {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-start text-sm leading-5 font-semibold transition duration-150 ease-in-out']) }}
   style="color:#3F4249;"
   onmouseover="this.style.background='#F6F6F7';"
   onmouseout="this.style.background='';">{{ $slot }}</a>
