@extends('layouts.app')

@section('title', 'Riwayat Absensi')
<link rel="stylesheet" href="{{ asset('css/absensi.css') }}">
{{-- Pastikan Font Awesome sudah terhubung --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@section('content')
@php
    function formatStatus($status) {
        switch (strtolower($status)) {
            case 'hadir': return 'P';
            case 'telat': return 'T';
            case 'izin': return 'I';
            case 'sakit': return 'S';
            case 'alpha': return 'A';
            case '-': return '-';
            case '': return '-';
            default: return $status;
        }
    }

    // Definisikan nama-nama hari di PHP untuk memudahkan akses
    $namaHariLengkap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'];
@endphp
<div class="absensi-container">
    <div class="absensi-header">
        <h3>Riwayat Absensi</h3>
        @if($bolehTambahAbsensi)
            <a href="{{ route('absensi.create') }}" class="ppm-btn-link desktop-only-button">
                <span class="button-text">Presensi</span>
            </a>
        @endif
    </div>
    <form method="GET" id="filterForm" class="filter-area">
        <label>Pilih Bulan:
            {{-- Hidden input for the actual month value --}}
            <input type="month" name="bulan" id="bulanInput" class="filter-input" value="{{ $bulan }}" style="position: absolute; opacity: 0; pointer-events: none; width: 0; height: 0;">
            {{-- Display input to mimic the desired UI --}}
            <input type="text" id="bulanDisplay" class="filter-input date-display-input" readonly>
        </label>
        @if(Auth::user()->role !== 'santri')
            <div class="filter-name-and-button-row">
                <label>
                    <input type="text" name="nama" class="filter-input" placeholder="Cari nama..." value="{{ $nama }}">
                </label>
                <button type="submit" class="filter-btn">Filter</button>
            </div>
        @endif
        <input type="hidden" name="kategori" id="kategoriInput" value="{{ $kategori }}">
        <input type="hidden" name="minggu" id="mingguInput" value="{{ $mingguAktif }}">
    </form>

    {{-- Ganti notifikasi lama dengan struktur toast baru --}}
    <div id="statusToast" class="profile-alert-base" style="display: none;">
        <i id="toastIcon" class="alert-icon"></i> {{-- Ikon akan diatur oleh JS --}}
        <span id="toastMessage"></span> {{-- Pesan akan diatur oleh JS (disembunyikan di CSS) --}}
    </div>

    <div class="tab-buttons-wrapper">
        <div class="tab-buttons">
            @foreach ($allowedTabs as $key => $label)
                <button type="button" class="tab {{ $kategori == $key ? 'active' : '' }}" data-kategori="{{ $key }}">{{ $label }}</button>
            @endforeach
            <div class="tab-indicator"></div>
        </div>
    </div>
    <div class="absensi-table-wrapper fade-anim" id="absensi-table-wrapper">
        <div class="table-info-header">
            <div class="minggu-label">
                Minggu ke-{{ $mingguAktif }} <small>({{ $tanggalInfo['rentang'] }})</small>
            </div>
            <div class="pagination">
                @for($i = 1; $i <= $jumlahMinggu; $i++)
                    <button class="page {{ $mingguAktif == $i ? 'active' : '' }}" onclick="gantiMinggu({{ $i }})">{{ $i }}</button>
                @endfor
            </div>
        </div>
        <div class="absensi-table-content-wrapper">
            <table class="absensi-table">
                <thead>
                    <tr>
                        <th class="sticky-col sticky-col-no">NO</th>
                        <th class="sticky-col sticky-col-nama">NAMA</th>

                        @if (in_array($kategori, ['apel_ggs', 'apel_qa']))
                            @foreach ([1, 2, 3, 4, 5, 6] as $dayIndex) {{-- Senin (1) s/d Sabtu (6) --}}
                                <th>{{ $namaHariLengkap[$dayIndex] }}<br><small>{{ (int)$tanggalInfo['tanggalHari'][$dayIndex] }}</small></th>
                            @endforeach
                        @else
                            <th>{{ $namaHariLengkap[0] }}<br><small>{{ (int)$tanggalInfo['tanggalHari'][0] }}</small></th> {{-- Minggu (0) --}}
                            @foreach ([1, 2, 3, 4, 5] as $dayIndex) {{-- Senin (1) s/d Jumat (5) --}}
                                <th colspan="2">{{ $namaHariLengkap[$dayIndex] }}<br><small>{{ (int)$tanggalInfo['tanggalHari'][$dayIndex] }}</small></th>
                            @endforeach
                            <th>{{ $namaHariLengkap[6] }}<br><small>{{ (int)$tanggalInfo['tanggalHari'][6] }}</small></th> {{-- Sabtu (6) --}}
                        @endif

                        <th>AKSI</th>
                    </tr>

                    @if (!in_array($kategori, ['apel_ggs', 'apel_qa']))
                        <tr>
                            <th class="sticky-col sticky-col-no-sub"></th>
                            <th class="sticky-col sticky-col-nama-sub"></th>
                            <th>M</th> {{-- Minggu Maghrib --}}
                            @foreach ([1, 2, 3, 4, 5] as $dayIndex) {{-- Senin s/d Jumat --}}
                                <th>S</th> {{-- Subuh --}}
                                <th>M</th> {{-- Maghrib --}}
                            @endforeach
                            <th>S</th> {{-- Sabtu Subuh --}}
                            <th></th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @forelse ($absensi as $i => $row)
                    <tr>
                        <td class="sticky-col sticky-col-no">{{ $i + 1 }}</td>
                        <td class="sticky-col sticky-col-nama">{{ $row->user->full_name ?? $row->user->name }}</td>
                        @if (in_array($kategori, ['apel_ggs', 'apel_qa']))
                            <td data-label="Senin">{{ formatStatus($row->senin ?? '-') }}</td>
                            <td data-label="Selasa">{{ formatStatus($row->selasa ?? '-') }}</td>
                            <td data-label="Rabu">{{ formatStatus($row->rabu ?? '-') }}</td>
                            <td data-label="Kamis">{{ formatStatus($row->kamis ?? '-') }}</td>
                            <td data-label="Jumat">{{ formatStatus($row->jumat ?? '-') }}</td>
                            <td data-label="Sabtu">{{ formatStatus($row->sabtu ?? '-') }}</td>
                        @else
                            <td data-label="Minggu Maghrib">{{ formatStatus($row->minggu_m ?? '-') }}</td>
                            <td data-label="Senin Subuh">{{ formatStatus($row->senin ?? '-') }}</td>
                            <td data-label="Senin Maghrib">{{ formatStatus($row->senin_m ?? '-') }}</td>
                            <td data-label="Selasa Subuh">{{ formatStatus($row->selasa ?? '-') }}</td>
                            <td data-label="Selasa Maghrib">{{ formatStatus($row->selasa_m ?? '-') }}</td>
                            <td data-label="Rabu Subuh">{{ formatStatus($row->rabu ?? '-') }}</td>
                            <td data-label="Rabu Maghrib">{{ formatStatus($row->rabu_m ?? '-') }}</td>
                            <td data-label="Kamis Subuh">{{ formatStatus($row->kamis ?? '-') }}</td>
                            <td data-label="Kamis Maghrib">{{ formatStatus($row->kamis_m ?? '-') }}</td>
                            <td data-label="Jumat Subuh">{{ formatStatus($row->jumat ?? '-') }}</td>
                            <td data-label="Jumat Maghrib">{{ formatStatus($row->jumat_m ?? '-') }}</td>
                            <td data-label="Sabtu Subuh">{{ formatStatus($row->sabtu ?? '-') }}</td>
                        @endif
                        <td>
                            @if(Auth::user()->role === 'masteradmin' || (Auth::user()->role === 'penerobos' && Auth::user()->boleh_tambah_absen))
                                <a href="{{ route('absensi.edit', [
                                    'user' => $row->user->id,
                                    'bulan' => $bulan,
                                    'minggu' => $mingguAktif,
                                    'kategori' => $kategori
                                ]) }}" class="btn-edit">
                                    Edit
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                        @empty
                            <tr>
                                @if (in_array($kategori, ['apel_ggs', 'apel_qa']))
                                    <td colspan="9">Tidak ada data</td>
                                @else
                                    <td colspan="15">Tidak ada data</td>
                                @endif
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let mingguSebelumnya = parseInt(document.getElementById('mingguInput').value);
const tabIndicator = document.querySelector('.tab-indicator');
const absensiTableWrapper = document.getElementById('absensi-table-wrapper');
const absensiTableContentWrapper = document.querySelector('.absensi-table-content-wrapper');
const absensiContainer = document.querySelector('.absensi-container');
const tabButtonsWrapper = document.querySelector('.tab-buttons-wrapper');

