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
        $userId = $request->auth->id;
        $user = \App\Models\User::find($userId);

        $barang = Barang::find($request->barang_id);

        if (!$barang) {
            return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan!'], 404);
        }

        // Validasi kepemilikan jurusan jika peminjam adalah siswa
        if ($request->auth->role === 'siswa' && $barang->jurusan !== $user->jurusan) {
            return response()->json(['success' => false, 'message' => 'Gagal! Barang ini tidak terdaftar di program keahlian Anda.'], 403);
        }

        // Validasi ketersediaan stok
        if ($barang->jumlah < $request->jumlah_pinjam) {
            return response()->json(['success' => false, 'message' => 'Gagal! Stok barang tidak mencukupi.'], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
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

            // Catat riwayat peminjaman
            \App\Models\RiwayatPeminjaman::create([
                'peminjaman_id'     => $peminjaman->id,
                'aktivitas'         => 'Peminjaman barang ' . $barang->nama_barang . ' oleh ' . ($user->nama_user ?? 'Siswa') . ' sebanyak ' . $request->jumlah_pinjam . ' unit.',
                'tanggal_aktivitas' => date('Y-m-d')
            ]);

            // Sinkronisasi data ke tabel peminjaman_alat untuk tracking program keahlian/jurusan jika role adalah siswa
            if ($request->auth->role === 'siswa') {
                \App\Models\PeminjamanAlat::create([
                    'siswa_id'        => $userId,
                    'nama_siswa'      => $user->nama_user ?? 'Siswa',
                    'nama_alat'       => $barang->nama_barang,
                    'kode_alat'       => $barang->kode_barang,
                    'jurusan_alat'    => $user->jurusan ?? 'Umum',
                    'status'          => 'dipinjam'
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peminjaman barang berhasil diproses!',
                'data'    => $peminjaman
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // POST -> /api/peminjaman/scan
    public function scanQr(Request $request): JsonResponse
    {
        $this->validate($request, [
            'kode_barang'   => 'required|string',
            'jumlah_pinjam' => 'required|integer|min:1'
        ]);

        $userId = $request->auth->id;
        $user = \App\Models\User::find($userId);

        $barang = null;
        if ($request->auth->role === 'siswa') {
            $barang = Barang::where('kode_barang', $request->kode_barang)
                ->where('jurusan', $user->jurusan)
                ->first();
        } else {
            $barang = Barang::where('kode_barang', $request->kode_barang)->first();
        }

        if (!$barang) {
            return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan di program keahlian/lab Anda!'], 404);
        }

        // Validasi ketersediaan stok
        if ($barang->jumlah < $request->jumlah_pinjam) {
            return response()->json(['success' => false, 'message' => 'Gagal! Stok barang tidak mencukupi.'], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Kurangi stok barang
            $barang->jumlah -= $request->jumlah_pinjam;
            $barang->save();

            // Buat data peminjaman
            $peminjaman = Peminjaman::create([
                'user_id'        => $userId,
                'barang_id'      => $barang->id,
                'jumlah_pinjam'  => $request->jumlah_pinjam,
                'tanggal_pinjam' => date('Y-m-d'),
                'status'         => 'dipinjam'
            ]);

            // Catat riwayat peminjaman
            \App\Models\RiwayatPeminjaman::create([
                'peminjaman_id'     => $peminjaman->id,
                'aktivitas'         => 'Peminjaman barang (via QR) ' . $barang->nama_barang . ' oleh ' . ($user->nama_user ?? 'Siswa') . ' sebanyak ' . $request->jumlah_pinjam . ' unit.',
                'tanggal_aktivitas' => date('Y-m-d')
            ]);

            // Sinkronisasi data ke tabel peminjaman_alat untuk tracking program keahlian/jurusan jika role adalah siswa
            if ($request->auth->role === 'siswa') {
                \App\Models\PeminjamanAlat::create([
                    'siswa_id'        => $userId,
                    'nama_siswa'      => $user->nama_user ?? 'Siswa',
                    'nama_alat'       => $barang->nama_barang,
                    'kode_alat'       => $barang->kode_barang,
                    'jurusan_alat'    => $user->jurusan ?? 'Umum',
                    'status'          => 'dipinjam'
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Peminjaman barang via QR berhasil diproses!',
                'data'    => $peminjaman
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // GET -> /api/peminjaman/riwayat
    public function riwayat(Request $request): JsonResponse
    {
        $userId = $request->auth->id;
        $role   = $request->auth->role;

        if ($role === 'siswa') {
            $riwayat = Peminjaman::with(['barang', 'pengembalian'])->where('user_id', $userId)->get();
        } else {
            $riwayat = Peminjaman::with(['user', 'barang', 'pengembalian'])->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat peminjaman berhasil diambil.',
            'data'    => $riwayat
        ], 200);
    }
}
