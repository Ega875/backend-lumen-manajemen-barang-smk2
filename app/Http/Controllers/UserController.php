<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Mengambil semua daftar user untuk ditampilkan di tabel Sarpras
    public function index()
    {
        // Diurutkan berdasarkan role dan nama agar tabel rapi
        $users = User::orderBy('role', 'ASC')->orderBy('nama_user', 'ASC')->get();
        return response()->json([
            'success' => true,
            'message' => 'Daftar semua pengguna sistem',
            'data'    => $users
        ], 200);
    }

    // Membuat Akun Baru (Hanya Sarpras yang bisa akses endpoint ini)
    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_user' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role'      => 'required|in:siswa,jurusan,sarpras,keuangan,kepsek',
            'status'    => 'nullable|in:aktif,nonaktif' 
        ]);

        // FITUR OTOMATIS: Memotong teks sebelum "@" pada email untuk dijadikan username
        // Contoh: "tkj@smk.sch.id" akan otomatis menjadi "tkj"
        $generateUsername = explode('@', $request->email)[0];

        $user = User::create([
            'nama_user' => $request->nama_user,
            'username'  => $generateUsername, // <-- Masukkan username otomatis ke database
            'email'     => $request->email,
            'password'  => Hash::make($request->password), 
            'role'      => $request->role,
            'status'    => $request->status ?? 'aktif' 
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun baru berhasil didaftarkan!',
            'data'    => $user
        ], 201);
    }

    // Melihat detail satu profil user
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Data user tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $user], 200);
    }

    // FUNGSI UPDATE BARU: Digunakan Frontend Vue Sarpras untuk Edit Profil & Reset Password di satu tempat
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'Akun tidak ditemukan'], 404);

        $this->validate($request, [
            'nama_user' => 'required|string',
            'email'     => 'required|email|unique:users,email,'.$id,
            'role'      => 'required|in:siswa,jurusan,sarpras,keuangan,kepsek',
            'password'  => 'nullable|min:6',
            'status'    => 'nullable|in:aktif,nonaktif'
        ]);

        $user->nama_user = $request->nama_user;
        $user->email = $request->email;
        $user->role = $request->role;
        
        if ($request->has('status')) {
            $user->status = $request->status;
        }
        
        // Jika Sarpras mengetik password baru di modal Vue, timpa password lamanya
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return response()->json(['success' => true, 'message' => 'Data akun berhasil diperbarui!'], 200);
    }

    // Fungsi Hapus Akun yang dipanggil tombol merah di tabel Vue Sarpras
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'Akun tidak ditemukan'], 404);
        
        // Membuka transaksi database agar jika gagal di tengah jalan, data bisa dikembalikan utuh
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Bersihkan semua riwayat log yang pernah disentuh oleh akun ini
            \Illuminate\Support\Facades\DB::table('logs_status')->where('user_id', $id)->delete();

            // 2. Bersihkan detail barang dari pengajuan yang dibuat akun ini
            $pengajuanIds = \Illuminate\Support\Facades\DB::table('pengajuan')->where('user_id', $id)->pluck('id');
            if ($pengajuanIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('detail_pengajuan')->whereIn('pengajuan_id', $pengajuanIds)->delete();
                // 3. Hapus berkas pengajuannya
                \Illuminate\Support\Facades\DB::table('pengajuan')->where('user_id', $id)->delete();
            }

            // 4. Terakhir, setelah semua bersih, baru MySQL mengizinkan kita menghapus Akunnya!
            $user->delete();

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['success' => true, 'message' => 'Akun dan seluruh riwayatnya berhasil dihapus.'], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            // Kirim pesan error asli ke Frontend jika masih ada yang mengganjal
            return response()->json(['success' => false, 'message' => 'Sistem MySQL menolak: ' . $e->getMessage()], 500);
        }
    }
    // Membiarkan fungsi aslimu utuh untuk berjaga-jaga jika sistem temanmu memanggil endpoint ini
    public function resetPassword(Request $request, $id)
    {
        $this->validate($request, [
            'password' => 'required|min:6'
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Data user tidak ditemukan'], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => "Password untuk user {$user->nama_user} (Role: {$user->role}) berhasil diperbarui!"
        ], 200);
    }
}