// New elements for month display
const bulanInput = document.getElementById('bulanInput');
const bulanDisplay = document.getElementById('bulanDisplay');

// Function to format month for display (e.g., "Juli 2025")
function formatMonthDisplay(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString + '-01'); // Append '-01' to make it a valid date for parsing
    const options = { year: 'numeric', month: 'long' };
    return date.toLocaleDateString('id-ID', options);
}

// Set initial display value
bulanDisplay.value = formatMonthDisplay(bulanInput.value);

// Open the month picker when the display input is clicked
bulanDisplay.addEventListener('click', () => {
    bulanInput.showPicker(); // This method works in most modern browsers
});

bulanInput.addEventListener('change', () => {
    // Update the display input when the hidden month input changes
    bulanDisplay.value = formatMonthDisplay(bulanInput.value);

    // Existing logic for month change
    document.getElementById('mingguInput').value = 1; // Reset to week 1 when changing month
    mingguSebelumnya = 1;
    localStorage.setItem('absensiMingguAktif', '1');

    if (absensiContainer) {
        absensiContainer.scrollTop = 0;
        localStorage.setItem('absensiScrollPosition', '0');
    }

    loadTable('fade-anim');
});

function animateTable(anim) {
    const tableBody = document.querySelector('.absensi-table tbody');
    if (tableBody) {
        tableBody.classList.remove('slide-left', 'slide-right', 'fade-anim');
        void tableBody.offsetWidth; // Trigger reflow
        tableBody.classList.add(anim);
    }
}

