<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class MastermindController extends Controller
{
    public function approveRole()
    {
        $pending = \App\Models\User::where('role', 'pending')->get();

        return view('mastermind.approve_role', compact('pending'));
    }

    public function approveRolePost(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:santri,penerobos,dewanguru,masteradmin',
            'kelas' => 'required|in:cepatan,mt,lambatan,guru'
        ]);
        $user = \App\Models\User::findOrFail($id);
        $user->role = $request->role;
        $user->kelas = $request->kelas;
        $user->save();

        return back()->with('success', 'Role user berhasil diubah!');
    }

    public function listSantri()
    {
        $users = \App\Models\User::whereIn('role', ['santri','penerobos','dewanguru'])
            ->where('role', '!=', 'pending')
            ->where('kelas', '!=', 'pending')
            ->get();

        return view('mastermind.list_santri', compact('users'));
    }

 public function izinAbsen()
{
    $usersByKelas = User::whereIn('role', ['penerobos', 'dewanguru'])
        ->where('kelas', '!=', 'pending')
        ->get()
        ->groupBy('kelas');

    return view('mastermind.izinabsen', compact('usersByKelas'));
}


    public function updateIzinAbsen(Request $request, User $user)
    {
        $request->validate([
            'boleh_tambah_absen' => 'required|boolean',
        ]);

        try {
            $user->boleh_tambah_absen = $request->boolean('boleh_tambah_absen');
            $user->save();

            $statusText = $user->boleh_tambah_absen ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'message' => "Izin tambah absen untuk {$user->full_name} berhasil {$statusText}.",
                'status' => $user->boleh_tambah_absen
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to update izin absen for user {$user->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat memperbarui izin.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateIzinAbsenBatch(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'boolean',
        ]);

        $permissions = $request->input('permissions');
        $updatedCount = 0;

        try {
            $userIds = array_keys($permissions);
            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                if (isset($permissions[$user->id]) && is_bool($permissions[$user->id])) {
                    if ($user->boleh_tambah_absen !== $permissions[$user->id]) {
                        $user->boleh_tambah_absen = $permissions[$user->id];
                        $user->save();
                        $updatedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyimpan {$updatedCount} perubahan izin absen.",
                'updated_count' => $updatedCount
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to update batch izin absen: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat menyimpan perubahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function izinAbsenPostBatch(Request $request)
    {
        $izin = $request->input('izin', []);

        $users = User::where('role', 'penerobos')
            ->whereIn('id', array_keys($izin))
            ->get();

        foreach ($users as $user) {
            $user->boleh_tambah_absen = isset($izin[$user->id]);
            $user->save();
        }

        return redirect()->route('mastermind.izinabsen', ['kelas' => $request->kelas])
            ->with('success', 'Izin berhasil diperbarui.');
    }
}
