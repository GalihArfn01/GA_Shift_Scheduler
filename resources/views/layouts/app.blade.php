<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#E2231A">
        <meta name="application-name" content="XYZ Scheduling">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <title>{{ trim(strip_tags($header ?? '')) ? trim(strip_tags($header)) . ' · ' : '' }}{{ config('app.name', 'XYZ Scheduling') }}</title>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased" style="background-color:#FCFAF7;">

        <div class="flex flex-col sm:flex-row min-h-screen" style="background-color:#FCFAF7;">
            @include('layouts.sidebar-navigation')

            <div class="flex-1 flex flex-col min-w-0">
                <div id="swup" class="flex-1 flex flex-col min-w-0">

                    {{-- Topbar --}}
                    <header class="sticky top-0 z-30 h-16 flex items-center gap-4 px-5 border-b"
                            style="background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);border-color:#D2D3D6;">

                        {{-- Breadcrumb --}}
                        <div class="flex items-center gap-1.5 text-sm">
                            <span style="color:#AEB0B6;">XYZ</span>
                            <svg class="w-3.5 h-3.5" style="color:#D2D3D6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            @isset($header)
                                <span class="font-semibold" style="color:#1F2126;">{{ $header }}</span>
                            @endisset
                        </div>

                        <div class="flex-1"></div>

                        {{-- Notifikasi bell --}}
                        @auth
                        @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                class="relative w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                                style="color:#82858E;"
                                onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                                onmouseout="this.style.background='';this.style.color='#82858E';"
                                title="Notifikasi">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @if($unreadCount > 0)
                                    <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"
                                          style="background-color:#E2231A;">
                                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                    </span>
                                @endif
                            </button>

                            {{-- Dropdown notifikasi --}}
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-80 rounded-xl overflow-hidden z-50"
                                 style="background:#fff;border:1px solid #D2D3D6;box-shadow:0 4px 8px -2px rgba(31,33,38,0.08),0 12px 24px -4px rgba(31,33,38,0.10);">

                                <div class="flex items-center justify-between px-4 py-3 border-b" style="border-color:#E9E9EB;">
                                    <span class="text-sm font-semibold" style="color:#1F2126;">Notifikasi</span>
                                    @if($unreadCount > 0)
                                        <form action="{{ route('notifications.read-all') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold hover:underline"
                                                    style="color:#E2231A;">Tandai Semua Dibaca</button>
                                        </form>
                                    @endif
                                </div>

                                @php
                                    $dropNotifs = auth()->user()->notifications()->latest()->take(6)->get();
                                    $dropGroups = [];
                                    foreach ($dropNotifs as $dropN) {
                                        $dropGroups[$dropN->created_at->isToday() ? 0 : 1][] = $dropN;
                                    }
                                    $dropLabels = [0 => 'Hari Ini', 1 => 'Sebelumnya'];

                                    $miniIcons = [
                                        'check'    => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                                        'x'        => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
                                        'calendar' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                                        'inbox'    => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>',
                                        'swap'     => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>',
                                        'bell'     => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                                    ];
                                @endphp
                                <div class="max-h-80 overflow-y-auto scrollbar-thin">
                                    @forelse($dropGroups as $dropKey => $dropItems)
                                        <div class="px-4 pt-2.5 pb-1.5 text-[10px] font-black uppercase tracking-wider" style="color:#AEB0B6;background:#FAFAFB;border-top:1px solid #E9E9EB;">
                                            {{ $dropLabels[$dropKey] }}
                                        </div>
                                        @foreach($dropItems as $notif)
                                            @php
                                                $dropIsRead = !is_null($notif->read_at);
                                                $dropType   = $notif->data['type'] ?? '';
                                                $dropIcon   = $notif->data['icon'] ?? 'bell';

                                                if (str_starts_with($dropType, 'off_request')) {
                                                    $dropColor = '#D97706'; $dropBg = '#FFFBEB';
                                                } elseif (str_starts_with($dropType, 'tukar_jadwal')) {
                                                    $dropColor = '#2563EB'; $dropBg = '#EFF6FF';
                                                } elseif (str_starts_with($dropType, 'jadwal')) {
                                                    $dropColor = '#E2231A'; $dropBg = '#FFF1F0';
                                                } else {
                                                    $dropColor = '#62656F'; $dropBg = '#F6F6F7';
                                                }

                                                if ($dropIcon === 'check')      { $dropColor = '#059669'; $dropBg = '#ECFDF5'; }
                                                elseif ($dropIcon === 'x')      { $dropColor = '#DC2626'; $dropBg = '#FEF2F2'; }
                                                elseif ($dropIcon === 'calendar') { $dropColor = '#E2231A'; $dropBg = '#FFF1F0'; }
                                            @endphp
                                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST" @submit="open = false"
                                               class="block w-full transition-colors"
                                               style="{{ !$dropIsRead ? 'background:#FFF9F8;' : '' }}"
                                               onmouseover="this.style.background='#F6F6F7';"
                                               onmouseout="this.style.background='{{ !$dropIsRead ? '#FFF9F8' : '' }}';">
                                                @csrf
                                                <button type="submit"
                                                    class="w-full flex items-start gap-2.5 px-4 py-3 text-left transition-colors"
                                                    style="background:transparent;border:0;cursor:pointer;font:inherit;color:inherit;">
                                                    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" style="background-color:{{ !$dropIsRead ? '#E2231A' : 'transparent' }};"></span>
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background-color:{{ $dropBg }};color:{{ $dropColor }};">
                                                        {!! $miniIcons[$dropIcon] ?? $miniIcons['bell'] !!}
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-xs font-bold leading-snug truncate" style="color:#1F2126;">
                                                            {{ $notif->data['title'] ?? 'Notifikasi' }}
                                                        </p>
                                                        <p class="text-xs mt-0.5 line-clamp-2" style="color:#62656F;">
                                                            {{ $notif->data['message'] ?? '' }}
                                                        </p>
                                                        <p class="text-[10px] mt-1" style="color:#AEB0B6;">
                                                            {{ $notif->created_at->diffForHumans() }}
                                                        </p>
                                                    </div>
                                                </button>
                                            </form>
                                        @endforeach
                                    @empty
                                        <div class="px-4 py-6 text-center text-sm" style="color:#AEB0B6;">
                                            Tidak ada notifikasi.
                                        </div>
                                    @endforelse
                                </div>

                                <a href="{{ route('notifications.index') }}" @click="open = false"
                                   class="block text-center text-xs font-semibold px-4 py-3 border-t transition"
                                   style="color:#E2231A;border-color:#E9E9EB;"
                                   onmouseover="this.style.background='#F6F6F7';"
                                   onmouseout="this.style.background='';">
                                    Lihat Semua Notifikasi →
                                </a>
                            </div>
                        </div>
                        @endauth

                        {{-- Divider --}}
                        <div class="h-6 w-px" style="background:#D2D3D6;"></div>

                        {{-- User avatar --}}
                        @auth
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                                 style="background:#FFF1F0;color:#E2231A;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <span class="text-sm font-semibold hidden sm:block" style="color:#1F2126;">
                                {{ auth()->user()->name }}
                            </span>
                        </div>
                        @endauth
                    </header>

                    {{-- Page Content --}}
                    <main class="flex-1 p-5 md:p-8 animate-fade-in">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
