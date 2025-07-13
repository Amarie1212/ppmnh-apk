<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\Rule; // Tambahkan ini untuk Rule::in

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Mengizinkan masteradmin, penerobos, dewanguru untuk melihat halaman ini
        if (!in_array($user->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Anda tidak punya akses!');
        }

        // Ambil santri dan penerobos untuk tabel utama
        $santriPenerobos = User::whereIn('role', ['santri', 'penerobos'])
            ->where('kelas', '!=', 'pending') // Filter kelas pending
            ->orderBy('jenis_kelamin', 'asc')
            ->orderBy('full_name', 'asc')
            ->get();

        // Ambil dewan guru untuk tabel terpisah
        // Hanya masteradmin dan dewanguru yang bisa melihat daftar dewan guru
        $dewanGuru = collect(); // Default: koleksi kosong
        if (in_array($user->role, ['masteradmin', 'dewanguru'])) {
             $dewanGuru = User::where('role', 'dewanguru')
                                ->where('kelas', '!=', 'pending')
                                ->get();
        }


        return view('santri.index', [
            'users' => $santriPenerobos,
            'dewanGuru' => $dewanGuru,
            'role' => $user->role, // Mengirim role user yang login
        ]);
    }

    // Metode untuk mengedit profil diri sendiri
    public function edit()
    {
        $user = Auth::user();

        // Menyimpan URL sebelumnya, kecuali jika dari halaman edit itu sendiri
        $previousUrl = url()->previous();
        if (!str_contains($previousUrl, route('profile.edit'))) {
            session(['previous_url' => $previousUrl]);
        }

        return view('santri.profile', compact('user'));
    }

    // Metode untuk mengupdate profil diri sendiri
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $role = $user->role; // Role user yang sedang mengedit (dirinya sendiri)

        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id, // Email harus unik kecuali dirinya sendiri
            'jenis_kelamin' => 'nullable|string', // Sudah disabled di Blade, tapi tetap validasi
            'no_hp' => 'nullable|string|max:20',
            'no_hp_ortu' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'asal_daerah' => 'nullable|string',
            'asal_desa' => 'nullable|string',
            'asal_kelompok' => 'nullable|string',
        ];

        // Kelas dan Role hanya bisa diupdate jika user adalah masteradmin, penerobos, atau dewanguru.
        // Di Blade, ini sudah disabled untuk santri dan dewanguru (role itu sendiri)
        // Kita tidak perlu validasi kelas dan role di sini karena Blade sudah handle disable.
        // Data dari disabled select tidak akan terkirim, jadi kita tidak perlu 'unset'.
        // Data jenis_kelamin juga diambil dari hidden input.

        $data = $request->validate($rules);

        // Jika jenis_kelamin tidak terkirim (karena disabled), ambil dari user yang sudah ada
        if (!isset($data['jenis_kelamin'])) {
            $data['jenis_kelamin'] = $user->jenis_kelamin;
        }

        // Pastikan kelas dan role tidak diupdate jika tidak diizinkan atau tidak terkirim
        // Ini adalah halaman edit profil sendiri, jadi kelas dan role seharusnya tidak perlu diubah dari sini.
        // Logika untuk mengubah kelas dan role user harus ada di updateUser
        unset($data['kelas']); // Pastikan kelas tidak bisa diubah dari edit profil sendiri
        unset($data['role']);  // Pastikan role tidak bisa diubah dari edit profil sendiri

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    // Metode untuk mengedit profil user LAIN
    public function editUser($id)
    {
        $authUser = Auth::user(); // User yang sedang login

        // Mengizinkan masteradmin, penerobos, dewanguru untuk mengakses halaman ini
        if (!in_array($authUser->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Tidak punya akses.');
        }

        $user = User::findOrFail($id); // User yang sedang diedit

        // Menyimpan URL sebelumnya, kecuali jika dari halaman editUser itu sendiri
        $previousUrl = url()->previous();
        if (!str_contains($previousUrl, route('profile.editUser', $id))) {
            session(['previous_edit_url' => $previousUrl]);
        }

        return view('santri.edit', compact('user'));
    }

    // Metode untuk mengupdate profil user LAIN
    public function updateUser(Request $request, $id)
    {
        $authUser = Auth::user(); // User yang sedang login (yang melakukan update)

        // Verifikasi izin akses (redundant karena sudah di handle di editUser, tapi bagus untuk keamanan)
        if (!in_array($authUser->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Tidak punya akses.');
        }

        $user = User::findOrFail($id); // User yang sedang diedit

        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id), // Email harus unik kecuali untuk user ini
            ],
            'jenis_kelamin' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'no_hp_ortu' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'asal_daerah' => 'nullable|string',
            'asal_desa' => 'nullable|string',
            'asal_kelompok' => 'nullable|string',
        ];

        // Aturan validasi untuk 'kelas' dan 'role' berdasarkan peran user yang LOGIN
        if ($authUser->role === 'masteradmin') {
            $rules['kelas'] = ['required', 'string', Rule::in(['cepatan', 'lambatan', 'mt', 'guru'])]; // Masteradmin bisa set 'guru'
            $rules['role'] = ['required', 'string', Rule::in(['santri', 'penerobos', 'dewanguru', 'masteradmin'])]; // Masteradmin bisa set semua role
        } elseif ($authUser->role === 'penerobos') {
            $rules['kelas'] = ['required', 'string', Rule::in(['cepatan', 'lambatan', 'mt'])]; // Penerobos tidak bisa set 'guru'
            // Penerobos hanya bisa mengubah ke santri atau penerobos.
            // Jika role user yang diedit saat ini adalah dewanguru/masteradmin,
            // penerobos tidak boleh bisa mengubahnya ke santri/penerobos.
            // Jadi, validasinya harus memperhitungkan role user yang diedit.
            $allowedRolesForPenerobos = ['santri', 'penerobos'];
            if (!in_array($user->role, $allowedRolesForPenerobos)) { // Jika user yang diedit bukan santri/penerobos
                 $rules['role'] = ['required', 'string', Rule::in([$user->role])]; // Hanya bisa tetap pada role aslinya
            } else {
                 $rules['role'] = ['required', 'string', Rule::in($allowedRolesForPenerobos)];
            }

        } elseif ($authUser->role === 'dewanguru') {
            $rules['kelas'] = ['required', 'string', Rule::in(['cepatan', 'lambatan', 'mt'])]; // Dewan Guru tidak bisa set 'guru'
            // Dewan Guru hanya bisa mengubah ke santri atau penerobos.
            // Jika role user yang diedit saat ini adalah masteradmin,
            // dewanguru tidak boleh bisa mengubahnya ke santri/penerobos/dewanguru.
            // Jadi, validasinya harus memperhitungkan role user yang diedit.
            $allowedRolesForDewanguru = ['santri', 'penerobos', 'dewanguru'];
            if (!in_array($user->role, $allowedRolesForDewanguru)) { // Jika user yang diedit bukan santri/penerobos/dewanguru
                $rules['role'] = ['required', 'string', Rule::in([$user->role])]; // Hanya bisa tetap pada role aslinya
            } else {
                $rules['role'] = ['required', 'string', Rule::in($allowedRolesForDewanguru)];
            }
        }

        $data = $request->validate($rules);

        // Jika field 'kelas' atau 'role' tidak ada dalam request (misal, karena tidak dirender di Blade untuk peran tertentu)
        // atau jika peran user yang login tidak memiliki izin untuk mengubahnya,
        // pastikan data tersebut tidak di-update secara tidak sengaja.
        // Ini berlaku jika Blade tidak mengirimkan field tersebut karena disabled/tersembunyi.
        if ($authUser->role !== 'masteradmin') {
            // Untuk dewanguru dan penerobos, kelas tidak bisa diubah ke 'guru'
            if (isset($data['kelas']) && $data['kelas'] === 'guru') {
                unset($data['kelas']); // Hapus jika mencoba set ke 'guru' tanpa izin masteradmin
            }

            // Untuk penerobos: tidak bisa set dewanguru/masteradmin
            if ($authUser->role === 'penerobos' && isset($data['role']) && !in_array($data['role'], ['santri', 'penerobos'])) {
                unset($data['role']); // Hapus jika mencoba set ke role yang tidak diizinkan
            }
            // Untuk dewanguru: tidak bisa set masteradmin
            if ($authUser->role === 'dewanguru' && isset($data['role']) && $data['role'] === 'masteradmin') {
                 unset($data['role']); // Hapus jika mencoba set ke role masteradmin
            }
        }
        // Aturan validasi Rule::in() di atas seharusnya sudah menangani ini,
        // tapi unset manual ini bisa jadi lapisan pertahanan ekstra jika ada celah di Blade.

        $user->update($data);

        return redirect()->route('profile.editUser', $user->id)->with('success', 'Profil berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $authUser = Auth::user();

        // Mengizinkan masteradmin, penerobos, dewanguru untuk menghapus user
        if (!in_array($authUser->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Tidak punya akses.');
        }

        $user = User::findOrFail($id);

        // Pencegahan menghapus akun sendiri
        if ($user->id === $authUser->id) {
            return redirect()->back()->with('error', 'Kamu tidak bisa menghapus akun sendiri.');
        }
        // Pencegahan menghapus masteradmin jika bukan masteradmin
        if ($user->role === 'masteradmin' && $authUser->role !== 'masteradmin') {
             return redirect()->back()->with('error', 'Kamu tidak punya izin menghapus masteradmin.');
        }


        $user->delete();

        return redirect()->route('santri')->with('success', 'User berhasil dihapus.');
    }
}
