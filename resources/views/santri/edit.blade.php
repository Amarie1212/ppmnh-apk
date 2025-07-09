@extends('layouts.app')

@section('title', 'Edit Profil User')
<link rel="stylesheet" href="{{ asset('css/santri.css') }}">

@section('content')
<div class="profile-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="profile-header" style="margin: 0;">Edit Profil: {{ $user->full_name ?? $user->name }}</h3>
        <a href="{{ route('santri') }}" class="profile-back-btn" style="text-decoration:none; color: var(--accent); font-weight: 600;">← Kembali</a>
    </div>

    @if(session('success'))
    <div class="profile-alert-success" id="successAlert" style="display: none;">
        {{ session('success') }}
    </div>
    @endif

    <div class="profile-table-wrapper fade-anim">
        <form action="{{ route('profile.updateUser', $user->id) }}" method="POST" id="editForm">
            @csrf
            <table class="profile-table">
                <tbody>
                    <tr>
                        <th>Nama Lengkap</th>
                        <td><input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}"></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><input type="email" name="email" value="{{ old('email', $user->email) }}"></td>
                    </tr>
                    <tr>
                        <th>Jenis Kelamin</th>
                        <td>
                            <select name="jenis_kelamin">
                                <option value="laki-laki" {{ (old('jenis_kelamin', $user->jenis_kelamin) === 'laki-laki') ? 'selected' : '' }}>Laki-laki</option>
                                <option value="perempuan" {{ (old('jenis_kelamin', $user->jenis_kelamin) === 'perempuan') ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>No HP</th>
                        <td><input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"></td>
                    </tr>
                    <tr>
                        <th>No HP Orang Tua</th>
                        <td><input type="text" name="no_hp_ortu" value="{{ old('no_hp_ortu', $user->no_hp_ortu) }}"></td>
                    </tr>
                    <tr>
                        <th>Tempat Lahir</th>
                        <td><input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $user->tempat_lahir) }}"></td>
                    </tr>
                    <tr>
                        <th>Tanggal Lahir</th>
                        <td><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $user->tanggal_lahir) }}"></td>
                    </tr>
                    <tr>
                        <th>Asal Daerah</th>
                        <td><input type="text" name="asal_daerah" value="{{ old('asal_daerah', $user->asal_daerah) }}"></td>
                    </tr>
                    <tr>
                        <th>Asal Desa</th>
                        <td><input type="text" name="asal_desa" value="{{ old('asal_desa', $user->asal_desa) }}"></td>
                    </tr>
                    <tr>
                        <th>Asal Kelompok</th>
                        <td><input type="text" name="asal_kelompok" value="{{ old('asal_kelompok', $user->asal_kelompok) }}"></td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td>
                            <select name="kelas">
                                <option value="mt" {{ (old('kelas', $user->kelas) === 'mt') ? 'selected' : '' }}>MT</option>
                                <option value="cepatan" {{ (old('kelas', $user->kelas) === 'cepatan') ? 'selected' : '' }}>Cepatan</option>
                                <option value="lambatan" {{ (old('kelas', $user->kelas) === 'lambatan') ? 'selected' : '' }}>Lambatan</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td>
                            <select name="role">
                                <option value="santri" {{ $user->role === 'santri' ? 'selected' : '' }}>Santri</option>
                                <option value="penerobos" {{ $user->role === 'penerobos' ? 'selected' : '' }}>Penerobos</option>
                                <option value="dewanguru" {{ $user->role === 'dewanguru' ? 'selected' : '' }}>Dewan Guru</option>
                                <option value="masteradmin" {{ $user->role === 'masteradmin' ? 'selected' : '' }}>Masteradmin</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td style="text-align: left;">
                            <button type="button" id="btnDelete" class="btn-delete">Hapus</button>
                        </td>
                        <td class="text-right">
                            <button type="submit" class="profile-btn">Simpan</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>

        <form id="deleteForm" action="{{ route('santri.delete', $user->id) }}" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<div id="confirmModal" class="modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div style="background:white; padding:20px; border-radius:8px; max-width: 400px; width: 90%;">
        <p>Yakin ingin menghapus user ini?</p>
        <div style="text-align:right;">
            <button id="confirmNo" class="btn-cancel" style="margin-right:10px;">Batal</button>
            <button id="confirmYes" class="btn-confirm">Ya, Hapus</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const alert = document.getElementById('successAlert');

        if (alert) {
            alert.style.display = 'block';
            void alert.offsetWidth;

            alert.classList.add('show-alert');

            setTimeout(() => {
                alert.classList.remove('show-alert');
                alert.classList.add('hide-alert');

                alert.addEventListener('transitionend', function handler() {
                    alert.removeEventListener('transitionend', handler);
                    alert.style.display = 'none';
                });
            }, 3000);
        }

        const btnDelete = document.getElementById('btnDelete');
        const modal = document.getElementById('confirmModal');
        const btnYes = document.getElementById('confirmYes');
        const btnNo = document.getElementById('confirmNo');
        const deleteForm = document.getElementById('deleteForm');

        btnDelete.addEventListener('click', () => {
            modal.style.display = 'flex';
        });

        btnNo.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        btnYes.addEventListener('click', () => {
            deleteForm.submit();
        });

        window.addEventListener('click', e => {
            if(e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>
@endpush
