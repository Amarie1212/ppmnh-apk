@php
    $user = Auth::user(); // Dapatkan objek user lengkap
    $role = $user->role ?? ''; // Ambil role
    $kelas = $user->kelas ?? ''; // Ambil kelas (penting untuk pending check)
    $isAbsensiParentActive = request()->routeIs('absensi.index') || request()->routeIs('absensi.create');

    // Cek apakah user sedang dalam status 'pending' (role atau kelasnya)
    $isPendingUser = ($role === 'pending' || $kelas === 'pending');
@endphp

<div class="bottom-nav-wrapper">
    <div class="bottom-nav" id="bottom-nav">
        <ul class="bottom-nav-menu">
            {{-- Dashboard (Always visible) --}}
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="icon"></i>
                    <span class="menu-label">Dashboard</span>
                </a>
            </li>

            {{-- Semua menu lainnya hanya terlihat jika user BUKAN pending --}}
            @if (! $isPendingUser)
                {{-- Absensi Menu --}}
                <li>
                    <a href="{{ route('absensi.index') }}" class="{{ $isAbsensiParentActive ? 'active' : '' }}">
                        <i data-lucide="clipboard-list" class="icon"></i>
                        <span class="menu-label">Absensi</span>
                    </a>
                </li>

                {{-- Data Santri --}}
                {{-- Only visible for roles NOT 'santri' (i.e., penerobos, dewanguru, masteradmin) --}}
                @if($role !== 'santri')
                <li><a href="{{ route('santri') }}" class="{{ request()->routeIs('santri') ? 'active' : '' }}">
                    <i data-lucide="users" class="icon"></i>
                    <span class="menu-label">Data Santri</span>
                </a></li>
                @endif

                {{-- Approve Role --}}
                {{-- HANYA untuk masteradmin --}}
                @if($role === 'masteradmin')
                    <li><a href="{{ route('mastermind.approve') }}" class="{{ request()->routeIs('mastermind.approve') ? 'active' : '' }}">
                        <i data-lucide="user-check" class="icon"></i>
                        <span class="menu-label">Approve Role</span>
                    </a></li>
                @endif

                {{-- Kelola Izin Absen --}}
                {{-- HANYA untuk masteradmin --}}
                @if($role === 'masteradmin')
                    <li><a href="{{ route('mastermind.izinabsen') }}" class="{{ request()->routeIs('mastermind.izinabsen') ? 'active' : '' }}">
                        <i data-lucide="lock" class="icon"></i>
                        <span class="menu-label">Kelola Izin Absen</span>
                    </a></li>
                @endif
            @endif {{-- End of !isPendingUser check --}}
        </ul>
    </div>
</div>
