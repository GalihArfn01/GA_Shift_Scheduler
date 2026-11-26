<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route agar hanya bisa diakses Agent (bukan PIC).
 * Dipasang pada halaman khusus Agent (Dashboard Agent, Jadwal Saya,
 * Off Request Saya). PIC yang mencoba akses akan di-redirect ke
 * dashboard miliknya sendiri.
 */
class EnsureUserIsAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAgent()) {
            abort(403, 'Halaman ini hanya bisa diakses oleh Agent.');
        }

        return $next($request);
    }
}
