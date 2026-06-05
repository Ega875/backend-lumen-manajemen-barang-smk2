<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Peminjaman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeminjamanController extends Controller
{
    // POST -> /api/peminjaman
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'barang_id'     => 'required|integer|exists:barang,id',
            'jumlah_pinjam' => 'required|integer|min:1'
        ]);

        // Ambil ID User dari token JWT yang sedang login
        $userId = $request->auth->sub;

        $barang = Barang::find($request->barang_id);

        if (!$barang) {
            return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan!'], 404);
        }

        // Validasi ketersediaan stok
        if ($barang->jumlah < $request->jumlah_pinjam) {
            return response()->json(['success' => false, 'message' => 'Gagal! Stok barang tidak mencukupi.'], 400);
        }

        // Kurangi stok barang
        $barang->jumlah -= $request->jumlah_pinjam;
        $barang->save();

        // Buat data peminjaman
        $peminjaman = Peminjaman::create([
            'user_id'        => $userId,
            'barang_id'      => $request->barang_id,
            'jumlah_pinjam'  => $request->jumlah_pinjam,
            'tanggal_pinjam' => date('Y-m-d'),
            'status'         => 'dipinjam'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman barang berhasil diproses!',
            'data'    => $peminjaman
        ], 201);
    }

    // GET -> /api/peminjaman/riwayat
    public function riwayat(Request $request): JsonResponse
    {
        $userId = $request->auth->sub;
        $role   = $request->auth->role;

        if ($role === 'siswa') {
            $riwayat = Peminjaman::with(['barang'])->where('user_id', $userId)->get();
        } else {
            $riwayat = Peminjaman::with(['user', 'barang'])->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat peminjaman berhasil diambil.',
            'data'    => $riwayat
        ], 200);
    }
}
