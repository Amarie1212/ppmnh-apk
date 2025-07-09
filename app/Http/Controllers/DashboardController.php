<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DashboardController extends Controller
{
public function index()
{
    $user = Auth::user();

    $absensiPersen = 90; // Contoh, ganti sesuai logika
    $jumlahSantri = User::whereIn('role', ['santri', 'penerobos'])
    ->where('kelas', '!=', 'pending')
    ->count();
    $jumlahUser = \App\Models\User::where('role', '!=', 'pending')
        ->where('kelas', '!=', 'pending')
        ->count();
    $infoPondok = 'Pondok Pesantren Mahasiswa Nurul Hakim';
    $materiPengajian = 'Tauhid, Fiqih, Bahasa Arab';
    $infoLainnya = 'Agenda bulanan, dsb.';
    $infoPengajian = 'Pengajian kitab rutin setiap Senin';

    return view('dashboard.index', compact(
        'user', 'absensiPersen', 'jumlahSantri', 'jumlahUser', 'infoPondok', 'materiPengajian', 'infoLainnya', 'infoPengajian'
    ));
}




    // ----- AUTH -----
    public function login() {
        return view('auth.login');
    }
    public function doLogin(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email', 'password'))) {
        // Semua user masuk ke dashboard, pengecekan dilakukan di controller dashboard!
        return redirect()->route('dashboard');
    }

    return back()->withErrors(['email' => 'Email atau password salah!']);
}



    public function logout() {
        Auth::logout();
        return redirect('/login');
    }

    // REGISTER (no pilih role)
    public function register() {
        return view('auth.register');
    }
public function doRegister(Request $request)
{
    // Validasi data form (HAPUS validasi role)
    $request->validate([
        'full_name'     => 'required|max:100',
        'email'         => 'required|email|unique:users,email',
        'password'      => 'required|min:5',
        'jenis_kelamin' => 'required|in:laki-laki,perempuan',
        'no_hp'         => 'nullable|max:20',
        'no_hp_ortu'    => 'nullable|max:20',
        'tempat_lahir'  => 'nullable|max:100',
        'tanggal_lahir' => 'nullable|date',
        'asal_daerah'   => 'nullable|max:100',
        'asal_desa'     => 'nullable|max:100',
        'asal_kelompok' => 'nullable|max:100',

        // HAPUS: 'role' => ...
    ]);

    // Simpan user baru ke database, role di-set 'pending'
    $user = \App\Models\User::create([
        'full_name'     => $request->full_name,
        'email'         => $request->email,
        'password'      => bcrypt($request->password),
        'jenis_kelamin' => $request->jenis_kelamin,
        'no_hp'         => $request->no_hp,
        'no_hp_ortu'    => $request->no_hp_ortu,
        'tempat_lahir'  => $request->tempat_lahir,
        'tanggal_lahir' => $request->tanggal_lahir,
        'asal_daerah'   => $request->asal_daerah,
        'asal_desa'     => $request->asal_desa,
        'asal_kelompok' => $request->asal_kelompok,
        'kelas'         => 'pending',
        'role'          => 'pending', // <-- di-set otomatis
    ]);

    // Login langsung setelah daftar
    Auth::login($user);

    // Redirect ke dashboard (atau halaman lain sesuai alurmu)
    return redirect('/')->with('success', 'Registrasi berhasil, silakan menunggu persetujuan.');
}


}
