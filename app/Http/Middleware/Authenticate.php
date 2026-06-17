<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // 1. Ambil token dari header Authorization
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak disediakan atau format salah.'
            ], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            // 2. Decode token JWT-mu
            $secretKey = env('JWT_SECRET', 'rahasia_super_secure_123');
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            $decoded->id = $decoded->sub;

            // Simpan data user ke request biar bisa diakses di controller
            $request->auth = $decoded;

            // 3. Cek Hak Akses / Role jika ada parameter tambahan di route
            if (!empty($roles) && !in_array($decoded->role, $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak! Role Anda tidak diizinkan.'
                ], 403);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token kadaluwarsa atau tidak valid.'
            ], 401);
        }

        return $next($request);
    }
}
