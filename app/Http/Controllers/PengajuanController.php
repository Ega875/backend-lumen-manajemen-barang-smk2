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
    // TAMPILKAN SEMUA DAFTAR PENGAJUAN (ANTI-CRASH & DENGAN ALARM ERROR)
    // =========================================================================
    public function index(Request $request)
    {
        try {
            $user = $request->auth;

            if ($user->role === 'jurusan') {
                $daftarPengajuan = Pengajuan::with(['detail_pengajuan.masterBarangUmum'])
                                    ->where('user_id', $user->id)
                                    ->orderBy('id', 'DESC')
                                    ->get();
            } else {
                $daftarPengajuan = Pengajuan::with(['detail_pengajuan.masterBarangUmum'])
                                    ->orderBy('id', 'DESC')
                                    ->get();
            }

            foreach ($daftarPengajuan as $pengajuan) {
                // 1. MENGAMBIL NAMA INSTANSI (ANTI-GAGAL)
                $pembuat = DB::table('users')->where('id', $pengajuan->user_id)->first();
                
                // Trik Konversi ke Array agar PHP tidak error jika kolom tidak ada
                $pembuatArr = (array) $pembuat; 
                $pengajuan->nama_pembuat = $pembuatArr['name'] ?? ($pembuatArr['nama'] ?? ($pembuatArr['username'] ?? 'Instansi Jurusan'));

                // 2. MENGAMBIL LOG STATUS (ANTI-GAGAL)
                $logTerakhir = LogsStatus::where('pengajuan_id', $pengajuan->id)
                                ->orderBy('id', 'DESC')
                                ->first();

                if ($logTerakhir && isset($logTerakhir->user_id)) {
                    $userPengubah = DB::table('users')->where('id', $logTerakhir->user_id)->first();
                    $userPengubahArr = (array) $userPengubah;
                    $pengajuan->diubah_oleh = $userPengubahArr['role'] ?? null;
                } else {
                    $pengajuan->diubah_oleh = null;
                }

                // 3. MERAPIKAN DATA BARANG
                foreach ($pengajuan->detail_pengajuan as $det) {
                    if ($det->masterBarangUmum) {
                        $det->nama_barang_riil = $det->masterBarangUmum->nama_barang;
                        $det->spesifikasi_riil = $det->keterangan ?? ($det->masterBarangUmum->spesifikasi_umum ?? 'Spesifikasi standar mutu sekolah.');
                        $det->harga_riil = $det->harga_satuan > 0 ? $det->harga_satuan : ($det->masterBarangUmum->harga_satuan ?? 0);
                    } else {
                        $det->nama_barang_riil = $det->nama_barang ?? 'Barang Mandiri';
                        $det->spesifikasi_riil = $det->keterangan ?? 'Spesifikasi tidak dilampirkan.';
                        $det->harga_riil = $det->harga_satuan ?? 0;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Daftar data pengajuan barang',
                'data'    => $daftarPengajuan
            ], 200);

        } catch (\Exception $e) {
            // ALARM ERROR: Jika server mati, kirim pesan error yang jelas ke Frontend!
            return response()->json([
                'success' => false,
                'message' => 'Error Server Lumen: ' . $e->getMessage() . ' di baris ' . $e->getLine()
            ], 500);
        }
    }
    // =========================================================================
    // FUNGSI CHECKOUT DARI KERANJANG KE NOTA PENGAJUAN
    // =========================================================================
    public function store(Request $request)
    {
        $user = $request->auth;
        $isiKeranjang = Keranjang::where('user_id', $user->id)->get();

        if ($isiKeranjang->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Keranjang kosong!'], 400);
        }

        DB::beginTransaction();
        try {
            $nomorPengajuan = 'REQ-' . date('Ymd-His') . '-' . $user->id;

            $pengajuan = Pengajuan::create([
                'user_id'        => $user->id,
                'barang_id'      => null, 
                'status'         => 'pending_sarpras', 
                'catatan_revisi' => null
            ]);

            foreach ($isiKeranjang as $item) {
                DetailPengajuan::create([
                    'pengajuan_id'     => $pengajuan->id,
                    'master_barang_id' => $item->master_barang_id,
                    'nama_barang'      => $item->nama_barang, 
                    'jumlah'           => $item->jumlah,
                    'harga_satuan'     => $item->harga_satuan, 
                    'total_harga'      => $item->harga_satuan * $item->jumlah,
                    'keterangan'       => $item->keterangan 
                ]);
            }

            LogsStatus::create([
                'pengajuan_id'      => $pengajuan->id,
                'user_id'           => $user->id,
                'status_sebelumnya' => 'draft',
                'status_sesudah'    => 'pending_sarpras'
            ]);

            Keranjang::where('user_id', $user->id)->delete();
            DB::commit(); 

            return response()->json(['success' => true, 'message' => 'Berhasil!', 'data' => $pengajuan], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $pengajuan = Pengajuan::find($id);
        if (!$pengajuan) return response()->json(['success' => false], 404);
        
        $detailBarang = DetailPengajuan::where('pengajuan_id', $id)->get();
        return response()->json(['success' => true, 'pengajuan' => $pengajuan, 'list_barang' => $detailBarang], 200);
    }

    public function revisiDetailBarang(Request $request, $detail_id)
    {
        $user = $request->auth;
        $detail = DetailPengajuan::find($detail_id);
        if (!$detail) return response()->json(['success' => false], 404);

        $pengajuan = Pengajuan::find($detail->pengajuan_id);

        DB::beginTransaction();
        try {
            $detail->update([
                'jumlah'      => $request->jumlah,
                'total_harga' => $request->jumlah * $detail->harga_satuan,
                'keterangan'  => $request->keterangan ?? $detail->keterangan
            ]);

            $statusSebelumnya = $pengajuan->status;
            $pengajuan->update([
                'status' => 'pending_sarpras',
                'catatan_revisi' => null 
            ]);

            LogsStatus::create([
                'pengajuan_id'      => $pengajuan->id,
                'user_id'           => $user->id,
                'status_sebelumnya' => $statusSebelumnya,
                'status_sesudah'    => 'pending_sarpras'
            ]);

            DB::commit();
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false], 500);
        }
    }

    // =========================================================================
    // FUNGSI UPDATE STATUS & CATATAN OLEH PIHAK PENINJAU (VERIFIKATOR)
    // =========================================================================
    public function updateStatus(Request $request, $id)
    {
        $user = $request->auth;
        $pengajuan = Pengajuan::find($id);
        
        if (!$pengajuan) return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        $statusSebelumnya = $pengajuan->status;
        $status_baru = $request->status_sesudah;
        $catatan = $request->catatan;
        $roleReviewer = strtoupper($user->role);

        $pengajuan->status = $status_baru;
        
        if (!empty($catatan)) {
            $pengajuan->catatan_revisi = "[DITOLAK OLEH " . $roleReviewer . "] : " . $catatan;
        } else if (in_array($status_baru, ['disetujui', 'pending_keuangan', 'pending_kepsek'])) {
            $pengajuan->catatan_revisi = null;
        }

        DB::beginTransaction();
        try {
            $pengajuan->save(); 

            $log = new LogsStatus();
            $log->pengajuan_id = $pengajuan->id;
            $log->user_id = $user->id;
            $log->status_sebelumnya = $statusSebelumnya;
            $log->status_sesudah = $status_baru;
            $log->save(); 

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Keputusan berhasil dikirim!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // FUNGSI SIMPAN REVISI OLEH JURUSAN & DIAJUKAN ULANG
    // =========================================================================
    public function updateDetail(Request $request, $id)
    {
        $user = $request->auth;
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            if ($request->has('list_barang')) {
                // 1. Kumpulkan ID barang yang MASIH ADA di form layar Jurusan
                $id_tersisa = array_column($request->list_barang, 'id');

                // 2. HAPUS barang dari database yang ID-nya sudah dibuang oleh Jurusan
                DetailPengajuan::where('pengajuan_id', $pengajuan->id)
                               ->whereNotIn('id', $id_tersisa)
                               ->delete();

                // 3. Update jumlah/kuantitas untuk barang yang masih dipertahankan
                foreach ($request->list_barang as $barang) {
                    $detail = DetailPengajuan::find($barang['id']);
                    if ($detail) {
                        $detail->jumlah = $barang['jumlah'];
                        $detail->total_harga = $barang['jumlah'] * $detail->harga_satuan;
                        $detail->save(); 
                    }
                }
            }

            $statusSebelumnya = $pengajuan->status;
            $pengajuan->status = 'pending_sarpras';
            $pengajuan->catatan_revisi = null;
            $pengajuan->save();

            $log = new LogsStatus();
            $log->pengajuan_id = $pengajuan->id;
            $log->user_id = $user->id;
            $log->status_sebelumnya = $statusSebelumnya;
            $log->status_sesudah = 'pending_sarpras';
            $log->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Berkas direvisi & diajukan ulang!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal revisi berkas: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // FUNGSI HAPUS PENGAJUAN PERMANEN (BATALKAN BERKAS)
    // =========================================================================
    public function destroy(Request $request, $id)
    {
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            // Hapus detail barang dan riwayat log agar tidak menjadi data sampah (orphan) di database
            DB::table('detail_pengajuan')->where('pengajuan_id', $id)->delete();
            DB::table('logs_status')->where('pengajuan_id', $id)->delete();
            
            // Hapus berkas pengajuan utamanya
            $pengajuan->delete(); 

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Berkas pengajuan berhasil dibatalkan dan dihapus permanen!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal membatalkan berkas: ' . $e->getMessage()], 500);
        }
    }
}