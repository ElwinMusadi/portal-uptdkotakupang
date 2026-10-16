<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="description" content="Clock-in petugas UPTD Pendapatan Daerah Wilayah Kota Kupang di kegiatan lapangan." />
    <meta name="theme-color" content="#F7F6F2" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#0A0A0C" media="(prefers-color-scheme: dark)" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-title" content="Absensi PINTU" />
    <link rel="manifest" href="{{ asset('absen.webmanifest') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon-32.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}" />
    <title>Absensi PINTU — UPTD Pendapatan Daerah Wilayah Kota Kupang</title>
    <script>
      // Tema dashboard berlaku juga di aplikasi absensi; bawaannya Terang
      try {
        var t = localStorage.getItem("pintu.tema") || "light"
        var dark = t === "dark" || (t === "system" && matchMedia("(prefers-color-scheme: dark)").matches)
        if (dark) document.documentElement.classList.add("dark")
      } catch (e) {}
    </script>
    @viteReactRefresh
    @vite('resources/js/absen/main.tsx')
    <link rel="stylesheet" href="{{ route('tema.dashboard') }}" />
  </head>
  <body>
    <noscript>Aplikasi Absensi PINTU memerlukan JavaScript.</noscript>
    <div id="root"></div>
  </body>
</html>
