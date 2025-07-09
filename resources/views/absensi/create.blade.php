@extends('layouts.app')

@section('title', 'Tambah Absensi Baru')
<link rel="stylesheet" href="{{ asset('css/absensi.css') }}">
@section('content')
<div class="tambah-absen">
    <div class="tambah-absen-header">
        <h3>Tambah Absensi Baru</h3>
        <a href="{{ route('absensi.index') }}" class="profile-back-btn">← Kembali</a>
    </div>

    @if(session('success'))
        <div class="profile-alert-success" id="absensi-success-alert" style="display: none;">
            {{ session('success') }}
        </div>
    @endif

    <form id="absensiForm" action="{{ route('absensi.store') }}" method="POST">
        @csrf

        <div class="form-section">
            <div class="form-group">
                <label for="kategori">Pilih Kategori Absensi:</label>
                <select name="kategori" id="kategori" class="tambah-absen-input filter-input">
                    <option value="" disabled {{ old('kategori') ? '' : 'selected' }}>-- Pilih Kategori Kelas --</option>
                    <option value="apel_ggs" {{ old('kategori') == 'apel_ggs' ? 'selected' : '' }}>Apel GGS</option>
                    <option value="apel_qa" {{ old('kategori') == 'apel_qa' ? 'selected' : '' }}>Apel QA</option>
                    <option value="lambatan_ggs" {{ old('kategori') == 'lambatan_ggs' ? 'selected' : '' }}>Lambatan GGS</option>
                    <option value="lambatan_qa" {{ old('kategori') == 'lambatan_qa' ? 'selected' : '' }}>Lambatan QA</option>
                    <option value="cepatan" {{ old('kategori') == 'cepatan' ? 'selected' : '' }}>Cepatan</option>
                    <option value="mt" {{ old('kategori') == 'mt' ? 'selected' : '' }}>MT</option>
                </select>
            </div>
        </div>

        <div class="form-section date-activity-section">
            <div class="form-group">
                <label for="tanggal">Tanggal:</label>
                <input type="date" name="tanggal" id="tanggal" class="tambah-absen-input filter-input" value="{{ old('tanggal', \Carbon\Carbon::now()->format('Y-m-d')) }}" required>
            </div>
            <div class="form-group">
                <label for="kegiatan">Kegiatan:</label>
                <select name="kegiatan" id="kegiatan" class="tambah-absen-input filter-input">
                    <option value="" disabled {{ old('kegiatan') ? '' : 'selected' }}>-- Pilih Kegiatan --</option>
                    <option value="Apel" {{ old('kegiatan') == 'Apel' ? 'selected' : '' }}>Apel</option>
                    <option value="Ngaji Subuh" {{ old('kegiatan') == 'Ngaji Subuh' ? 'selected' : '' }}>Ngaji Subuh</option>
                    <option value="Ngaji Maghrib" {{ old('kegiatan') == 'Ngaji Maghrib' ? 'selected' : '' }}>Ngaji Maghrib</option>
                </select>
            </div>
        </div>

        <h4>Daftar Santri:</h4>
        <div class="tambah-absen-container fade-anim" style="overflow-x: auto;">
            <table class="santri-table">
                <thead>
                    <tr>
                        <th style="width: 60%; text-align: left;">Nama Santri</th>
                        <th style="width: 40%; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody id="santri-list-container">
                    <tr class="info-row">
                        <td colspan="2"><p class="info-message"></p></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="submit" class="tambah-absen-btn">Simpan Absensi</button>
    </form>
</div>

