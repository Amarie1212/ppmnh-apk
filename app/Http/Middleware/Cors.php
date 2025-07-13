<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Izinkan semua origin untuk pengembangan. Ganti dengan domain spesifik Anda di produksi.
        $allowedOrigins = ['http://localhost:8100', 'http://127.0.0.1:8100']; // Domain frontend Ionic Anda

        // Ambil origin dari request
        $origin = $request->header('Origin');

        // Cek apakah origin diizinkan
        if (in_array($origin, $allowedOrigins)) {
            $headers = [
                'Access-Control-Allow-Origin'      => $origin,
                'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
                'Access-Control-Allow-Credentials' => 'true', // Penting jika Anda menggunakan cookie/session
                'Access-Control-Max-Age'           => '86400',
                'Access-Control-Allow-Headers'     => 'Content-Type, X-Auth-Token, Origin, Authorization',
            ];
        } else {
            // Jika origin tidak diizinkan, kembalikan header CORS standar tanpa Access-Control-Allow-Origin yang spesifik
            $headers = [
                'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age'           => '86400',
                'Access-Control-Allow-Headers'     => 'Content-Type, X-Auth-Token, Origin, Authorization',
            ];
        }


        if ($request->isMethod('OPTIONS')) {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
