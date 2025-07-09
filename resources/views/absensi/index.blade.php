@extends('layouts.app')

@section('title', 'Riwayat Absensi')
<link rel="stylesheet" href="{{ asset('css/absensi.css') }}">
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
@endphp
<div class="absensi-container">
    <div class="absensi-header">
        <h3>Riwayat Absensi</h3>
        @if($bolehTambahAbsensi)
<a href="{{ route('absensi.create') }}" class="ppm-btn-link desktop-only-button">
<span class="button-text">Presensi</span> </a>
        @endif
    </div>
    <form method="GET" id="filterForm" class="filter-area">
        <label>Pilih Bulan:
            <input type="month" name="bulan" class="filter-input" value="{{ $bulan }}">
        </label>
        <div class="filter-name-and-button-row">
            <label>
                <input type="text" name="nama" class="filter-input" placeholder="Cari nama..." value="{{ $nama }}">
            </label>
            <button type="submit" class="filter-btn">Filter</button>
        </div>

        <input type="hidden" name="kategori" id="kategoriInput" value="{{ $kategori }}">
        <input type="hidden" name="minggu" id="mingguInput" value="{{ $mingguAktif }}">
    </form>
    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif

    <div class="tab-buttons-wrapper">
        <div class="tab-buttons">
            @php
                $tabs = [
                    'apel_ggs' => 'Apel GGS',
                    'apel_qa' => 'Apel QA',
                    'cepatan' => 'Cepatan',
                    'lambatan_ggs' => 'Lambatan GGS',
                    'lambatan_qa' => 'Lambatan QA',
                    'mt' => 'MT',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
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
                        @php
                            $hariListApelDisplay = ['Senin','Selasa','Rabu','Kamis','Jum\'at','Sabtu'];
                            $tanggalHariApelIndices = [1, 2, 3, 4, 5, 6];
                        @endphp
                        @foreach ($hariListApelDisplay as $i => $hari)
                            <th>{{ $hari }}<br><small>{{ (int)$tanggalInfo['tanggalHari'][$tanggalHariApelIndices[$i]] }}</small></th>
                        @endforeach
                    @else

                        <th>Minggu<br><small>{{ (int)$tanggalInfo['tanggalHari'][0] }}</small></th>

                        @php
                            $hariListSeninJumat = ['Senin','Selasa','Rabu','Kamis','Jum\'at'];

                            $tanggalHariSeninJumatIndices = [1, 2, 3, 4, 5];
                        @endphp

                        @foreach ($hariListSeninJumat as $i => $hari)
                            <th colspan="2">{{ $hari }}<br><small>{{ (int)$tanggalInfo['tanggalHari'][$tanggalHariSeninJumatIndices[$i]] }}</small></th>
                        @endforeach


                        <th>Sabtu<br><small>{{ (int)$tanggalInfo['tanggalHari'][6] }}</small></th>
                    @endif

                    <th>AKSI</th>
                </tr>

                @if (!in_array($kategori, ['apel_ggs', 'apel_qa']))
                    <tr>
                        <th class="sticky-col sticky-col-no-sub"></th>
                        <th class="sticky-col sticky-col-nama-sub"></th>
                        <th>M</th>
                        @foreach ($hariListSeninJumat as $hari)
                            <th>S</th>
                            <th>M</th>
                        @endforeach
                        <th>S</th>
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
                            <a href="{{ route('absensi.edit', [
                                'user' => $row->user->id,
                                'bulan' => $bulan,
                                'minggu' => $mingguAktif,
                                'kategori' => $kategori
                            ]) }}" class="btn-edit">
                                Edit
                            </a>
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

function animateTable(anim) {
    const tableBody = document.getElementById('absensi-body');
    tableBody.classList.remove('slide-left', 'slide-right', 'fade-anim');
    void tableBody.offsetWidth;
    tableBody.classList.add(anim);
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

            document.querySelector('.table-info-header').innerHTML = newTableInfoHeader.innerHTML;
            document.querySelector('.absensi-table-content-wrapper').innerHTML = newTableContentWrapper.innerHTML;

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

window.addEventListener('load', () => {
    let initialKategori = localStorage.getItem('absensiTabAktif');
    let initialMinggu = localStorage.getItem('absensiMingguAktif');
    let initialBulan = document.querySelector('input[name="bulan"]').value;
    if (lastAddedKategoriFromSession && lastAddedKategoriFromSession !== '') {
        initialKategori = lastAddedKategoriFromSession;
        initialMinggu = '1';
        if (lastAddedBulanFromSession && lastAddedBulanFromSession !== '') {
            initialBulan = lastAddedBulanFromSession;
        }
    }
    else if (!initialKategori) {
        initialKategori = "{{ $kategori }}";
    }
    document.getElementById('kategoriInput').value = initialKategori;
    document.getElementById('mingguInput').value = initialMinggu;
    document.querySelector('input[name="bulan"]').value = initialBulan;
    mingguSebelumnya = parseInt(initialMinggu);

    const tabButtons = document.querySelectorAll('.tab');
    tabButtons.forEach(btn => btn.classList.remove('active'));
    const activeTabToRestore = [...tabButtons].find(btn => btn.dataset.kategori === initialKategori);
    if (activeTabToRestore) {
        activeTabToRestore.classList.add('active');
    }

    updateTabIndicator();
    loadTable('fade-anim');

    const successAlert = document.querySelector('.success-message');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => successAlert.remove(), 500);
        }, 3000);
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
        document.getElementById('mingguInput').value = 1;
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

document.querySelector('input[name="bulan"]').addEventListener('change', () => {
    document.getElementById('mingguInput').value = 1;
    mingguSebelumnya = 1;
    localStorage.setItem('absensiMingguAktif', '1');

    if (absensiContainer) {
        absensiContainer.scrollTop = 0;
        localStorage.setItem('absensiScrollPosition', '0');
    }

    loadTable('fade-anim');
});
</script>
@endpush

@endsection
