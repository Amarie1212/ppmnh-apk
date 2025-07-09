@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="auth-page-wrapper">
  <div class="auth-box">
    <h3>Selamat datang kembali</h3>

    @if(session('error'))
      <div class="alert">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="form-group">
  <div class="input-wrapper">
    <input id="email" type="email" name="email" required placeholder="email" value="{{ old('email') }}">
<span class="underline"></span>

  </div>
    </div>

    <div class="form-group">
  <div class="input-wrapper">
    <input id="password" type="password" name="password" required placeholder="password">
<span class="underline"></span>

</div>
    </div>

    <div class="actions">
      <a href="#">Lupa Password?</a>
    </div>

    <button type="submit">
      <i data-lucide="log-in" style="margin-right: 6px; vertical-align: middle;"></i>
      Masuk
    </button>

    <div class="footer">
      Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
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
    function toggleTheme() {
  const html = document.documentElement;
  const current = html.classList.contains('dark') ? 'dark' : 'light';
  const next = current === 'dark' ? 'light' : 'dark';
  html.classList.remove('dark', 'light');
  html.classList.add(next);
  localStorage.setItem('theme', next);
}
</script>