function loadTable(anim = 'fade-anim') {
    if (document.documentElement.classList.contains('dark')) {
        absensiTableWrapper.classList.add('dark-mode-fix');
    }
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form));
    params.set('ajax', 'yes');

    fetch(`?${params.toString()}`)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newTableInfoHeader = doc.querySelector('.table-info-header');
            const newTableContentWrapper = doc.querySelector('.absensi-table-content-wrapper');

            // Replace content
            if (newTableInfoHeader) {
                document.querySelector('.table-info-header').innerHTML = newTableInfoHeader.innerHTML;
            }
            if (newTableContentWrapper) {
                document.querySelector('.absensi-table-content-wrapper').innerHTML = newTableContentWrapper.innerHTML;
            }

            animateTable(anim);

            const mingguNow = document.getElementById('mingguInput').value;
            localStorage.setItem('absensiMingguAktif', mingguNow);

            updateTabIndicator();
        })
        .catch(error => console.error('Error loading table:', error));
}

function updateTabIndicator() {
    const active = document.querySelector('.tab.active');
    if (active && tabIndicator) {
        tabIndicator.style.width = `${active.offsetWidth}px`;
        tabIndicator.style.left = `${active.offsetLeft}px`;
        if (tabButtonsWrapper) {
            const scrollTarget = active.offsetLeft - (tabButtonsWrapper.offsetWidth / 2) + (active.offsetWidth / 2);
            // Only scroll if the active tab is not fully visible
            if (scrollTarget < tabButtonsWrapper.scrollLeft || scrollTarget > tabButtonsWrapper.scrollLeft + tabButtonsWrapper.offsetWidth - active.offsetWidth) {
                tabButtonsWrapper.scrollLeft = scrollTarget;
            }
        }
    } else if (!active && tabIndicator) {
        tabIndicator.style.width = '0px';
        tabIndicator.style.left = '0px';
    }
}

window.addEventListener('resize', updateTabIndicator);
const lastAddedKategoriFromSession = "{{ session('last_added_kategori', '') }}";
const lastAddedBulanFromSession = "{{ session('last_added_bulan', '') }}";
const sessionSuccessMessage = "{{ session('success') }}"; // Dapatkan pesan sukses dari session

window.addEventListener('load', () => {
    let initialKategori = localStorage.getItem('absensiTabAktif');
    let initialMinggu = localStorage.getItem('absensiMingguAktif');
    let initialBulan = document.querySelector('input[name="bulan"]').value;

    // --- Dynamic tab selection based on allowedTabs ---
    const availableCategories = JSON.parse('<?php echo json_encode(array_keys($allowedTabs)); ?>');

    if (lastAddedKategoriFromSession && lastAddedKategoriFromSession !== '') {
        initialKategori = lastAddedKategoriFromSession;
        initialMinggu = '1'; // Always reset minggu to 1 when navigating from add/edit
        if (lastAddedBulanFromSession && lastAddedBulanFromSession !== '') {
            initialBulan = lastAddedBulanFromSession;
        }
    } else if (!initialKategori || !availableCategories.includes(initialKategori)) {
        initialKategori = availableCategories[0] || "{{ $kategori }}";
    }

    document.getElementById('kategoriInput').value = initialKategori;
    document.getElementById('mingguInput').value = initialMinggu;
    bulanInput.value = initialBulan; // Set value for the hidden month input
    bulanDisplay.value = formatMonthDisplay(initialBulan); // Update display input
    mingguSebelumnya = parseInt(initialMinggu);

    const tabButtons = document.querySelectorAll('.tab');
    tabButtons.forEach(btn => btn.classList.remove('active'));
    const activeTabToRestore = [...tabButtons].find(btn => btn.dataset.kategori === initialKategori);
    if (activeTabToRestore) {
        activeTabToRestore.classList.add('active');
    }

    updateTabIndicator();
    loadTable('fade-anim');

    // Menggunakan fungsi showToast baru jika ada pesan sukses dari session
    if (sessionSuccessMessage) {
        showToast('success', ''); // Panggil showToast dengan tipe 'success' dan pesan kosong
    }

    const savedAbsensiScrollPosition = localStorage.getItem('absensiScrollPosition');
    if (savedAbsensiScrollPosition && absensiContainer) {
        setTimeout(() => {
            absensiContainer.scrollTop = parseInt(savedAbsensiScrollPosition);
        }, 100);
    }
    const savedTabScrollPosition = localStorage.getItem('tabButtonsScrollPosition');
    if (savedTabScrollPosition && tabButtonsWrapper) {
        setTimeout(() => {
            tabButtonsWrapper.scrollLeft = parseInt(savedTabScrollPosition);
        }, 150);
    }
});