<script>
    const allRelevantUsers = @json($allRelevantUsers);
    const absensiDataBulanan = @json($absensiDataBulanan);

    const activityMap = {
        'apel_ggs': ['Apel'],
        'apel_qa': ['Apel'],
        'lambatan_ggs': ['Ngaji Subuh', 'Ngaji Maghrib'],
        'lambatan_qa': ['Ngaji Subuh', 'Ngaji Maghrib'],
        'cepatan': ['Ngaji Subuh', 'Ngaji Maghrib'],
        'mt': ['Ngaji Subuh', 'Ngaji Maghrib']
    };

    function updateKegiatanOptions() {
        const kategoriSelect = document.getElementById('kategori');
        const kegiatanSelect = document.getElementById('kegiatan');
        const selectedKategori = kategoriSelect.value;
        const currentKegiatan = kegiatanSelect.value;

        kegiatanSelect.innerHTML = '';

        const placeholderKegiatanOption = document.createElement('option');
        placeholderKegiatanOption.value = "";
        placeholderKegiatanOption.disabled = true;
        placeholderKegiatanOption.textContent = "-- Pilih Kegiatan --";

        let isKegiatanSelected = false;
        const options = activityMap[selectedKategori] || [];

        options.forEach(kegiatan => {
            const option = document.createElement('option');
            option.value = kegiatan;
            option.textContent = kegiatan;
            if (kegiatan === currentKegiatan) {
                option.selected = true;
                isKegiatanSelected = true;
            }
            kegiatanSelect.appendChild(option);
        });

        if (!isKegiatanSelected) {
            placeholderKegiatanOption.selected = true;
        }
        if (!kegiatanSelect.querySelector('option[value=""][disabled]')) {
            kegiatanSelect.prepend(placeholderKegiatanOption);
        }
    }

    function filterAndRenderSantri() {
        const kategori = document.getElementById('kategori').value;
        const tanggal = document.getElementById('tanggal').value;
        const kegiatan = document.getElementById('kegiatan').value;
        const santriListContainer = document.getElementById('santri-list-container');

        if (!kategori || !tanggal || !kegiatan) {
            santriListContainer.innerHTML = `
                <tr class="info-row">
                    <td colspan="2"><p class="info-message"></p></td>
                </tr>
            `;
            return;
        }

        const filteredUsers = allRelevantUsers.filter(user => {
            switch (kategori) {
                case 'apel_ggs': return user.jenis_kelamin === 'laki-laki';
                case 'apel_qa': return user.jenis_kelamin === 'perempuan';
                case 'lambatan_ggs': return user.kelas === 'lambatan' && user.jenis_kelamin === 'laki-laki';
                case 'lambatan_qa': return user.kelas === 'lambatan' && user.jenis_kelamin === 'perempuan';
                case 'cepatan': return user.kelas === 'cepatan';
                case 'mt': return user.kelas === 'mt';
                default: return false;
            }
        });

        let santriRowsHtml = '';
        if (filteredUsers.length > 0) {
            filteredUsers.forEach((user, index) => {
                const existingAbsence = absensiDataBulanan.find(abs =>
                    abs.user_id === user.id && abs.tanggal === tanggal && abs.kegiatan === kegiatan
                );
                const initialStatus = existingAbsence ? existingAbsence.status : null;

                santriRowsHtml += `
                <tr class="santri-row table-row-appear" style="animation-delay: ${index * 0.05}s;"> <td>
                        <input type="hidden" name="attendances[${user.id}][user_id]" value="${user.id}">
                        <input type="hidden" name="attendances[${user.id}][kelas]" value="${user.kelas}">
                        <div class="santri-info">
                            <span class="santri-name">${user.full_name}</span>
                            <span class="santri-kelas">(${user.kelas ? user.kelas.toUpperCase() : 'N/A'})</span>
                        </div>
                    </td>
                    <td>
                        <div class="status-selection">
                            <button type="button" class="status-btn ${initialStatus === 'hadir' ? 'active' : ''}" data-status="hadir">H</button>
                            <button type="button" class="status-btn ${initialStatus === 'telat' ? 'active' : ''}" data-status="telat">T</button>
                            <button type="button" class="status-btn ${initialStatus === 'izin' ? 'active' : ''}" data-status="izin">I</button>
                            <button type="button" class="status-btn ${initialStatus === 'alpha' ? 'active' : ''}" data-status="alpha">A</button>
                            <button type="button" class="status-btn ${initialStatus === 'sakit' ? 'active' : ''}" data-status="sakit">S</button>
                            <input type="hidden" name="attendances[${user.id}][status]" class="status-input" value="${initialStatus || ''}">
                        </div>
                    </td>
                </tr>
                `;
            });
        } else {
            santriRowsHtml = `
                <tr class="info-row">
                    <td colspan="2"><p class="info-message">Tidak ada santri yang ditemukan untuk kriteria ini.</p></td>
                </tr>
            `;
        }
        santriListContainer.innerHTML = santriRowsHtml;
        attachStatusButtonListeners();
    }

    function attachStatusButtonListeners() {
        document.querySelectorAll('.santri-row .status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const parentRow = this.closest('.santri-row');
                const statusInput = parentRow.querySelector('.status-input');

                parentRow.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                statusInput.value = this.dataset.status;
            });
        });
    }

    document.getElementById('kategori').addEventListener('change', () => {
        updateKegiatanOptions();
        filterAndRenderSantri();
    });
    document.getElementById('tanggal').addEventListener('change', filterAndRenderSantri);
    document.getElementById('kegiatan').addEventListener('change', filterAndRenderSantri);

    document.addEventListener('DOMContentLoaded', () => {
        const kategoriSelect = document.getElementById('kategori');
        const kegiatanSelect = document.getElementById('kegiatan');

        if ("{{ old('kategori') }}" === '' && kategoriSelect.value !== '') {
            kategoriSelect.value = '';
            const placeholderKategoriOption = kategoriSelect.querySelector('option[value=""][disabled]');
            if (placeholderKategoriOption) {
                placeholderKategoriOption.selected = true;
            }
        }

        updateKegiatanOptions();
        filterAndRenderSantri();

        const successAlert = document.getElementById('absensi-success-alert');
        if (successAlert) {
            successAlert.style.display = 'block';
            void successAlert.offsetWidth;
            successAlert.classList.add('show-alert');

            setTimeout(() => {
                successAlert.classList.remove('show-alert');
                successAlert.classList.add('hide-alert');

                successAlert.addEventListener('transitionend', function handler() {
                    successAlert.removeEventListener('transitionend', handler);
                    successAlert.remove();
                    window.location.href = "{{ route('absensi.index') }}";
                });
            }, 3000);
        }
    });
</script>
@endsection
