<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route agar hanya bisa diakses PIC (Supervisor).
 * Dipasang pada semua route yang sudah ada sebelumnya (Agent CRUD,
 * Template Operasional, Off Request milik PIC, Generate Jadwal, History).
 * Agent yang mencoba akses akan di-redirect ke dashboard miliknya sendiri.
 */
class EnsureUserIsPic
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isPic()) {
            abort(403, 'Halaman ini hanya bisa diakses oleh PIC.');
        }

        return $next($request);
    }
}
