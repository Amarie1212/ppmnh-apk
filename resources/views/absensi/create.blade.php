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
                {{-- Set the value directly in the HTML using the desired display format for initial render --}}
                <input type="text" id="tanggal" class="tambah-absen-input filter-input"
                       value="{{ \Carbon\Carbon::parse(old('tanggal', \Carbon\Carbon::now()->format('Y-m-d')))->isoFormat('dddd, DD MMMM YYYY') }}"
                       placeholder="Hari, DD Bulan YYYY" required>
                <input type="hidden" name="tanggal" id="tanggal_hidden" value="{{ old('tanggal', \Carbon\Carbon::now()->format('Y-m-d')) }}">
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

    // New: Map kegiatan based on day of the week
    const dayBasedActivityMap = {
        'Minggu': ['Ngaji Maghrib'], // Sunday
        'Senin': ['Apel', 'Ngaji Subuh', 'Ngaji Maghrib'], // Monday
        'Selasa': ['Apel', 'Ngaji Subuh', 'Ngaji Maghrib'], // Tuesday
        'Rabu': ['Apel', 'Ngaji Subuh', 'Ngaji Maghrib'], // Wednesday
        'Kamis': ['Apel', 'Ngaji Subuh', 'Ngaji Maghrib'], // Thursday
        'Jumat': ['Apel', 'Ngaji Subuh', 'Ngaji Maghrib'], // Friday
        'Sabtu': ['Apel', 'Ngaji Subuh'] // Saturday
    };

    // Define all possible activities to use when no category is selected
    const ALL_POSSIBLE_ACTIVITIES = ['Apel', 'Ngaji Subuh', 'Ngaji Maghrib'];

    function updateKegiatanOptions() {
        const kategoriSelect = document.getElementById('kategori');
        const kegiatanSelect = document.getElementById('kegiatan');
        const tanggalHiddenInput = document.getElementById('tanggal_hidden');
        const selectedKategori = kategoriSelect.value;
        const currentKegiatan = kegiatanSelect.value; // Store current selection before clearing

        kegiatanSelect.innerHTML = '';

        const placeholderKegiatanOption = document.createElement('option');
        placeholderKegiatanOption.value = "";
        placeholderKegiatanOption.disabled = true;
        placeholderKegiatanOption.textContent = "-- Pilih Kegiatan --";

        let activitiesBasedOnCategory = [];
        if (selectedKategori && activityMap[selectedKategori]) {
            activitiesBasedOnCategory = activityMap[selectedKategori];
        } else {
            // If no specific category is selected, consider all general activities.
            activitiesBasedOnCategory = ALL_POSSIBLE_ACTIVITIES;
        }

        let activitiesBasedOnDay = [];
        if (tanggalHiddenInput.value) {
            const selectedDate = new Date(tanggalHiddenInput.value);
            // Check for valid date
            if (isNaN(selectedDate.getTime())) {
                activitiesBasedOnDay = ALL_POSSIBLE_ACTIVITIES; // Fallback if date is invalid
            } else {
                const options = { weekday: 'long' };
                const dayName = selectedDate.toLocaleDateString('id-ID', options);

                if (dayBasedActivityMap[dayName]) {
                    activitiesBasedOnDay = dayBasedActivityMap[dayName];
                } else {
                    // Fallback: If day is not defined in map, allow all activities.
                    activitiesBasedOnDay = ALL_POSSIBLE_ACTIVITIES;
                }
            }
        } else {
            // If no date is selected (shouldn't happen often with defaultDate)
            activitiesBasedOnDay = ALL_POSSIBLE_ACTIVITIES;
        }

        // Intersect the two sets of activities (category-based and day-based)
        const finalPossibleActivities = activitiesBasedOnCategory.filter(activity =>
            activitiesBasedOnDay.includes(activity)
        );

        let isKegiatanSelected = false;
        finalPossibleActivities.forEach(kegiatan => {
            const option = document.createElement('option');
            option.value = kegiatan;
            option.textContent = kegiatan;
            if (kegiatan === currentKegiatan) { // Re-select the previously selected activity if still valid
                option.selected = true;
                isKegiatanSelected = true;
            }
            kegiatanSelect.appendChild(option);
        });

        // Always prepend placeholder. Select it if no activity is pre-selected or no activities are available.
        kegiatanSelect.prepend(placeholderKegiatanOption);
        if (!isKegiatanSelected || finalPossibleActivities.length === 0) {
            placeholderKegiatanOption.selected = true;
        }
    }

    function filterAndRenderSantri() {
        const kategori = document.getElementById('kategori').value;
        const tanggal = document.getElementById('tanggal_hidden').value;
        const kegiatan = document.getElementById('kegiatan').value;
        const santriListContainer = document.getElementById('santri-list-container');

        // Added 'Pilih Kegiatan' check here, as per your request, santri list should be empty if 'kegiatan' isn't chosen
        if (!kategori || !tanggal || !kegiatan) {
            santriListContainer.innerHTML = `
                <tr class="info-row">
                    <td colspan="2"><p class="info-message">Pilih Kategori, Tanggal, dan Kegiatan untuk melihat daftar santri.</p></td>
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
                <tr class="santri-row table-row-appear" style="animation-delay: ${index * 0.05}s;">
                    <td>
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
    document.getElementById('kegiatan').addEventListener('change', filterAndRenderSantri);

    document.addEventListener('DOMContentLoaded', () => {
        const kategoriSelect = document.getElementById('kategori');
        const kegiatanSelect = document.getElementById('kegiatan');
        const tanggalInput = document.getElementById('tanggal');
        const tanggalHiddenInput = document.getElementById('tanggal_hidden');

        // Ensure category placeholder is selected initially if no old value
        if ("{{ old('kategori') }}" === '') {
            kategoriSelect.value = '';
            const placeholderKategoriOption = kategoriSelect.querySelector('option[value=""][disabled]');
            if (placeholderKategoriOption) {
                placeholderKategoriOption.selected = true;
            }
        }

        // Initialize Flatpickr
        const fp = flatpickr("#tanggal", {
            locale: "id", // Set locale to Indonesian
            dateFormat: "Y-m-d", // Actual format for internal use/hidden input
            altInput: true, // Enable alternative input (the one user sees)
            altFormat: "l, d F Y", // Desired display format: Hari, DD Bulan YYYY
            defaultDate: tanggalHiddenInput.value, // Set default date from old('tanggal') or current date
            onReady: function(selectedDates, dateStr, instance) {
                // Ensure hidden input is correctly set on load.
                // This might seem redundant, but ensures consistency in case of browser quirks.
                if (selectedDates.length > 0) {
                    tanggalHiddenInput.value = instance.formatDate(selectedDates[0], "Y-m-d");
                    // Explicitly update the altInput to ensure it reflects the formatted date immediately
                    instance.altInput.value = instance.formatDate(selectedDates[0], "l, d F Y");
                }
                updateKegiatanOptions(); // Update kegiatan options based on initial date
                filterAndRenderSantri(); // Filter santri when Flatpickr is ready
            },
            onChange: function(selectedDates, dateStr, instance) {
                // Update the hidden input value when date changes
                if (selectedDates.length > 0) {
                    tanggalHiddenInput.value = instance.formatDate(selectedDates[0], "Y-m-d");
                }
                updateKegiatanOptions(); // Update kegiatan options based on new date
                filterAndRenderSantri(); // Re-filter santri based on new date
            }
        });

        // If for some reason Flatpickr's altInput isn't immediately reflecting the defaultDate on page load,
        // manually set it after a very short delay or directly after initialization.
        // This is a common workaround for rendering inconsistencies on different browsers/devices.
        // For instance, if the server renders the date as 'YYYY-MM-DD' in 'tanggal_hidden',
        // but the browser's default text input tries to format it differently before Flatpickr takes over.
        // By explicitly setting altInput.value here, we ensure it's always in the desired format.
        if (fp && fp.selectedDates.length > 0) {
            fp.altInput.value = fp.formatDate(fp.selectedDates[0], "l, d F Y");
        }


        // Initial call to set options based on pre-selected category and date
        // This is important if `old('kategori')` or `old('tanggal')` has a value.
        // The calls within onReady/onChange ensure this happens on date selection/change.
        // For the initial load, it's covered by onReady.
        // updateKegiatanOptions(); // This call is redundant due to onReady.

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
