@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="ppm-dashboard ppm-fade-in">
    <div class="ppm-dashboard-header">
        <h1>Selamat datang, {{ $user->full_name ?? $user->name }}</h1>
    </div>

    @switch($user->role)
        @case('masteradmin')
        @case('mastermind')
            <div class="ppm-dashboard-grid cols-3">
                <div class="ppm-card ppm-animated">
                    <h4>Jumlah Santri</h4>
                    <p>{{ $jumlahSantri ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Jumlah User Aktif</h4>
                    <p>{{ $jumlahUser ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Informasi Mastermind</h4>
                    <p>{{ $infoLainnya ?? '-' }}</p>
                </div>
            </div>
            @break

        @case('penerobos')
            <div class="ppm-dashboard-grid cols-2">
                <div class="ppm-card ppm-animated">
                    <h4>Absensi Anda</h4>
                    <p>{{ $absensiPersen ?? '-' }}%</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Jumlah Santri</h4>
                    <p>{{ $jumlahSantri ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Info Pondok</h4>
                    <p>{{ $infoPondok ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Materi Pengajian</h4>
                    <p>{{ $materiPengajian ?? '-' }}</p>
                </div>
            </div>
            <div class="ppm-dashboard-actions">
                <a href="{{ route('absensi.index') }}" class="ppm-btn-link">+ Tambah Absen</a>
                <a href="{{ route('santri') }}" class="ppm-btn-link">Data Santri & Penerobos</a>
            </div>
            @break

        @case('santri')
            <div class="ppm-dashboard-grid cols-2">
                <div class="ppm-card ppm-animated">
                    <h4>Absensi Anda</h4>
                    <p>{{ $absensiPersen ?? '-' }}%</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Info Pengajian</h4>
                    <p>{{ $infoPengajian ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Info Pondok</h4>
                    <p>{{ $infoLainnya ?? '-' }}</p>
                </div>
            </div>
            @break

        @case('dewanguru')
            <div class="ppm-dashboard-grid cols-2">
                <div class="ppm-card ppm-animated">
                    <h4>Info Pondok</h4>
                    <p>{{ $infoPondok ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Materi Pengajian</h4>
                    <p>{{ $materiPengajian ?? '-' }}</p>
                </div>
                <div class="ppm-card ppm-animated">
                    <h4>Jumlah Santri</h4>
                    <p>{{ $jumlahSantri ?? '-' }}</p>
                </div>
            </div>
            @break

        @case('pending')
            <div class="ppm-card ppm-centered-card ppm-animated">
                <h2>Akun Anda Belum Di-Approve</h2>
                <p>Mohon tunggu, akun Anda akan diproses oleh admin/mastermind.</p>
            </div>
            @break

        @default
            <div class="ppm-card ppm-centered-card ppm-animated">
                <h2>Role Tidak Dikenali</h2>
                <p>Silakan hubungi admin untuk bantuan lebih lanjut.</p>
            </div>
    @endswitch
</div>
@endsection
