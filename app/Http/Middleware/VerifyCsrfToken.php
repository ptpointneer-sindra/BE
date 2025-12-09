<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware CSRF dummy untuk API Laravel 12
 * Semua request API dianggap valid CSRF, agar token mismatch tidak terjadi
 */
class VerifyCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bisa tambahkan log CSRF jika mau debug
        // \Log::info('API CSRF skipped for: '.$request->path());

        return $next($request);
    }
}
