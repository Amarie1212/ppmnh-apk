@extends('layouts.app')

@section('title', 'Data Santri')
<link rel="stylesheet" href="{{ asset('css/santri.css') }}">
@section('content')
<div class="santri-container">
    <div class="santri-header">
        <h3>Data Santri</h3>

        {{-- Get the role of the currently logged-in user --}}
        @php $loggedInUserRole = Auth::user()->role; @endphp

        {{-- Button to toggle Dewan Guru section is visible ONLY for masteradmin --}}
        @if($loggedInUserRole === 'masteradmin')
            {{-- Default icon is 🔽 because the section will be initially hidden for masteradmin --}}
            <button onclick="toggleDewanGuru()" class="btn-toggle-guru" id="toggleGuruButton">🔽</button>
        @endif
    </div>

    <div class="santri-table-wrapper fade-anim">
        <table class="santri-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentGender = null;
                @endphp
                @foreach($users as $u)
                    {{-- KODE UNTUK GARIS PEMISAH GENDER DI SINI --}}
                    @if ($currentGender === null)
                        {{-- First user, just set the initial gender --}}
                        @php $currentGender = $u->jenis_kelamin; @endphp
                    @elseif ($currentGender !== $u->jenis_kelamin)
                        {{-- Gender changed, insert separator row --}}
                        <tr class="gender-separator-row">
                            <td colspan="4" class="gender-separator">
                                <hr class="simple-separator-line">
                            </td>
                        </tr>
                        @php $currentGender = $u->jenis_kelamin; @endphp
                    @endif
                    {{-- AKHIR KODE GARIS PEMISAH GENDER --}}

                    <tr>
                        <td>{{ $u->full_name ?? $u->name }}</td>
                        <td>{{ ucfirst($u->role) }}</td>
                        <td>{{ $u->kelas ?? '-' }}</td>
                        <td>
                            {{-- Logic for 'Edit' button in Santri/Penerobos section --}}
                            @if($loggedInUserRole === 'masteradmin')
                                {{-- Masteradmin can edit anyone --}}
                                <a href="{{ route('profile.editUser', $u->id) }}" class="btn-edit">Edit</a>
                            @elseif(in_array($loggedInUserRole, ['penerobos', 'dewanguru']) && in_array($u->role, ['santri', 'penerobos']))
                                {{-- Penerobos AND Dewanguru can edit santri and other penerobos --}}
                                <a href="{{ route('profile.editUser', $u->id) }}" class="btn-edit">Edit</a>
                            @else
                                {{-- For other roles or uneditable targets, display nothing or a disabled state --}}
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Data Dewan Guru section --}}
    {{-- Visible for masteradmin and dewanguru --}}
    @if(in_array($loggedInUserRole, ['masteradmin', 'dewanguru']))
    <div class="santri-table-wrapper slide-left" id="guru-section" style="display: none; margin-top: 24px;">
        <h4>Data Dewan Guru</h4>
        <table class="santri-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dewanGuru as $g)
                    <tr>
                        <td>{{ $g->full_name ?? $g->name }}</td>
                        <td>{{ $g->kelas ?? '-' }}</td>
                        <td>
                            {{-- Logic for 'Edit' button in Dewan Guru section --}}
                            @if($loggedInUserRole === 'masteradmin' || $loggedInUserRole === 'dewanguru')
                                {{-- Masteradmin AND Dewanguru can edit dewan guru (dewanguru can edit other dewan guru) --}}
                                <a href="{{ route('profile.editUser', $g->id) }}" class="btn-edit">Edit</a>
                            @else
                                {{-- Penerobos and Santri cannot edit dewan guru --}}
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleDewanGuru() {
        const section = document.getElementById('guru-section');
        const button = document.getElementById('toggleGuruButton'); // Get the button element

        // Toggle display style
        if (section.style.display === 'none') {
            section.style.display = 'block';
            if (button) { // Update button text/icon if it exists
                button.innerHTML = '🔼'; // Change to up arrow
            }
        } else {
            section.style.display = 'none';
            if (button) { // Update button text/icon if it exists
                button.innerHTML = '🔽'; // Change to down arrow
            }
        }
    }
</script>
@endpush
