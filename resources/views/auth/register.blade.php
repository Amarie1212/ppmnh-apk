@extends('layouts.app')
@section('title', 'Daftar Akun')

@section('content')
<div class="auth-page-wrapper">
  <div class="auth-box">
    <h3>Daftar Akun</h3>

    @if($errors->any())
      <div class="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('doRegister') }}">
      @csrf
@php
  $fields = [
    ['id' => 'full_name', 'type' => 'text', 'placeholder' => 'nama lengkap'],
    ['id' => 'email', 'type' => 'email', 'placeholder' => 'email'],
    ['id' => 'password', 'type' => 'password', 'placeholder' => 'password'],
    ['id' => 'no_hp', 'type' => 'text', 'placeholder' => 'nomor telepon'],
    ['id' => 'no_hp_ortu', 'type' => 'text', 'placeholder' => 'nomor telepon ortu'],
    // Gender nanti ditaruh sebelum ini
    ['id' => 'tempat_lahir', 'type' => 'text', 'placeholder' => 'tempat lahir'],
    ['id' => 'tanggal_lahir', 'type' => 'text', 'placeholder' => 'tanggal lahir'],
    ['id' => 'asal_daerah', 'type' => 'text', 'placeholder' => 'asal daerah'],
    ['id' => 'asal_desa', 'type' => 'text', 'placeholder' => 'asal desa'],
    ['id' => 'asal_kelompok', 'type' => 'text', 'placeholder' => 'asal kelompok'],
  ];
@endphp

@foreach ($fields as $field)
  @if ($field['id'] === 'tempat_lahir')
    <div class="form-group">
  <label for="jenis_kelamin" class="form-label" style="color: var(--text-primary); margin-bottom: 8px;">Jenis Kelamin</label>
  <div class="gender-options">
    <label class="gender-option">
      <input type="radio" name="jenis_kelamin" value="laki-laki" {{ old('jenis_kelamin') == 'laki-laki' ? 'checked' : '' }} required>
      <span>Laki-laki</span>
    </label>
    <label class="gender-option">
      <input type="radio" name="jenis_kelamin" value="perempuan" {{ old('jenis_kelamin') == 'perempuan' ? 'checked' : '' }} required>
      <span>Perempuan</span>
    </label>
  </div>
</div>
  @endif

  <div class="form-group">
    <div class="input-wrapper">
      <input
        id="{{ $field['id'] }}"
        name="{{ $field['id'] }}"
        type="{{ $field['type'] }}"
        value="{{ old($field['id']) }}"
        placeholder="{{ $field['placeholder'] }}"
        {{ $field['id'] !== 'tanggal_lahir' ? 'required' : '' }}
        @if($field['id'] === 'tanggal_lahir')
          onfocus="this.type='date'"
        @endif
      >
    </div>
  </div>
@endforeach

      <button type="submit" class="btn-login">
        <i data-lucide="user-plus" style="margin-right: 6px; vertical-align: middle;"></i>
        Daftar
      </button>

      <div class="footer">
        Sudah punya akun? <a href="{{ route('login') }}">Login</a>
      </div>
    </form>
  </div>


  <div class="theme-toggle-wrapper">
    <div class="theme-toggle-card">
      <div class="theme-switcher">
        <button class="theme-btn" onclick="setTheme('light')" title="Tema Terang">
          <i data-lucide="sun" class="icon-theme" style="color: #facc15"></i>
        </button>
        <button class="theme-btn" onclick="setTheme('dark')" title="Tema Gelap">
          <i data-lucide="moon" class="icon-theme" style="color: #60a5fa"></i>
        </button>
        <button class="theme-btn" onclick="setTheme('system')" title="Tema Sistem">
          <i data-lucide="monitor" class="icon-theme" style="color: #10b981"></i>
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

<script>
function setTheme(mode) {
  const html = document.documentElement;
  html.classList.remove('light', 'dark');
  if (mode === 'system') {
    const systemPref = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    html.classList.add(systemPref);
    localStorage.setItem('theme', 'system');
  } else {
    html.classList.add(mode);
    localStorage.setItem('theme', mode);
  }
}
</script>
