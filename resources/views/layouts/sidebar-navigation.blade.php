@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();
@endphp

{{-- ==================== DESKTOP: Sidebar kiri ==================== --}}
<aside
    x-data="{
        // Pertahankan status collapse antar halaman (disimpan di localStorage, tahan terhadap reload).
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
        isHovered: false,
        currentPath: window.location.pathname,
        get isExpanded() {
            return !this.sidebarCollapsed || this.isHovered;
        },
        isMenuActive(targetPath, extraPatterns) {
            const path = this.currentPath.replace(/^\/+/, '');
            const toRegex = (pattern) => new RegExp('^' + pattern.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$');
            if (toRegex(targetPath).test(path)) return true;
            return extraPatterns.some((pattern) => toRegex(pattern).test(path));
        }
    }"
    x-init="window.addEventListener('swup:page-view', () => currentPath = window.location.pathname)"
    @mouseenter="if (sidebarCollapsed) isHovered = true"
    @mouseleave="isHovered = false"
    :class="sidebarCollapsed && !isHovered ? 'w-[72px]' : 'w-64'"
    class="hidden sm:flex sm:flex-col shrink-0 border-r h-screen sticky top-0 z-40 transition-all duration-200 ease-in-out"
    style="background:#ffffff;border-color:#D2D3D6;">

    {{-- Logo & nama app --}}
    <div class="h-16 flex items-center justify-between px-3 border-b shrink-0" style="border-color:#E9E9EB;">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-2 min-w-0">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-brand"
                 style="background-color:#E2231A;">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div x-show="isExpanded" x-cloak class="leading-tight min-w-0">
                <div class="font-extrabold text-sm tracking-tight truncate" style="color:#1F2126;">XYZ Scheduling</div>
                <div class="text-[10px] font-semibold uppercase tracking-wider" style="color:#E2231A;">PT XYZ Care</div>
            </div>
        </a>
        {{-- Toggle collapse --}}
        <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed ? '1' : '0')"
                x-show="isExpanded" x-cloak
                class="rounded-lg p-1.5 transition-colors hidden lg:flex items-center justify-center"
                style="color:#AEB0B6;"
                onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
                onmouseout="this.style.background='';this.style.color='#AEB0B6';">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    {{-- Navigasi --}}
    <nav class="flex-1 overflow-y-auto scrollbar-thin py-2">
        @foreach ($menuGroups as $group)
            @if ($group['title'])
                <p x-show="isExpanded" x-cloak class="menu-group-label">
                    {{ $group['title'] }}
                </p>
            @endif

            <div class="px-3 space-y-0.5">
                @foreach ($group['items'] as $item)
                    @php
                        $targetPath = ltrim((string) parse_url($item['path'], PHP_URL_PATH), '/');
                        $extra = $item['extra'] ?? [];
                    @endphp
                    <a href="{{ $item['path'] }}"
                       data-no-swup
                       class="menu-item group relative"
                       :class="[
                           isMenuActive(@js($targetPath), @js($extra)) ? 'menu-item-active' : 'menu-item-inactive',
                           !isExpanded ? 'justify-center' : 'justify-start'
                       ]"
                       title="{{ $item['name'] }}">
                        {{-- Active indicator bar --}}
                        <template x-if="isMenuActive(@js($targetPath), @js($extra))">
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 rounded-r-full"
                                  style="background-color:#E2231A;"></span>
                        </template>
                        <span class="shrink-0"
                              :class="isMenuActive(@js($targetPath), @js($extra)) ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                            {!! MenuHelper::getIconSvg($item['icon']) !!}
                        </span>
                        <span x-show="isExpanded" x-cloak class="truncate">
                            {{ $item['name'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- User area (bawah sidebar) --}}
    <div class="border-t p-3 space-y-1 shrink-0" style="border-color:#E9E9EB;">
        <a href="{{ route('notifications.index') }}"
           class="menu-item group"
           :class="!isExpanded ? 'justify-center' : 'justify-start'"
           style="color:#62656F;"
           onmouseover="this.style.background='#F6F6F7';this.style.color='#1F2126';"
           onmouseout="this.style.background='';this.style.color='#62656F';">
            @php $unreadNav = auth()->user()->unreadNotifications()->count(); @endphp
            <span class="relative shrink-0" style="color:#AEB0B6;">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($unreadNav > 0)
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 text-white text-[9px] font-bold rounded-full flex items-center justify-center"
                          style="background:#E2231A;">{{ $unreadNav > 9 ? '9+' : $unreadNav }}</span>
                @endif
            </span>
            <span x-show="isExpanded" x-cloak>Notifikasi</span>
        </a>

        {{-- User profile & logout --}}
        <x-dropdown align="left" width="48" :open-upward="true">
            <x-slot name="trigger">
                <button class="menu-item w-full group"
                        :class="!isExpanded ? 'justify-center' : 'justify-start'"
                        style="color:#62656F;">
                    <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                          style="background:#FFF1F0;color:#E2231A;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span x-show="isExpanded" x-cloak class="flex-1 min-w-0 text-left">
                        <span class="block text-sm font-bold truncate" style="color:#1F2126;">{{ Auth::user()->name }}</span>
                        <span class="block text-xs truncate" style="color:#82858E;">{{ Auth::user()->email }}</span>
                    </span>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')" data-no-swup>
                    Profil
                </x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" data-no-swup>
                    @csrf
                    <x-dropdown-link :href="route('logout')" data-no-swup
                            @click.prevent="$refs.logoutForm.submit()">
                        Keluar
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</aside>

{{-- ==================== MOBILE: Navbar atas ==================== --}}
<nav x-data="{ open: false }" class="sm:hidden w-full border-b" style="background:#ffffff;border-color:#D2D3D6;">
    <div class="px-4">
        <div class="flex justify-between h-16 items-center">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color:#E2231A;">
                    <span class="text-white font-bold text-xs">XYZ</span>
                </div>
                <span class="text-sm font-semibold" style="color:#1F2126;">PT XYZ</span>
            </a>
            <button @click="open = !open" class="p-2 rounded-md transition-colors"
                    style="color:#AEB0B6;"
                    onmouseover="this.style.background='#F6F6F7';"
                    onmouseout="this.style.background='';">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': !open}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    <div :class="{'block': open, 'hidden': !open}" class="hidden border-t py-2 space-y-1 px-3" style="border-color:#E9E9EB;">
        @foreach ($menuGroups as $group)
            @foreach ($group['items'] as $item)
                <x-responsive-nav-link :href="$item['path']" :active="MenuHelper::isActive($item['path'], $item['extra'] ?? [])">
                    {{ $item['name'] }}
                </x-responsive-nav-link>
            @endforeach
        @endforeach
        <div class="pt-3 pb-1 border-t" style="border-color:#E9E9EB;">
            <div class="px-3 mb-2">
                <div class="font-semibold text-sm" style="color:#1F2126;">{{ Auth::user()->name }}</div>
                <div class="text-xs" style="color:#82858E;">{{ Auth::user()->email }}</div>
            </div>
            <x-responsive-nav-link :href="route('profile.edit')" data-no-swup>Profil</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}" data-no-swup>
                @csrf
                <x-responsive-nav-link :href="route('logout')" @click.prevent="$el.closest('form').submit()">
                    Keluar
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
