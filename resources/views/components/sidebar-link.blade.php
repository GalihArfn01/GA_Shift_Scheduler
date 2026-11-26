@props(['active' => false, 'href', 'matchPaths' => null])

@php
$baseClasses = 'group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150';
$activeClasses = 'sidebar-link-active';
$inactiveClasses = 'sidebar-link-inactive';

$paths = $matchPaths ?? [parse_url($href, PHP_URL_PATH)];
$pathsJson = json_encode($paths);
@endphp

<a href="{{ $href }}"
    x-data="{ currentPath: window.location.pathname, matchPaths: {{ $pathsJson }} }"
    x-init="window.addEventListener('swup:page-view', () => currentPath = window.location.pathname)"
    :class="matchPaths.some(p => currentPath.startsWith(p)) ? '{{ $activeClasses }}' : '{{ $inactiveClasses }}'"
    {{ $attributes->merge(['class' => $baseClasses]) }}
    :title="sidebarCollapsed ? '{{ $title ?? '' }}' : ''">
    <span class="shrink-0 w-5 h-5 flex items-center justify-center">
        {{ $icon ?? '' }}
    </span>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate whitespace-nowrap">
        {{ $slot }}
    </span>
</a>
