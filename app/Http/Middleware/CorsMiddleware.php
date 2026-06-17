<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1. Definisikan header CORS yang valid
        // Hapus 'Access-Control-Allow-Credentials' => 'true' jika Origin menggunakan '*'
       $headers = [
    'Access-Control-Allow-Origin'      => '*',
    'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
    'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, X-Auth-Token'
];

        // 2. Tangani preflight request OPTIONS secara murni
        // Browser membutuhkan respons kosong (204 No Content) atau status 200 tanpa body untuk preflight OPTIONS
        if ($request->isMethod('OPTIONS')) {
            return response('', 204, $headers);
        }

        // 3. Lanjutkan request ke Controller untuk method lainnya (POST, GET, dll)
        $response = $next($request);

        // 4. Inject header CORS ke dalam respons yang dihasilkan oleh Lumen
        if (method_exists($response, 'header')) {
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
        } elseif (method_exists($response, 'withHeaders')) {
            $response->withHeaders($headers);
        }

        return $response;
    }
}
