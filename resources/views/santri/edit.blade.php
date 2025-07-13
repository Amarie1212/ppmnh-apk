@extends('layouts.app')

@section('title', 'Edit Profil User')
<link rel="stylesheet" href="{{ asset('css/santri.css') }}">

@section('content')
<div class="profile-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="profile-header" style="margin: 0;">Edit Profil: {{ $user->full_name ?? $user->name }}</h3>
        {{-- Link kembali ke halaman daftar santri, asumsi ini adalah halaman dari mana user ini diakses --}}
        <a href="{{ route('santri') }}" class="profile-back-btn" style="text-decoration:none; color: var(--accent); font-weight: 600;">← Kembali</a>
    </div>

    {{-- Notifikasi Toast (akan dikelola JS) --}}
    {{-- Ini adalah elemen toast utama, akan diisi dan ditampilkan/disembunyikan oleh JS --}}
    <div id="statusToast" class="profile-alert-base" style="display: none;">
        <i id="toastIcon" class="alert-icon" data-lucide=""></i> {{-- Ikon akan diatur oleh JS --}}
        <span id="toastMessage"></span> {{-- Pesan akan diatur oleh JS (disembunyikan di CSS) --}}
    </div>

    <div class="profile-table-wrapper fade-anim">
        {{-- Get the role of the currently logged-in user --}}
        @php $loggedInUserRole = Auth::user()->role; @endphp

        {{-- Access to this form is allowed for masteradmin, dewanguru, and penerobos. Santri are blocked. --}}
        @if(in_array($loggedInUserRole, ['masteradmin', 'dewanguru', 'penerobos']))
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
                                    <option value="mt" {{ old('kelas', $user->kelas) === 'mt' ? 'selected' : '' }}>MT</option>
                                    <option value="cepatan" {{ old('kelas', $user->kelas) === 'cepatan' ? 'selected' : '' }}>Cepatan</option>
                                    <option value="lambatan" {{ old('kelas', $user->kelas) === 'lambatan' ? 'selected' : '' }}>Lambatan</option>

                                    {{-- 'guru' class option: only visible for masteradmin, OR if the current user being edited has 'guru' class --}}
                                    @if($loggedInUserRole === 'masteradmin' || $user->kelas === 'guru')
                                        <option value="guru" {{ old('kelas', $user->kelas) === 'guru' ? 'selected' : '' }}>Guru</option>
                                    @endif
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Role</th>
                            <td>
                                <select name="role">
                                    <option value="santri" {{ $user->role === 'santri' ? 'selected' : '' }}>Santri</option>

                                    {{-- Penerobos option --}}
                                    @if(in_array($loggedInUserRole, ['masteradmin', 'dewanguru', 'penerobos']) || $user->role === 'penerobos')
                                        <option value="penerobos" {{ $user->role === 'penerobos' ? 'selected' : '' }}>Penerobos</option>
                                    @endif

                                    {{-- Dewan Guru option --}}
                                    @if(in_array($loggedInUserRole, ['masteradmin', 'dewanguru']) || $user->role === 'dewanguru')
                                        <option value="dewanguru" {{ $user->role === 'dewanguru' ? 'selected' : '' }}>Dewan Guru</option>
                                    @endif

                                    {{-- Masteradmin option --}}
                                    @if($loggedInUserRole === 'masteradmin' || $user->role === 'masteradmin')
                                        <option value="masteradmin" {{ $user->role === 'masteradmin' ? 'selected' : '' }}>Masteradmin</option>
                                    @endif
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align: left;">
                                {{-- Only Masteradmin, Dewan Guru, and Penerobos can delete users --}}
                                @if(in_array($loggedInUserRole, ['masteradmin', 'dewanguru', 'penerobos']))
                                    <button type="button" id="btnDelete" class="btn-delete">Hapus</button>
                                @endif
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
        @else
            {{-- Message if user does not have permission to access this page --}}
            <p>Anda tidak memiliki izin untuk mengedit profil pengguna lain.</p>
        @endif
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
        const statusToast = document.getElementById('statusToast');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');

        // Function to display toast notification
        function showToast(message, type = 'success') { // type can be 'success' or 'error'
            statusToast.classList.remove('profile-alert-success', 'profile-alert-error');
            statusToast.classList.add(`profile-alert-${type}`);

            // Set icon based on type
            if (type === 'success') {
                toastIcon.setAttribute('data-lucide', 'check-circle');
            } else if (type === 'error') {
                toastIcon.setAttribute('data-lucide', 'x-circle');
            }
            // Re-render Lucide icons if they are dynamically added/changed
            if (window.lucide) {
                window.lucide.createIcons();
            }

            toastMessage.textContent = message; // Set message text (optional, but good for debugging)
            toastMessage.style.display = 'none'; // Hide text if only icon is desired for success/error

            statusToast.style.display = 'flex'; // Use flex to center icon
            void statusToast.offsetWidth; // Trigger reflow for animation
            statusToast.classList.add('show-alert');

            setTimeout(() => {
                statusToast.classList.remove('show-alert');
                statusToast.classList.add('hide-alert');

                statusToast.addEventListener('transitionend', function handler() {
                    statusToast.style.display = 'none';
                    statusToast.removeEventListener('transitionend', handler);
                }, { once: true }); // Use { once: true } to remove listener automatically
            }, 3000); // Display duration
        }

        // Check for session messages on page load
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @elseif(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif

        const btnDelete = document.getElementById('btnDelete');
        const modal = document.getElementById('confirmModal');
        const btnYes = document.getElementById('confirmYes');
        const btnNo = document.getElementById('confirmNo');
        const deleteForm = document.getElementById('deleteForm');

        // Only add event listeners if the elements exist
        if (btnDelete) {
            btnDelete.addEventListener('click', () => {
                modal.style.display = 'flex';
            });
        }

        if (btnNo) {
            btnNo.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        if (btnYes) {
            btnYes.addEventListener('click', () => {
                deleteForm.submit();
            });
        }

        // Hide modal if clicked outside
        window.addEventListener('click', e => {
            if(e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>
@endpush
