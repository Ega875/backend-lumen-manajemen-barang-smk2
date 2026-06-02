<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\LogsStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsStatusController extends Controller
{
    // 1. Ambil Semua Riwayat Perubahan Status dari Satu Pengajuan Tertentu (Untuk Timeline di UI)
    public function getHistory($pengajuan_id)
    {
        $logs = LogsStatus::where('pengajuan_id', $pengajuan_id)
                ->orderBy('id', 'ASC') // Urutkan dari yang paling lama ke terbaru biar jadi timeline
                ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat status pengajuan berhasil diambil',
            'data'    => $logs
        ], 200);
    }

    // 2. Fungsi Utama untuk Mengubah Status Pengajuan (Approval / Reject / Revisi)
    public function updateStatus(Request $request, $pengajuan_id)
{
    $user = $request->auth; // Mengambil data user yang login dari middleware JWT

    // ATURAN 1: Filter ketat! Hanya Sarpras, Keuangan, dan Kepsek yang boleh mengubah status
    if (!in_array($user->role, ['sarpras', 'keuangan', 'kepsek'])) {
        return response()->json([
            'success' => false,
            'message' => 'Hak akses ditolak! Role Anda tidak diizinkan mengubah status pengajuan.'
        ], 403);
    }

    $this->validate($request, [
        'status_sesudah' => 'required|in:pending_sarpras,pending_keuangan,pending_kepsek,disetujui,ditolak,revisi',
        'catatan'        => 'nullable|string|max:255'
    ]);

    $pengajuan = Pengajuan::find($pengajuan_id);

    if (!$pengajuan) {
        return response()->json([
            'success' => false,
            'message' => 'Data pengajuan tidak ditemukan'
        ], 404);
    }

    // ATURAN 2: Hanya Kepsek yang boleh memberikan status final 'disetujui'
    if ($request->status_sesudah === 'disetujui' && $user->role !== 'kepsek') {
        return response()->json([
            'success' => false,
            'message' => 'Hanya Kepala Sekolah yang berhak memberikan persetujuan final (disetujui)!'
        ], 403);
    }

    $statusSebelumnya = $pengajuan->status;

    DB::beginTransaction();
    try {
        // Tentukan catatan penolakan/revisi dinamis agar tahu siapa yang melakukan aksi
        $catatanFinal = $request->catatan;
        if (in_array($request->status_sesudah, ['ditolak', 'revisi']) && empty($catatanFinal)) {
            $catatanFinal = 'Pengajuan ' . $request->status_sesudah . ' oleh pihak ' . ucfirst($user->role);
        }

        // A. Jalankan update ke tabel utama pengajuan
        $pengajuan->update([
            'status'         => $request->status_sesudah,
            'catatan_revisi' => in_array($request->status_sesudah, ['revisi', 'ditolak']) ? $catatanFinal : $pengajuan->catatan_revisi
        ]);

        // B. Simpan rekam jejak ke tabel logs_status (Sesuai kolom baru di migration)
        $log = LogsStatus::create([
            'pengajuan_id'      => $pengajuan->id,
            'user_id'           => $user->id,
            'status_sebelumnya' => $statusSebelumnya,
            'status_sesudah'    => $request->status_sesudah,
            'keterangan'        => $catatanFinal ?? 'Status diperbarui oleh ' . ucfirst($user->role)
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diperbarui oleh ' . ucfirst($user->role),
            'data'    => $log
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui status: ' . $e->getMessage()
        ], 500);
    }
}
}