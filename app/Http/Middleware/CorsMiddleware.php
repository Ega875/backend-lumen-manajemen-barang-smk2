<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1. Definisikan header CORS yang aman dan lengkap
        $headers = [
            'Access-Control-Allow-Origin'      => '*', // Atau sesuaikan ke 'http://localhost:8080'
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
        ];

        // 2. PERBAIKAN: Tangani preflight request OPTIONS secara murni tanpa JSON string mentah
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['method' => 'OPTIONS'], 200, $headers);
        }

        // 3. Lanjutkan request untuk method lainnya (POST, GET, dll)
        $response = $next($request);

        // 4. Pastikan response memiliki method header sebelum di-inject (antisipasi jika response bukan berupa objek utama)
        if (method_exists($response, 'header')) {
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
        }

        return $response;
    }
}
