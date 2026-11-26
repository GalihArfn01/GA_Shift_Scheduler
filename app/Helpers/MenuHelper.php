<?php

namespace App\Helpers;

/**
 * Struktur menu sidebar, dipisah berdasarkan role (PIC / Agent).
 *
 * Pola kelas ini diadaptasi dari TailAdmin Laravel (resources/views/layouts/sidebar.blade.php
 * + app/Helpers/MenuHelper.php bawaan template), tapi isinya disesuaikan dengan
 * route name dan kebutuhan menu yang sudah berjalan di proyek PT XYZ
 * (lihat resources/views/layouts/sidebar-navigation.blade.php versi sebelumnya).
 */
class MenuHelper
{
    /**
     * Menu untuk role PIC (Supervisor) — akses penuh.
     */
    public static function getPicMenuGroups(): array
    {
        return [
            [
                'title' => null, // tanpa label grup, tampil langsung di atas
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => route('dashboard')],
                ],
            ],
            [
                'title' => 'Kelola Data',
                'items' => [
                    ['icon' => 'agent', 'name' => 'Agent', 'path' => route('agents.index')],
                    ['icon' => 'shift', 'name' => 'Shift', 'path' => route('shifts.index')],
                    ['icon' => 'template', 'name' => 'Template Operasional', 'path' => route('operational-templates.index')],
                    ['icon' => 'off-request', 'name' => 'Off Request', 'path' => route('off-requests.index')],
                    ['icon' => 'swap', 'name' => 'Tukar Jadwal', 'path' => route('pic.shift-swaps.index')],
                ],
            ],
            [
                'title' => 'Jadwal',
                'items' => [
                    [
                        'icon' => 'generate',
                        'name' => 'Generate Jadwal',
                        'path' => route('schedules.wizard'),
                        'extra' => ['schedules/generate*', 'schedules/periods/*/requirements', 'schedules/periods/*/check-complete', 'schedules/periods/*/run', 'schedules/periods/*/form-request-window', 'pic/schedules/*/grid*'],
                    ],
                    [
                        'icon' => 'history',
                        'name' => 'History Jadwal',
                        'path' => route('schedules.history'),
                        'extra' => ['schedules/history/*'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Menu untuk role Agent — akses terbatas (self-service).
     */
    public static function getAgentMenuGroups(): array
    {
        return [
            [
                'title' => null,
                'items' => [
                    ['icon' => 'dashboard', 'name' => 'Dashboard', 'path' => route('dashboard')],
                ],
            ],
            [
                'title' => 'Jadwal Saya',
                'items' => [
                    ['icon' => 'history', 'name' => 'Jadwal Saya', 'path' => route('agent.schedule.index')],
                    ['icon' => 'off-request', 'name' => 'Off Request Saya', 'path' => route('agent.off-requests.index')],
                    ['icon' => 'swap', 'name' => 'Tukar Jadwal', 'path' => route('agent.shift-swaps.index'), 'extra' => ['shift-swaps/create']],
                ],
            ],
            [
                'title' => 'Tim',
                'items' => [
                    ['icon' => 'team-schedule', 'name' => 'Jadwal Tim', 'path' => route('agent.schedule.team')],
                ],
            ],
        ];
    }

    /**
     * Ambil grup menu sesuai role user yang sedang login.
     */
    public static function getMenuGroups(): array
    {
        return auth()->user()?->isPic()
            ? self::getPicMenuGroups()
            : self::getAgentMenuGroups();
    }

    /**
     * Cek apakah sebuah path sidebar sedang aktif, dengan dukungan
     * beberapa path tambahan (mis. Generate Jadwal aktif juga untuk
     * /schedules/periods/* dan /schedules/generate/*).
     */
    public static function isActive(string $path, array $extraPatterns = []): bool
    {
        // Bandingkan hanya path (tanpa domain/query string), supaya tidak rapuh
        // terhadap query string atau trailing slash yang tidak relevan.
        $targetPath = ltrim((string) parse_url($path, PHP_URL_PATH), '/');

        if (request()->is($targetPath)) {
            return true;
        }

        foreach ($extraPatterns as $pattern) {
            if (request()->is(ltrim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ikon SVG per menu. Memakai set ikon yang sudah dipakai di
     * dashboard.blade.php supaya visualnya konsisten di seluruh sistem.
     */
    public static function getIconSvg(string $iconName): string
    {
        $icons = [
            'dashboard' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>',

            'agent' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 110 7.75M9 8a4 4 0 100 7.75M12 12a4 4 0 100-8 4 4 0 000 8z"></path></svg>',

            'shift' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm6-6l2 2 4-4"></path></svg>',

            'template' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>',

            'off-request' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',

            'generate' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>',

            'history' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            
            'swap' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>',

            'team-schedule' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',

            'grid-edit' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>',
        ];

        return $icons[$iconName] ?? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
    }
}
