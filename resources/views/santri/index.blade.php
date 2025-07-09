@extends('layouts.app')

@section('title', 'Data Santri')
<link rel="stylesheet" href="{{ asset('css/santri.css') }}">
@section('content')
<div class="santri-container">
    <div class="santri-header">
        <h3>Data Santri</h3>

        @if(in_array($role, ['masteradmin']))
            <button onclick="toggleDewanGuru()" class="btn-toggle-guru">🔽</button>
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
                    {{-- Check if gender has changed to insert separator --}}
                    @if ($currentGender === null)
                        {{-- First user, just set the initial gender --}}
                        @php $currentGender = $u->jenis_kelamin; @endphp
                    @elseif ($currentGender !== $u->jenis_kelamin)
                        {{-- Gender changed, insert separator row --}}
                        <tr class="gender-separator-row">
                            <td colspan="4" class="gender-separator">
                                {{-- MODIFICATION START: Removed text span --}}
                                <hr class="simple-separator-line">
                                {{-- MODIFICATION END --}}
                            </td>
                        </tr>
                        @php $currentGender = $u->jenis_kelamin; @endphp
                    @endif
                    <tr>
                        <td>{{ $u->full_name ?? $u->name }}</td>
                        <td>{{ ucfirst($u->role) }}</td>
                        <td>{{ $u->kelas ?? '-' }}</td>
                        <td><a href="{{ route('profile.editUser', $u->id) }}" class="btn-edit">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(in_array($role, ['masteradmin', 'penerobos']))
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
                        <td><a href="{{ route('profile.editUser', $g->id) }}" class="btn-edit">Edit</a></td>
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
        if (section.style.display === 'none') {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    }
</script>
@endpush
