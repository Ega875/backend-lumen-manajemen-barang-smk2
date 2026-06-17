<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\PeminjamanAlat;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->auth->id;

        // Count active borrowings from peminjaman
        $totalPinjam = Peminjaman::where('user_id', $userId)
            ->where('status', 'dipinjam')
            ->count();

        // Count active borrowings from peminjaman_alat
        $totalPinjamAlat = PeminjamanAlat::where('siswa_id', $userId)
            ->where('status', 'dipinjam')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_pinjam' => $totalPinjam,
                'total_pinjam_alat' => $totalPinjamAlat,
            ]
        ], 200);
    }
}
