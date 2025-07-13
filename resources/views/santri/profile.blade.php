@extends('layouts.app')

@section('title', 'Account Preferences')
<link rel="stylesheet" href="{{ asset('css/santri.css') }}">

@section('content')
<div class="profile-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 class="profile-header" style="margin: 0;">Edit Profil</h3>
        <a href="{{ session('previous_url', url('/')) }}" class="profile-back-btn" style="text-decoration:none; color: var(--accent); font-weight: 600;">← Kembali</a>
    </div>

    {{-- Notifikasi Toast (akan dikelola JS) --}}
    {{-- Ini adalah elemen toast utama, akan diisi dan ditampilkan/disembunyikan oleh JS --}}
    <div id="statusToast" class="profile-alert-base" style="display: none;">
        <i id="toastIcon" class="alert-icon" data-lucide=""></i> {{-- Ikon akan diatur oleh JS --}}
        <span id="toastMessage"></span> {{-- Pesan akan diatur oleh JS (disembunyikan di CSS) --}}
    </div>

    <div class="profile-table-wrapper fade-anim">
        <form action="{{ route('profile.update') }}" method="POST">
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
                            {{-- Changed: Added disabled attribute to prevent editing --}}
                            <select name="jenis_kelamin" disabled>
                                <option value="laki-laki" {{ $user->jenis_kelamin === 'laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="perempuan" {{ $user->jenis_kelamin === 'perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            {{-- Added: Hidden input to ensure the value is still submitted --}}
                            <input type="hidden" name="jenis_kelamin" value="{{ $user->jenis_kelamin }}">
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

                    {{-- Class field display logic --}}
                    @php $loggedInUserRole = Auth::user()->role; @endphp
                    <tr>
                        <th>Kelas</th>
                        <td>
                            {{-- Disable class select if logged-in user is santri OR dewanguru --}}
                            <select name="kelas" {{ in_array($loggedInUserRole, ['santri', 'dewanguru']) ? 'disabled' : '' }}>
                                <option value="mt" {{ old('kelas', $user->kelas) === 'mt' ? 'selected' : '' }}>MT</option>
                                <option value="cepatan" {{ old('kelas', $user->kelas) === 'cepatan' ? 'selected' : '' }}>Cepatan</option>
                                <option value="lambatan" {{ old('kelas', $user->kelas) === 'lambatan' ? 'selected' : '' }}>Lambatan</option>
                                {{-- Only Masteradmin can see and select 'guru' as a class --}}
                                @if($loggedInUserRole === 'masteradmin')
                                    <option value="guru" {{ old('kelas', $user->kelas) === 'guru' ? 'selected' : '' }}>Guru</option>
                                @endif
                            </select>
                            {{-- Always include a hidden input for the value if disabled --}}
                            @if(in_array($loggedInUserRole, ['santri', 'dewanguru']))
                                <input type="hidden" name="kelas" value="{{ $user->kelas }}">
                            @endif
                        </td>
                    </tr>

                    {{-- Role field display logic --}}
                    <tr>
                        <th>Role</th>
                        <td>
                            {{-- Disable role select if logged-in user is santri OR if dewanguru is editing themselves --}}
                            <select name="role" {{ ($loggedInUserRole === 'santri' || ($loggedInUserRole === 'dewanguru' && $user->role === 'dewanguru')) ? 'disabled' : '' }}>
                                <option value="santri" {{ $user->role === 'santri' ? 'selected' : '' }}>Santri</option>

                                @if(in_array($loggedInUserRole, ['masteradmin', 'penerobos', 'dewanguru']))
                                    <option value="penerobos" {{ $user->role === 'penerobos' ? 'selected' : '' }}>Penerobos</option>
                                @endif

                                @if(in_array($loggedInUserRole, ['masteradmin', 'dewanguru']))
                                    <option value="dewanguru" {{ $user->role === 'dewanguru' ? 'selected' : '' }}>Dewan Guru</option>
                                @endif

                                @if($loggedInUserRole === 'masteradmin')
                                    <option value="masteradmin" {{ $user->role === 'masteradmin' ? 'selected' : '' }}>Masteradmin</option>
                                @endif
                            </select>
                            {{-- Always include a hidden input for the value if the select is disabled --}}
                            @if($loggedInUserRole === 'santri' || ($loggedInUserRole === 'dewanguru' && $user->role === 'dewanguru'))
                                <input type="hidden" name="role" value="{{ $user->role }}">
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" class="text-right">
                            <button type="submit" class="profile-btn">Simpan</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
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
    });
</script>
@endpush
