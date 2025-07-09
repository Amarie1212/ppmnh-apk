@php
    $role = Auth::user()->role ?? '';
    $isAbsensiParentActive = request()->routeIs('absensi.index') || request()->routeIs('absensi.create');
@endphp

<div class="bottom-nav-wrapper">
    <div class="bottom-nav" id="bottom-nav">
        <ul class="bottom-nav-menu">
            {{-- Dashboard --}}
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="icon"></i>
                    <span class="menu-label">Dashboard</span>
                </a>
            </li>

            {{-- Absensi Menu (Single Link for all roles, based on your last provided code) --}}
            <li>
                <a href="{{ route('absensi.index') }}" class="{{ $isAbsensiParentActive ? 'active' : '' }}">
                    <i data-lucide="clipboard-list" class="icon"></i>
                    <span class="menu-label">Absensi</span>
                </a>
            </li>

            {{-- Data Santri --}}
            <li><a href="{{ route('santri') }}" class="{{ request()->routeIs('santri') ? 'active' : '' }}">
                <i data-lucide="users" class="icon"></i>
                <span class="menu-label">Data Santri</span>
            </a></li>

            {{-- Approve Role & Kelola Izin Absen --}}
            {{-- HANYA untuk masteradmin, mastermind, dan dewanguru --}}
            @if(in_array($role, ['masteradmin', 'mastermind', 'dewanguru']))
                <li><a href="{{ route('mastermind.approve') }}" class="{{ request()->routeIs('mastermind.approve') ? 'active' : '' }}">
                    <i data-lucide="user-check" class="icon"></i>
                    <span class="menu-label">Approve Role</span>
                </a></li>
                <li><a href="{{ route('mastermind.izinabsen') }}" class="{{ request()->routeIs('mastermind.izinabsen') ? 'active' : '' }}">
                    <i data-lucide="lock" class="icon"></i>
                    <span class="menu-label">Kelola Izin Absen</span>
                </a></li>
            @endif
        </ul>
    </div>
</div>
