<!doctype html>
<html lang="id" class="no-js">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aktivasi Akun — PINTU UPTD Pendapatan Daerah Wilayah Kota Kupang</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#F7F6F2">
  <link rel="icon" href="{{ asset('assets/img/favicon-32.png') }}" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/base.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}">
  <script>document.documentElement.classList.replace("no-js", "js");</script>
</head>
<body id="top" data-page="aktivasi">
<main id="main" class="auth">
  <section class="auth__side">
    <a class="auth__brand" href="{{ url('/') }}">
      <img class="emblem auth__emblem" src="{{ asset('assets/img/lambang-ntt-192.webp') }}" alt="" width="38" height="40">
      <span><strong>PINTU</strong><span>Ruang Pegawai · UPTD Kota Kupang</span></span>
    </a>
    <div class="auth__copy">
      <p class="eyebrow">Ruang pegawai</p>
      <h1 class="h-display">Aktifkan akun.<br><span class="tone">Pilih kata sandi sendiri.</span></h1>
      <p class="body">Tautan aktivasi hanya bisa dipakai sekali dan berlaku terbatas. Setelah aktif, masuk lewat halaman Login Pegawai.</p>
    </div>
    <div class="auth__foot"><a class="link-arrow" href="{{ url('/') }}">Kembali ke portal publik</a><span>© 2026 UPTD Pendapatan Daerah Wilayah Kota Kupang</span></div>
  </section>

  <section class="auth__main">
    @if ($user)
      <form class="card card--xl auth__card" method="post" action="{{ request()->getRequestUri() }}" novalidate>
        @csrf
        <p class="smallcaps">Aktivasi akun</p>
        <h2>Buat kata sandi Anda.</h2>
        <p class="small muted auth__lead">{{ $user->name }} · NIP <span class="mono">{{ $user->nip }}</span></p>

        @if ($errors->any())
          <div class="auth__alert is-on" role="alert"><span>{{ $errors->first() }}</span></div>
        @endif

        <div class="field auth__fields">
          <label class="label" for="sandi">Kata sandi baru <small>min. 12 karakter, huruf dan angka</small></label>
          <input class="input" id="sandi" name="sandi" type="password" autocomplete="new-password" required @if ($errors->has('sandi')) aria-invalid="true" @endif>
        </div>
        <div class="field">
          <label class="label" for="sandi_ulang">Ulangi kata sandi</label>
          <input class="input" id="sandi_ulang" name="sandi_ulang" type="password" autocomplete="new-password" required>
        </div>

        <button class="btn btn--dark btn--block auth__submit" type="submit">Aktifkan akun</button>

        <p class="auth__security"><span>Jangan bagikan kata sandi, termasuk kepada rekan kerja.</span></p>
      </form>
    @else
      <div class="card card--xl auth__card">
        <p class="smallcaps">Aktivasi akun</p>
        <h2>Tautan tidak berlaku.</h2>
        <p class="small muted auth__message">Tautan aktivasi sudah dipakai, kedaluwarsa, atau tidak lengkap. Minta tautan baru kepada admin di Subbagian Tata Usaha.</p>
        <a class="btn btn--dark btn--block auth__submit" href="{{ url('/login') }}">Ke halaman Login Pegawai</a>
      </div>
    @endif
  </section>
</main>
<script src="{{ asset('assets/js/main.js') }}" defer></script>
</body>
</html>