window.addEventListener('beforeunload', () => {
    if (absensiContainer) {
        localStorage.setItem('absensiScrollPosition', absensiContainer.scrollTop);
    }
    if (tabButtonsWrapper) {
        localStorage.setItem('tabButtonsScrollPosition', tabButtonsWrapper.scrollLeft);
    }
});


const tabButtons = document.querySelectorAll('.tab');
tabButtons.forEach(button => {
    button.addEventListener('click', e => {
        e.preventDefault();
        const kategori = button.dataset.kategori;
        document.getElementById('kategoriInput').value = kategori;
        document.getElementById('mingguInput').value = 1; // Reset to week 1 when changing category
        mingguSebelumnya = 1;
        tabButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        updateTabIndicator();

        localStorage.setItem('absensiTabAktif', kategori);
        localStorage.setItem('absensiMingguAktif', '1');

        if (absensiContainer) {
            absensiContainer.scrollTop = 0;
            localStorage.setItem('absensiScrollPosition', '0');
        }

        loadTable('slide-left');
    });
});

function gantiMinggu(ke) {
    const anim = ke > mingguSebelumnya ? 'slide-left' : 'slide-right';
    mingguSebelumnya = ke;
    document.getElementById('mingguInput').value = ke;

    localStorage.setItem('absensiMingguAktif', ke);

    if (absensiContainer) {
        absensiContainer.scrollTop = 0;
        localStorage.setItem('absensiScrollPosition', '0');
    }

    loadTable(anim);
}

// --- Fungsi showToast baru untuk mengelola notifikasi ---
function showToast(type, message) {
    const statusToast = document.getElementById('statusToast');
    const toastIcon = document.getElementById('toastIcon');
    const toastMessage = document.getElementById('toastMessage');

    // Atur kelas ikon berdasarkan tipe
    if (type === 'success') {
        toastIcon.className = 'alert-icon fas fa-check-circle'; // Ikon centang
        statusToast.style.backgroundColor = 'var(--accent)'; // Warna hijau
    } else if (type === 'error') {
        toastIcon.className = 'alert-icon fas fa-times-circle'; // Ikon silang (jika ada error)
        statusToast.style.backgroundColor = '#dc3545'; // Contoh warna merah untuk error
    } else {
        // Default atau tipe lainnya
        toastIcon.className = 'alert-icon fas fa-info-circle';
        statusToast.style.backgroundColor = '#007bff';
    }

    // Atur pesan (disembunyikan oleh CSS seperti yang Anda inginkan)
    toastMessage.textContent = message;

    // Pastikan toast terlihat pada posisi awal sebelum animasi
    statusToast.style.display = 'flex'; // Gunakan flex untuk centering
    statusToast.classList.remove('hide-alert');
    statusToast.classList.add('show-alert');

    // Atur timing untuk menyembunyikan toast
    setTimeout(() => {
        statusToast.classList.remove('show-alert');
        statusToast.classList.add('hide-alert');

        // Hapus toast dari DOM setelah animasi selesai
        statusToast.addEventListener('transitionend', function handler() {
            statusToast.style.display = 'none';
            statusToast.classList.remove('hide-alert'); // Bersihkan kelas
            statusToast.removeEventListener('transitionend', handler); // Hapus listener
        }, { once: true }); // Pastikan listener hanya berjalan sekali

    }, 1000); // Toast terlihat selama 1 detik (1000ms)
}
</script>
@endpush

@endsection
