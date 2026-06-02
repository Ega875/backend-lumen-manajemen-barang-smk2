<?php

namespace App\Http\Controllers;

use App\Models\DetailPengajuan;
use Illuminate\Http\Request;

class DetailPengajuanController extends Controller
{
    // 1. Tampilkan semua item barang berdasarkan ID Pengajuan tertentu
    public function getByPengajuan($pengajuan_id)
    {
        $details = DetailPengajuan::where('pengajuan_id', $pengajuan_id)->get();

        if ($details->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Detail barang untuk pengajuan ini tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail barang pengajuan berhasil diambil',
            'data'    => $details
        ], 200);
    }

    // 2. Update harga estimasi atau jumlah (Biasanya dilakukan Sarpras/Keuangan saat review)
    public function update(Request $request, $id)
    {
        $user = $request->auth;

        // Validasi hak akses: Hanya Sarpras dan Keuangan yang boleh utak-atik harga estimasi
        if (!in_array($user->role, ['sarpras', 'keuangan'])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak! Hanya Sarpras atau Keuangan yang boleh mengubah detail harga.'
            ], 403);
        }

        $this->validate($request, [
            'harga_estimasi' => 'required|numeric|min:0',
            'jumlah'         => 'required|integer|min:1',
            'keterangan'     => 'nullable|string'
        ]);

        $detail = DetailPengajuan::find($id);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Data detail pengajuan tidak ditemukan'
            ], 404);
        }

        // Update datanya
        $detail->update([
            'harga_estimasi' => $request->harga_estimasi,
            'jumlah'         => $request->jumlah,
            'keterangan'     => $request->keterangan ?? $detail->keterangan
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail barang pengajuan berhasil diperbarui!',
            'data'    => $detail
        ], 200);
    }
}