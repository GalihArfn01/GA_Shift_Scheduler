<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#E2231A">
        <meta name="application-name" content="XYZ Scheduling">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <title>@isset($title){{ trim(strip_tags($title)) }} · @endisset{{ config('app.name', 'XYZ Scheduling') }}</title>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased" style="background-color:#FCFAF7;">        {{-- Split-screen: panel brand (kiri) + form (kanan) --}}
        <div class="min-h-screen flex relative">

            {{-- Lingkaran dekoratif yang melintasi pembatas panel (melengkung) --}}
            <div class="hidden lg:block absolute top-1/2 -translate-y-1/2 left-[46%] -translate-x-1/2 w-96 h-96 rounded-full pointer-events-none"
                 style="border:2px solid rgba(226,35,26,0.16);box-shadow:0 0 60px rgba(226,35,26,0.08) inset;"></div>
            <div class="hidden lg:block absolute bottom-[-3.5rem] left-[46%] -translate-x-1/2 w-44 h-44 rounded-full pointer-events-none"
                 style="border:2px solid rgba(226,35,26,0.12);"></div>            {{-- Panel kiri: brand (desktop) --}}
            <div class="hidden lg:flex lg:w-[46%] bg-brand-mesh relative overflow-hidden lg:rounded-r-[3rem]">
                <div class="absolute inset-0 bg-dotted opacity-10"></div>
                <div class="login-float absolute -top-32 -right-24 w-[28rem] h-[28rem] rounded-full" style="background:rgba(255,255,255,0.10);filter:blur(90px);animation-duration:11s;"></div>
                <div class="login-float absolute -bottom-40 -left-20 w-96 h-96 rounded-full" style="background:rgba(255,255,255,0.07);filter:blur(90px);animation-duration:14s;animation-delay:-4s;"></div>

                <div class="relative flex flex-col justify-between p-12 text-white h-full">

                    {{-- Logo --}}
                    <div class="login-enter flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                        style="background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="font-extrabold text-lg tracking-tight">XYZ Scheduling</div>
                </div>                    {{-- Brand mark --}}
                    <div class="login-enter flex flex-col items-center text-center" style="animation-delay:0.18s;">
                    <div class="w-24 h-24 rounded-3xl flex items-center justify-center mb-6"
                        style="background:rgba(255,255,255,0.14);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.22);box-shadow:0 24px 48px -12px rgba(0,0,0,0.25);">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-4xl font-extrabold tracking-tight">XYZ Scheduling</h1>
                    <p class="mt-2 text-xs font-bold uppercase tracking-[0.22em]" style="color:rgba(255,255,255,0.65);">
                        Customer Service Center PT XYZ
                    </p>
                </div>

                <div></div>
            </div>
        </div>

        {{-- Panel kanan: form --}}
        <div class="flex-1 flex items-center justify-center p-6" style="background-color:#FCFAF7;">
            <div class="w-full max-w-sm">                    {{-- Logo mobile --}}
                    <div class="login-enter lg:hidden flex items-center gap-3 mb-8">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shadow-brand"
                        style="background-color:#E2231A;">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold tracking-tight" style="color:#1F2126;">XYZ Scheduling</div>
                        <div class="text-xs font-semibold uppercase tracking-wider" style="color:#E2231A;">PT XYZ
                            Care</div>
                    </div>
                </div>                    <div class="bg-white rounded-2xl p-8 border {{ $errors->any() ? 'login-shake' : 'login-enter' }}"
                         style="border-color:#E9E9EB;box-shadow:0 1px 2px rgba(31,33,38,0.04),0 12px 32px -12px rgba(31,33,38,0.10);{{ $errors->any() ? '' : 'animation-delay:0.1s;' }}">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>