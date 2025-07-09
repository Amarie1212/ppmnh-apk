@extends('layouts.app')

@section('title', 'Edit Absensi: ' . ($user->full_name ?? $user->name))

<link rel="stylesheet" href="{{ asset('css/absensi.css') }}">
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
                case '-':
                case '': return '-';
                default: return $status;
            }
        }
    @endphp

    <div class="edit-absensi-container">
        <div class="edit-absensi-header-row">
            <h3 class="edit-absensi-header">Edit Absensi: {{ $user->full_name ?? $user->name }}</h3>
            <a href="{{ route('absensi.index', ['bulan' => $bulanParam, 'minggu' => $mingguAktif, 'kategori' => $kategori]) }}" class="edit-absensi-back-btn">← Kembali</a>
        </div>

        <div class="edit-absensi-form-wrapper">
            <div class="date-range-info">
                <p>Absensi Tanggal: {{ $currentWeekStart->translatedFormat('d M Y') }} - {{ $currentWeekEnd->translatedFormat('d M Y') }}</p>
            </div>

            <form action="{{ route('absensi.update', $user->id) }}" method="POST" id="editAbsensiForm">
                @csrf
                @method('PUT')

                <input type="hidden" name="bulan" value="{{ $bulanParam }}">
                <input type="hidden" name="minggu" value="{{ $mingguAktif }}">
                <input type="hidden" name="kategori" value="{{ $kategori }}">

                <table class="edit-absensi-table">
                    <thead>
                        <tr>
                            <th class="edit-absensi-table-label-col-header">Hari</th>
                            @if (in_array($kategori, ['apel_ggs', 'apel_qa']))
                                <th>Apel</th>
                            @else
                                <th>Ngaji Subuh</th>
                                <th>Ngaji Maghrib</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="absensiTableBody">
                    </tbody>
                    <tbody>
                        <tr>
                            @php
                                $colspanSimpan = in_array($kategori, ['apel_ggs', 'apel_qa']) ? 1 : 2;
                            @endphp
                            <td colspan="{{ $colspanSimpan }}" class="edit-absensi-buttons-footer">
                                <button type="submit" class="edit-absensi-btn-save">Simpan</button>
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
        console.log('DOM Content Loaded. Edit Absensi Script started.');

        function showAlert(type, message, duration, thenCallback = null) {
            const alertElement = document.createElement('div');
            alertElement.className = `edit-absensi-alert-${type}`;
            alertElement.textContent = message;

            const container = document.querySelector('.edit-absensi-container');
            if (container) {
                container.prepend(alertElement);
                alertElement.style.display = 'block';
                void alertElement.offsetWidth;
                alertElement.classList.add('show-alert');
            } else {
                console.error("Alert container '.edit-absensi-container' not found!");
                return;
            }

            setTimeout(() => {
                alertElement.classList.remove('show-alert');
                alertElement.classList.add('hide-alert');

                const removeAlert = () => {
                    alertElement.removeEventListener('transitionend', removeAlert);
                    alertElement.remove();
                    if (thenCallback) {
                        thenCallback();
                    }
                };
                alertElement.addEventListener('transitionend', removeAlert);
            }, duration);
        }

        const absensiTableBody = document.getElementById('absensiTableBody');
        const kategoriAbsensi = "{{ $kategori }}";
        const editDataJS = JSON.parse('{!! $editDataJson !!}');

        function formatStatusForDisplay(status) {
            if (status === null || status === undefined || status === '') return '-';
            switch (status.toLowerCase()) {
                case 'hadir': return 'P';
                case 'telat': return 'T';
                case 'izin': return 'I';
                case 'sakit': return 'S';
                case 'alpha': return 'A';
                case '-': return '-';
                default: return status;
            }
        }

        function generateActivityCellHtml(date, activityType, activityData) {
            const currentStatus = activityData?.status;

            const isDisplayOnly = !activityData || currentStatus === null || currentStatus === '' || currentStatus === '-';

            if (isDisplayOnly) {
                return `
                    <td>-
                        <input type="hidden" name="status[${date}][${activityType}]" value="">
                        <input type="hidden" name="id[${date}][${activityType}]" value="">
                    </td>
                `;
            }

            const formattedStatus = formatStatusForDisplay(currentStatus);

            return `
                <td>
                    <select name="status[${date}][${activityType}]" class="edit-absensi-input">
                        <option value="">Pilih Status</option>
                        <option value="P" ${formattedStatus === 'P' ? 'selected' : ''}>P</option>
                        <option value="T" ${formattedStatus === 'T' ? 'selected' : ''}>T</option>
                        <option value="I" ${formattedStatus === 'I' ? 'selected' : ''}>I</option>
                        <option value="S" ${formattedStatus === 'S' ? 'selected' : ''}>S</option>
                        <option value="A" ${formattedStatus === 'A' ? 'selected' : ''}>A</option>
                        <option value="-" ${formattedStatus === '-' ? 'selected' : ''}>-</option>
                    </select>
                    <input type="hidden" name="id[${date}][${activityType}]" value="${activityData.id || ''}">
                </td>
            `;
        }

        function renderTableRows() {
            absensiTableBody.innerHTML = '';

            for (const date in editDataJS) {
                const data = editDataJS[date];
                const row = document.createElement('tr');
                let activityCellsHtml = '';

                if (['apel_ggs', 'apel_qa'].includes(kategoriAbsensi)) {
                    activityCellsHtml += generateActivityCellHtml(date, 'apel', data.apel);
                } else {
                    activityCellsHtml += generateActivityCellHtml(date, 'ngaji_subuh', data.ngaji_subuh);
                    activityCellsHtml += generateActivityCellHtml(date, 'ngaji_maghrib', data.ngaji_maghrib);
                }

                row.innerHTML = `
                    <td class="edit-absensi-table-label-col">${data.hari.charAt(0).toUpperCase() + data.hari.slice(1)}, ${data.tanggal_display}</td>
                    ${activityCellsHtml}
                `;
                absensiTableBody.appendChild(row);
            }
        }

        const editAbsensiForm = document.getElementById('editAbsensiForm');

        if (editAbsensiForm) {
            editAbsensiForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                console.log('Form submitted for update.');

                const formData = new FormData(this);
                formData.append('_method', 'PUT');

                console.log('--- Sending UPDATE Request Data ---');
                for (let pair of formData.entries()) {
                    console.log(pair[0]+ ': '+ pair[1]);
                }
                console.log('-----------------------------------');

                const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        let errorMessage = 'Terjadi kesalahan server.';
                        try {
                            const errorData = JSON.parse(errorText);
                            errorMessage = errorData.message || errorMessage;
                        } catch (jsonError) {
                            console.error('Failed to parse error response as JSON (Update):', jsonError);
                            console.error('Raw error response (Update):', errorText);
                            errorMessage = 'Terjadi kesalahan jaringan atau server: Respons tidak valid. Detail: ' + (errorText.length > 100 ? errorText.substring(0, 100) + '...' : errorText);
                        }
                        throw new Error(errorMessage);
                    }

                    const data = await response.json();
                    console.log('Update success data:', data);

                    if (data.success) {
                        showAlert('success', data.message, 1000, () => {
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        showAlert('error', data.message || 'Gagal menyimpan absensi.', 3000);
                    }

                } catch (error) {
                    console.error('Update fetch error:', error);
                    showAlert('error', 'Terjadi kesalahan saat menyimpan: ' + error.message, 5000);
                }
            });
        } else {
            console.error('Error: Form with ID "editAbsensiForm" not found.');
        }

        renderTableRows();

        @if(session('success'))
            showAlert('success', "{{ session('success') }}", 1000);
        @endif
        @if(session('error'))
            showAlert('error', "{{ session('error') }}", 3000);
        @endif
    });
</script>
@endpush
