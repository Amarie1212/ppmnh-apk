<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Role
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            abort(403, 'Silakan login terlebih dahulu.');
        }

        // Dukung multi-role: role:admin,user OR role:admin,user,superadmin
        if (count($roles) === 1 && str_contains($roles[0], ',')) {
            $roles = explode(',', $roles[0]);
        }
        // Support comma separated roles: role:masteradmin,penerobos
        if (count($roles) === 1 && str_contains($roles[0], ',')) {
            $roles = explode(',', $roles[0]);
        }
        $user = Auth::user();
        if (!$user || !in_array($user->role, $roles)) {
            abort(403, 'Akses Ditolak');
        }
        return $next($request);
    }
}
