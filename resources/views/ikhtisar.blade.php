<!doctype html>
<html lang="id" class="ikhtisar">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="referrer" content="no-referrer" />
    <meta name="color-scheme" content="light" />
    <meta name="description" content="Ikhtisar penerimaan, realisasi target, dan pemetaan PKB UPTD Pendapatan Daerah Wilayah Kota Kupang untuk pegawai." />
    <meta name="theme-color" content="#F7F6F2" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon-32.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}" />
    <title>Ikhtisar Penerimaan &amp; Pemetaan PKB — PINTU</title>
    {{-- Ikhtisar (D-099): selalu bertema terang agar terbaca di proyektor; angkanya dari GET /api/ikhtisar dengan token tautan --}}
    @viteReactRefresh
    @vite('resources/js/ikhtisar/main.tsx')
    <link rel="stylesheet" href="{{ route('tema.dashboard') }}" />
  </head>
  <body>
    <noscript>Halaman Ikhtisar PINTU memerlukan JavaScript.</noscript>
    <div id="root"></div>
  </body>
</html>
