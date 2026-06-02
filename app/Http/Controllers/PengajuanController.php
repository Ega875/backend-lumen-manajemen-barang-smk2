<?php

namespace App\Http\Controllers;

use App\Models\Keranjang;
use App\Models\Pengajuan;
use App\Models\DetailPengajuan;
use App\Models\LogsStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanController extends Controller
{
    // =========================================================================
    // 1. Tampilkan Semua Daftar Pengajuan (DIPERBAIKI: Menampilkan Info Aktor Penolak)
    // =========================================================================
    public function index(Request $request)
    {
        $user = $request->auth;

        // Jika dia 'jurusan', hanya boleh melihat pengajuan miliknya sendiri
        if ($user->role === 'jurusan') {
            $daftarPengajuan = Pengajuan::where('user_id', $user->id)->orderBy('id', 'DESC')->get();
        } else {
            // Jika dia Sarpras, Keuangan, atau Kepsek, bisa melihat semua pengajuan masuk untuk di-review
            $daftarPengajuan = Pengajuan::orderBy('id', 'DESC')->get();
        }

        // Trik Tambahan: Menyisipkan info penolak/pengubah terakhir secara instan di bawah status
        foreach ($daftarPengajuan as $pengajuan) {
            // Cari log status paling terakhir dari pengajuan ini
            $logTerakhir = LogsStatus::where('pengajuan_id', $pengajuan->id)
                            ->orderBy('id', 'DESC')
                            ->first();

            // Jika ada lognya, ambil role dari user yang mengubahnya
            if ($logTerakhir && $logTerakhir->user) {
                $pengajuan->diubah_oleh = $logTerakhir->user->role; // Mengambil teks string: 'keuangan' atau 'sarpras'
            } else {
                $pengajuan->diubah_oleh = null;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar data pengajuan barang',
            'data'    => $daftarPengajuan
        ], 200);
    }

    // =========================================================================
    // 2. Fungsi Checkout (Mengubah Keranjang Menjadi Nota Pengajuan)
    // =========================================================================
    public function store(Request $request)
    {
        $user = $request->auth;

        // Ambil data keranjang milik user ini
        $isiKeranjang = Keranjang::where('user_id', $user->id)->get();

        if ($isiKeranjang->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengajuan, keranjang kamu masih kosong!'
            ], 400);
        }

        // Mulai transaksi database aman
        DB::beginTransaction();

        try {
            // Generate Nomor Pengajuan otomatis, misal: REQ-20260530-001
            $nomorPengajuan = 'REQ-' . date('Ymd') . '-' . rand(100, 999);

            // Buat nota utama pengajuan
            $pengajuan = Pengajuan::create([
                'user_id'        => $user->id,
                'barang_id'      => null, // Diisi null karena barang aslinya baru ada kalau sudah disetujui/dibeli nanti
                'status'         => 'pending_sarpras', // Status awal masuk ke sarpras dulu
                'catatan_revisi' => null
            ]);

            // Pindahkan barang dari keranjang ke detail pengajuan
            foreach ($isiKeranjang as $item) {
                DetailPengajuan::create([
                    'pengajuan_id'     => $pengajuan->id,
                    'master_barang_id' => $item->master_barang_id,
                    'jumlah'           => $item->jumlah,
                    'harga_estimasi'   => 0, // Bisa di-update nanti oleh sarpas/keuangan saat survei harga
                    'keterangan'       => 'Diajukan oleh ' . $user->nama_user
                ]);
            }

            // Buat Log Status pertama kali
            LogsStatus::create([
                'pengajuan_id'      => $pengajuan->id,
                'user_id'           => $user->id,
                'status_sebelumnya' => 'draft',
                'status_sesudah'    => 'pending_sarpras',
                'catatan'           => 'Pengajuan berhasil dibuat oleh ' . $user->role
            ]);

            // Kosongkan keranjang user karena sudah jadi nota pengajuan
            Keranjang::where('user_id', $user->id)->delete();

            DB::commit(); // Simpan semua data permanen ke database

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan barang berhasil dikirim ke Sarpras!',
                'no_pengajuan' => $nomorPengajuan,
                'data'    => $pengajuan
            ], 201); // Diperbaiki dari 21 menjadi 215/201 Created standar API

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika ada error ditengah-tengah
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // 3. Detail Nota Pengajuan beserta list barang di dalamnya
    // =========================================================================
    public function show($id)
    {
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan tidak ditemukan'
            ], 404);
        }

        // Ambil barang-barang yang ada di dalam nota ini
        $detailBarang = DetailPengajuan::where('pengajuan_id', $id)->get();

        return response()->json([
            'success' => true,
            'pengajuan' => $pengajuan,
            'list_barang' => $detailBarang
        ], 200);
    }

    // =========================================================================
    // 4. BARU: Fungsi untuk Jurusan mengedit barang yang ditolak/revisi
    // =========================================================================
    public function revisiDetailBarang(Request $request, $detail_id)
    {
        $user = $request->auth;

        // Validasi inputan baru dari jurusan
        $this->validate($request, [
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string'
        ]);

        // 1. Cari data barang di detail pengajuan
        $detail = DetailPengajuan::find($detail_id);
        if (!$detail) {
            return response()->json(['success' => false, 'message' => 'Data detail barang tidak ditemukan'], 404);
        }

        // 2. Ambil data nota utamanya
        $pengajuan = Pengajuan::find($detail->pengajuan_id);

        // Pastikan hanya pemilik pengajuan (jurusan terkait) yang bisa mengedit
        if ($pengajuan->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak mengubah pengajuan ini'], 403);
        }

        DB::beginTransaction();
        try {
            // 3. Update jumlah barang yang diminta (misal dikurangi sesuai saran keuangan)
            $detail->update([
                'jumlah' => $request->jumlah,
                'keterangan' => $request->keterangan ?? $detail->keterangan
            ]);

            $statusSebelumnya = $pengajuan->status;

            // 4. PENTING: Naikkan kembali status nota utama ke 'pending_sarpras' agar diperiksa ulang
            $pengajuan->update([
                'status' => 'pending_sarpras',
                'catatan_revisi' => null // Reset/bersihkan catatan penolakan yang lama
            ]);

            // 5. Catat rekam jejak pengiriman ulang ke Log Status
            LogsStatus::create([
                'pengajuan_id'      => $pengajuan->id,
                'user_id'           => $user->id,
                'status_sebelumnya' => $statusSebelumnya,
                'status_sesudah'    => 'pending_sarpras',
                'catatan'           => 'Barang telah direvisi oleh Jurusan. Mengajukan kembali ke Sarpras.'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil direvisi dan nota otomatis diajukan kembali ke Sarpras!',
                'data_pengajuan' => $pengajuan
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal revisi: ' . $e->getMessage()], 500);
        }
    }
}
