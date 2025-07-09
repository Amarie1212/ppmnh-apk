<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class UpdateBreadcrumb
{
    public function handle($request, Closure $next)
    {
        $route = Route::currentRouteName();

        $breadcrumbMap = [
            'dashboard' => ['label' => 'Dashboard', 'url' => route('dashboard')],
            'absensi' => ['label' => 'Absensi', 'url' => route('absensi')],
            'absensi.tambah' => ['label' => 'Tambah Absen', 'url' => route('absensi.tambah')],
            'santri' => ['label' => 'Data Santri', 'url' => route('santri')],
            'profile.edit' => ['label' => 'Edit Profil', 'url' => route('profile.edit')],
        ];

        $breadcrumbs = [];

        if (isset($breadcrumbMap[$route])) {
            $breadcrumbs[] = $breadcrumbMap['dashboard']; // Selalu ada dashboard
            if ($route !== 'dashboard') {
                $breadcrumbs[] = $breadcrumbMap[$route];
            }
        }

        Session::put('breadcrumbs', $breadcrumbs);

        return $next($request);
    }
}
