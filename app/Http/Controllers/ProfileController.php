<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Anda tidak punya akses!');
        }

        $santriPenerobos = User::whereIn('role', ['santri', 'penerobos'])
            ->where('kelas', '!=', 'pending')
            ->orderBy('jenis_kelamin', 'asc')
            ->orderBy('full_name', 'asc')
            ->get();

        $dewanGuru = in_array($user->role, ['masteradmin', 'penerobos'])
            ? User::where('role', 'dewanguru')->where('kelas', '!=', 'pending')->get()
            : collect();

        return view('santri.index', [
            'users' => $santriPenerobos,
            'dewanGuru' => $dewanGuru,
            'role' => $user->role,
        ]);
    }

    public function edit()
    {
        $user = Auth::user();

        $previousUrl = url()->previous();
        if (!str_contains($previousUrl, route('profile.edit'))) {
            session(['previous_url' => $previousUrl]);
        }

        return view('santri.profile', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $role = $user->role;

        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'jenis_kelamin' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'no_hp_ortu' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'asal_daerah' => 'nullable|string',
            'asal_desa' => 'nullable|string',
            'asal_kelompok' => 'nullable|string',
        ];

        if (in_array($role, ['masteradmin', 'penerobos'])) {
            $rules['kelas'] = 'required|string|in:cepatan,lambatan,mt.guru';
            $rules['role'] = 'required|string|in:santri,penerobos,dewanguru,masteradmin';
        }

        $data = $request->validate($rules);

        if (!in_array($role, ['masteradmin', 'penerobos'])) {
            unset($data['kelas']);
            unset($data['role']);
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function editUser($id)
    {
        $authUser = Auth::user();

        if (!in_array($authUser->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Tidak punya akses.');
        }

        $user = User::findOrFail($id);

        $previousUrl = url()->previous();
        if (!str_contains($previousUrl, route('profile.editUser', $id))) {
            session(['previous_edit_url' => $previousUrl]);
        }

        return view('santri.edit', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $authUser = Auth::user();

        if (!in_array($authUser->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Tidak punya akses.');
        }

        $user = User::findOrFail($id);

        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'jenis_kelamin' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
            'no_hp_ortu' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'asal_daerah' => 'nullable|string',
            'asal_desa' => 'nullable|string',
            'asal_kelompok' => 'nullable|string',
        ];

        if (in_array($authUser->role, ['masteradmin', 'penerobos'])) {
            $rules['kelas'] = 'required|string|in:cepatan,lambatan,mt';
            $rules['role'] = 'required|string|in:santri,penerobos,dewanguru,masteradmin';
        }

        $data = $request->validate($rules);

        if (!in_array($authUser->role, ['masteradmin', 'penerobos'])) {
            unset($data['kelas']);
            unset($data['role']);
        }

        $user->update($data);

        return redirect()->route('profile.editUser', $user->id)->with('success', 'Profil berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $authUser = Auth::user();

        if (!in_array($authUser->role, ['masteradmin', 'penerobos', 'dewanguru'])) {
            abort(403, 'Tidak punya akses.');
        }

        $user = User::findOrFail($id);

        if ($user->id === $authUser->id) {
            return redirect()->back()->with('error', 'Kamu tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('santri')->with('success', 'User berhasil dihapus.');
    }
}
