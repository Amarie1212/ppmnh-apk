<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <script>
        // Atur tema awal sebelum konten tampil
        (function () {
            const theme = localStorage.getItem('theme') || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const finalTheme = theme === 'dark' || (theme === 'system' && prefersDark) ? 'dark' : 'light';
            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(finalTheme);
        })();
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="{{ request()->routeIs('login') || request()->routeIs('register') ? 'auth-page' : '' }}">

    @if (!request()->routeIs('login') && !request()->routeIs('register'))
        @include('layouts.headbar')
        {{-- Main content area needs padding at the bottom for the fixed navigation --}}
        <div class="main-layout-wrapper">
            <div class="content page-fade">
                @yield('content')
            </div>
        </div>
        @include('layouts.sidebar') {{-- Ini akan menjadi sidebar di desktop, bottom nav di mobile --}}
    @else
        {{-- Fullscreen khusus halaman login dan register --}}
        <div class="auth-container-fullscreen">
            @yield('content')
        </div>
    @endif

    {{-- PASTIKAN TIDAK ADA MOBILE POP-UP WRAPPER DI SINI LAGI --}}

    <script src="{{ asset('js/theme.js') }}"></script>
    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', function () {
            const savedTheme = localStorage.getItem('theme') || 'system';
            setTheme(savedTheme);
            document.body.classList.add('loaded');

            const content = document.querySelector('.page-fade');
            if (content) content.classList.add('in');

            // HAPUS SEMUA PANGGILAN JAVASCRIPT UNTUK DROPDOWN ABSENSI
            // setupAbsensiDesktopDropdown(); // Ini tidak lagi diperlukan
        });

        function setTheme(theme) {
            const html = document.documentElement;
            html.classList.remove('dark', 'light');

            if (theme === 'dark') html.classList.add('dark');
            else if (theme === 'light') html.classList.add('light');
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches)
                html.classList.add('dark');
            else html.classList.add('light');

            localStorage.setItem('theme', theme);
        }

        // Fungsi toggleProfileDropdown (tetap sama)
        function toggleProfileDropdown() {
            const el = document.getElementById('profileDropdown');
            if (el) {
                el.classList.toggle('show');
            }
        }

        document.addEventListener('click', function (event) {
            const trigger = document.querySelector('.profile-trigger');
            const dropdown = document.getElementById('profileDropdown');
            if (!trigger?.contains(event.target) && !dropdown?.contains(event.target)) {
                dropdown?.classList.remove('show');
            }
        });

        // =================================================================== //
        // === HAPUS SEMUA FUNGSI JAVASCRIPT TERKAIT DROPDOWN ABSENSI === //
        // =================================================================== //
        // function setupAbsensiDesktopDropdown() { ... } // Hapus seluruh fungsi ini
    </script>

    @stack('scripts')
</body>
</html>
