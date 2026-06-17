<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        // 1. Ambil token dari header HTTP (Authorization: Bearer <token>)
        $header = $request->header('Authorization');
        
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak disediakan atau format salah!'
            ], 401);
        }

        try {
            // Potong kata 'Bearer ' untuk mengambil string tokennya saja
            $token = substr($header, 7);
            
            // 2. Decode token JWT menggunakan secret key dari .env
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET', 'rahasia_super_secure_123'), 'HS256'));
            
            // 3. Cari data user berdasarkan ID ('sub') yang ada di dalam token
            $user = User::find($decoded->sub);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengguna tidak ditemukan dalam sistem!'
                ], 404);
            }

            $user->sub = $user->id;

            // 4. PROSES PENYARINGAN ROLE: Cek apakah role user diizinkan masuk
            if (!in_array($user->role, $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak! Akun ' . ucfirst($user->role) . ' tidak diizinkan mengakses halaman ini.'
                ], 403);
            }

            // Simpan data user yang login ke dalam request agar bisa dipanggil di controller nanti
            $request->auth = $user;

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token kadaluwarsa atau tidak sah!'
            ], 401);
        }
    }
}