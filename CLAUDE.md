# SIGC2 — Sistem Informasi Grade Control PT Antam

Dokumen ini dibaca otomatis oleh Claude Code di setiap sesi. Berisi arsitektur, konvensi, gotcha teknis, dan status pengerjaan project ini.

---

## 1. GAMBARAN UMUM

**Tujuan:** Sistem pelaporan Grade Control untuk tim lapangan tambang nikel PT Antam, area Kolaka/Pomalaa (UBPN Kolaka/Sultra).

**Dua sisi sistem:**
- **Backend admin panel** — Laravel + Filament, diakses tim office lewat browser
- **Aplikasi mobile** — Flutter, dipakai tim lapangan, terhubung ke backend yang sama lewat REST API

**Repo:** `github.com/Hamdanhams/sigc2` (branch `main`). Dua folder terpisah: `sigc2` (Laravel) dan `sigc2_mobile` (Flutter).

**Status deployment (per 27 Okt 2026):** Backend **live di VPS production** — domain `https://lacakgc.my.id`, path `/var/www/lacakgc`, Ubuntu 26.04 (`resolute`), PHP 8.5 (paket Ubuntu `8.5.4-0ubuntu1.5`, sudah memuat tambalan CVE-2026-109618/109619), PHP-FPM, Laravel 13.32, `APP_ENV=production`, `APP_DEBUG=false`. Halaman `/` dialihkan ke `/admin`. Flutter sudah di-release (APK, distribusi manual, belum di Play Store), versi terakhir **1.1.0+3**. Semua fitur sudah dites user di HP dan berjalan normal. **Nama final aplikasi belum diputuskan secara resmi** — UI login kini memakai branding "LACAK — Layanan Area Control And Kualitas" (selaras domain `lacakgc`), `pubspec` & `MaterialApp.title` masih "SIGC2"; jangan hardcode nama baru sebelum user memutuskan.

**Environment development:** Laragon (PHP 8.3.33, MySQL 8.4) di Windows, Laravel 13, Filament 3.3.x, Flutter 3.47.5. MySQL Laragon kadang mati sendiri (error `[2002] refused`) — nyalakan via Laragon atau `mysqld.exe --defaults-file=...\my.ini`. Heredoc di Bash tool sering gagal → pakai tool Write/Edit. Jangan jalankan `dart format` pada file lama (mis. `app_shell.dart`) — mengubah ratusan baris.

---

## 2. PRINSIP ARSITEKTUR UTAMA (WAJIB DIPATUHI)

**Semua logic bisnis harus hidup di Service class (`app/Services/`), bukan menempel di form Filament.** Form Filament hanya jalan kalau data diinput lewat admin panel — begitu Flutter mengirim data lewat API, logic yang menempel di Filament tidak akan pernah terpanggil. Logic seperti lookup Block Model, generate Running Number, parsing kode sampel FSBS harus dipanggil dari Form Filament, Model Observer, DAN API Controller sekaligus.

---

## 3. BACKEND — DATABASE & MODEL (STATUS SELESAI)

### Master Data
- **`fronts`** — `nama_front`, `inisial` (3 huruf), `lokasi`, `status`
- **`personils`** — field worker, bisa login (`username`/`password`, Sanctum). `ttd` = file tanda tangan.
- **`user_pegawais`** — Pengawas, bisa login (`username`/`password` nullable, Sanctum). `ttd` = file tanda tangan. `jabatan` enum: `pengawas`, `work_unit_head`, `pengawas_senior`.
- **`block_models`** — lookup Ni/Fe untuk Produksi & FSBS. `inisial_front` TEKS BEBAS (bukan FK, sengaja — data Excel sering tidak match sempurna). Import via `BlockModelImport` (queue, chunk 500). **PENTING:** `ShouldQueue` harus dari `Illuminate\Contracts\Queue\ShouldQueue`, bukan dari Maatwebsite Excel. Import hanya MENAMBAH baris (tidak ada cek duplikat / opsi "ganti seluruh data" — user memutuskan TIDAK dibuat); import ulang file yang sama = data ganda. Elevasi minus di Block Model & kode sampel pakai format `M02` (bukan `-02`). Tabel ini sanggup ratusan ribu baris karena sudah ber-index (`inisial_front`,`titik_produksi`).

### Modul Produksi
- **`produksis`** (header) — `tanggal`, `shift`, `jam_mulai/selesai`, `front_id`, `fleet`, `pic_1_id`, `pic_2_id`, `user_pegawai_id`, `dokumentasi_produksi`/`dokumentasi_kendala` (JSON array URL Cloudinary), `keterangan`, `rencana_produksi_besok`, `status_approval` (enum: menunggu/disetujui/ditolak — **akan direvisi jadi 2 lapis**), `catatan_penolakan`
- **`detail_produksis`** — `titik_produksi`, `elevasi_atas`, `running_number` (manual, user isi bebas, tombol "Tambah Detail" auto-increment angka di akhir string), `tujuan_dumping`, `ni_bm`/`fe_bm` (hasil lookup), `ritase`, `gridding`

### Modul FSBS (flat, 1 kode_sampel = 1 koordinat)
- **`fsbs`** — `kode_sampel`, `front`/`titik_produksi`/`elevasi`/`huruf_running` (hasil parsing), `koordinat_x`/`koordinat_y` (decimal 16,8 — WAJIB presisi ini untuk UTM), `personil_id`, `user_pegawai_id`, `status_approval`/`catatan_penolakan`/`ditolak_oleh_jabatan`, `foto_material` (URL Cloudinary), `keterangan`, `increment`, `gridding`, `ni_bm`, `fe_bm`, `client_uuid` (unik, nullable — ID draft di HP; kiriman ulang dengan UUID sama TIDAK membuat data ganda, server membalas 201 dengan data yang sudah ada)
- **`fsbs.ni`** (decimal 6,3, nullable; 30 Okt 2026) = hasil analisa Ni (%), diisi **siapa saja yang login** dengan mengetuk pin plot di peta (Flutter) atau lewat admin Filament. Hanya SATU kolom Ni (kolom kadar lab lengkap tetap dihapus). Endpoint: `PATCH /fsbs/ni` (body `client_uuid` ATAU `id`, `ni` wajib hadir, null = hapus), `GET /fsbs/peta?front=XXX&hari=90` (plot satu Front termasuk HP lain, maks 5000), `POST /fsbs` menerima `ni`. Ada di tabel admin, form, dan Export Excel (kolom "Ni (analisa)"). Warna pin: ≥2,00 hijau · 1,80–1,99 biru · 1,50–1,79 kuning · 1,20–1,49 oranye · <1,20 merah · belum ada = abu-abu (`KelasNi` di `lib/models/fsbs_plot_marker.dart`); titik tengah pin gelap = plot belum terkirim.
- **`created_at` = WAKTU PLOT di HP** (app mengirim `dibuat_pada` ISO UTC; server mengabaikan jika > 10 menit di masa depan atau > 90 hari lampau → pakai waktu sinkron). `created_at` disimpan **UTC**; tanggal kerja dihitung **WITA (UTC+8)**. Semua filter tanggal FSBS memakai scope `Fsbs::tanggalWita($dari, $sampai)` (rentang waktu, bukan `whereDate`) dan kolom Tanggal di Filament ditampilkan `dateTime(..., 'Asia/Makassar')`. Zona waktu app (`config/app.php`) SENGAJA tetap UTC agar data lama tidak bergeser.
- Kolom kadar lab (`ni`,`co`,`fe`,`sio2`,`cao`,`mgo`,`cr2o3`,`al2o3`) dan `status` **SUDAH DIHAPUS** — data lab diproses di luar aplikasi. Fitur "Import Hasil Analisa" **sudah dihapus total**.
- Export **Excel (.xlsx, `App\Exports\FsbsExport`)** — menggantikan Export CSV (CSV sudah DIHAPUS), GPX/KML, Export PDF "Pengantar Sample" (F-270.503.001), Download Dokumentasi (ZIP foto per Front+tanggal WITA)

### Modul Sebaran FSBS (terpisah dari modul FSBS di atas)
- **`sebaran_fsbs`** — `inisial_front` (teks bebas), `titik_produksi`, `elevasi`, `koordinat_x`/`koordinat_y`, `ni`, `fe`, `si_mg_ratio` (1 kolom rasio gabungan). Import Excel+queue (header yang dikenali: `inisial_front`/`front`, `titik_produksi`, `elevasi`, `x`/`koordinat_x`, `y`/`koordinat_y`, `ni`, `fe`, `s_m`/`sm`/`si_mg_ratio`). **Elevasi minus di tabel ini ditulis `-02`/`-2` (BUKAN `M02`)** karena tombol pembersihan memakai `CAST(elevasi AS DECIMAL)`. Tombol "Sisakan 2 Elevasi Terendah" (bulk cleanup — simpan SEMUA baris di 2 LEVEL elevasi terendah, bukan 2 baris literal).
- ~~`peta_sebaran_layers`~~ — **DIHAPUS (30 Okt 2026)**, digantikan Peta Block Model (di bawah). Endpoint `/peta-sebaran` tinggal stub kosong untuk APK lama; hapus bersama `PetaSebaranController` bila semua pengguna sudah pakai APK baru. File ZIP/GeoPDF lama (`mbtiles/{id}.zip`, `geopdf_sebaran/`) TIDAK ikut terhapus — dulu `mbtiles/{id}.zip` bertabrakan id dengan peta biasa; bersihkan manual di VPS.

### Modul Peta Block Model (BARU, 30 Okt 2026 — dikoding, belum di-deploy/dites device)
- **`peta_block_models`** — `front_id`, `rl` (decimal 8,2), `nama_bagian` (opsional: Utara/Tengah/Selatan; RL TIDAK unik per Front — satu Front+RL boleh banyak peta), `file_geopdf` (`geopdf_blockmodel/`), `file_mbtiles` (`mbtiles_blockmodel/{id}.zip`), `status`, `pesan_error`, center/zoom. Model: `PetaBlockModel` (label otomatis "RB4 RL 12 (Utara)", `elevasi_data` = RL + 2; ganti GeoPDF → hasil lama dihapus & status kembali pending; event `deleting` hapus file).
- **Titik sebaran FSBS di peta** = `sebaran_fsbs` dengan `inisial_front` = `fronts.inisial` DAN elevasi (numerik, cocok `-02`/`014`/`14`) = RL + 2. Semua titik Front+elevasi dikirim ke semua bagian peta (keputusan user: opsi "tanpa filter koordinat"). Endpoint `GET /peta-block-model`, `/peta-block-model/{id}/download`, `/peta-block-model/{id}/sebaran`.
- Konversi: `ConvertPetaBlockModelJob` → `MapConversionService::convert($peta, 'mbtiles_blockmodel')` (parameter folder; folder TIAP jenis peta harus sendiri). File sementara (.tif + folder tile) kini dibersihkan juga saat konversi GAGAL. Filament "Peta Block Model": tombol per baris **Konversi**, header **Konversi Semua** (antre semua pending/gagal), bulk "Konversi yang dipilih". Tetap butuh worker manual: `php artisan queue:work --stop-when-empty` (timeout job 1800 dtk, diproses satu per satu).

### Modul Peta
- **`peta_layers`** — `nama_peta`, `front_id`, `file_geopdf`, `file_mbtiles` (isinya path .zip), `status`, `center_lat/lon`, `min_zoom`/`max_zoom`. Model punya event `deleting` — otomatis hapus file GeoPDF + .zip + `.aux.xml` sidecar saat record dihapus.

### Modul Permintaan
- **`permintaans`** — `personil_id`, `front_id`, `jenis_permintaan` (teks bebas), `gambar` (nullable), `keterangan`, `status` (enum: menunggu/diproses/selesai), `hasil_pdf`. Filament punya `getNavigationBadge()`.

### Modul Approval Produksi (2 lapis: Pengawas → Work Unit Head, SELESAI — lihat bagian 7A)
- Pengawas (User Pegawai) approve/reject laporan Produksi yang `user_pegawai_id`-nya dia; lapis 2 oleh semua Work Unit Head
- Endpoint: `GET /approval/produksi`, `PATCH /approval/produksi/{id}/approve`, `PATCH /approval/produksi/{id}/reject`
- Revisi laporan ditolak: UPDATE laporan yang sama (bukan bikin baru) via `PUT /produksi/{id}`, reset `status_approval` jadi `menunggu`

---

## 4. BACKEND — SERVICE CLASSES (`app/Services/`)

1. **`BlockModelLookupService`** — lookup Ni/Fe BM, normalisasi elevasi (strip leading zero kecuali format `M` untuk minus)
2. **`RunningNumberService`** — generate running number, generic (huruf apapun)
3. **`KodeSampelParserService`** — parsing kode sampel FSBS (3 char Front, char terakhir huruf running, 3 char sebelum itu elevasi, sisanya titik produksi)
4. **`CoordinateConversionService`** — UTM zona 51 Selatan ↔ Lat/Lon
5. **`MapConversionService`** — GeoPDF → tile pyramid → ZIP. GENERIC (terima `PetaLayer` ATAU `PetaSebaranLayer`, type hint `mixed`). `GDAL_PAM_ENABLED=NO` di-set untuk cegah file `.aux.xml`. Env var GDAL di-hardcode via `Process::setEnv()` (hindari konflik Surpac — ini cuma masalah di komputer dev Windows, TIDAK relevan di server Linux production).
6. **`ProduksiPdfService`** — PDF F-270.501.R0, terima collection (multi-page)
7. **`FsbsPdfService`** — PDF F-270.503.001, 1 halaman landscape 2 kolom per tanggal

---

## 5. BACKEND — API ENDPOINTS (`routes/api.php`)

**PENTING:** SEMUA endpoint protected HARUS dalam SATU group `auth:sanctum`. Pernah ada bug fatal — beberapa route ketambahan DI LUAR group (bisa diakses tanpa token). Selalu cek ulang struktur file ini sebelum menambah route baru.

```
PUBLIC: POST /login, POST /login-pengawas, GET /login-slides (throttle 60/menit; slide layar login)
PROTECTED (auth:sanctum):
  /logout, /me, /upload-foto, PUT /akun/password (limiter akun-password)
  /produksi (POST/GET/{id}GET/{id}PUT), /my-produksi
  /fsbs (POST/GET)  — POST menerima client_uuid + dibuat_pada
  /my-fsbs, /approval/fsbs (grup Front+tanggal WITA), /rekonsiliasi
  /safety-meeting (POST/GET/PUT ganti foto saja; TIDAK ada DELETE), /cuti, /cuti/hitung, /approval/cuti
  /peta, /peta/{id}/download
  /master/fronts, /master/personils, /master/user-pegawais
  /peta-block-model, /peta-block-model/{id}/download, /peta-block-model/{id}/sebaran
  /sebaran-fsbs/{front}, /peta-sebaran (stub kosong), /peta-sebaran/{id}/download (404) — hanya APK lama
  /approval/produksi (GET), /approval/produksi/{id}/approve|reject (PATCH)
  /permintaan (POST/GET/{id}GET/{id}/download-hasil)
```

---

## 6. FLUTTER APP (`sigc2_mobile`) — STRUKTUR SAAT INI

### Role & Login
- `AuthService` — `enum UserRole { personil, pengawas, pengawasSenior, workUnitHead }`. Method `login()` (Personil) dan `loginPengawas()` (User Pegawai).
- **`LoginScreen` (redesign 13 Okt 2026, ala Livin')**: carousel foto penuh layar dari backend (`GET /login-slides`, dikelola admin di menu Filament "Slide Login", foto di Cloudinary), 2 tombol **Login Personil** / **Login Pengawas / WUH** → form login muncul sebagai bottom sheet. Slide + foto di-cache di HP (`LoginSlideService`, refresh maks tiap 30 menit); tanpa slide → latar gradasi biru bawaan.
- **`AuthGateScreen`** — layar perantara di `main.dart`, cek token tersimpan sebelum arahkan ke Login/Home (auto-login, tidak perlu login ulang tiap buka app).

### Offline Support (SELESAI, prioritas utama project ini)
- **Master Data, Peta list, Sebaran FSBS data** — semua pakai pola cache: fetch online → simpan `shared_preferences` → kalau gagal/offline, fallback ke cache terakhir (timeout 8 detik per request). **Daftar Front & daftar Peta kini CACHE-FIRST**: tampil seketika dari cache, lalu diperbarui di latar belakang (peta baru dari admin muncul pada pembukaan berikutnya). **Semua request HTTP punya batas waktu** lewat `TimeoutClient` (`lib/services/timeout_client.dart`, dipasang di `main.dart` dengan `http.runWithClient`: 20 dtk biasa, 90 dtk unggah) — mencegah "loading terus" di sinyal lemah
- **Background peta offline — SOLVED.** JANGAN PAKAI tile server HTTP lokal (`shelf`/`TileServer`, sudah tidak dipakai lagi, boleh dihapus). MapLibre Native punya `NetworkStatus` check yang BLOK semua request HTTP (termasuk ke `127.0.0.1`!) saat device offline — tidak bisa diakali dari Dart. **Solusi final:** pakai skema `file://` langsung di tiles array (`'file://$tilesDir/{z}/{x}/{y}.png'`), bypass network stack sepenuhnya karena ini baca file lokal, bukan request jaringan.
- **JANGAN PERNAH coba modifikasi `MainActivity.kt` untuk urusan MapLibre/network.** Sudah dicoba 2x (reflection call ke `MapLibre.setConnected()`), keduanya menyebabkan native crash/freeze susah didiagnosis. `MainActivity.kt` HARUS tetap versi kosong:
  ```kotlin
  package com.example.sigc2_mobile
  import io.flutter.embedding.android.FlutterActivity
  class MainActivity : FlutterActivity()
  ```
- GPS tracking (titik biru, kompas, deteksi radius) selalu jalan offline — itu murni sensor HP, tidak butuh internet sama sekali, tidak terpengaruh masalah di atas.

### Struktur Menu Peta (BARU — 3 tingkat)
`PetaFrontListScreen` (pilih Front, dari `/master/fronts`) → `PetaScreen(front)` (daftar peta UNTUK Front itu saja, filter `frontId`, bisa lebih dari 1 peta per Front) → `MapViewerScreen(peta)` (buka peta). **Menu "Peta Block Model" (menggantikan Persebaran FSBS, 30 Okt 2026):** `BlockModelFrontListScreen` → `BlockModelPetaScreen(front)` (peta per RL/bagian, download/hapus lokal) → `BlockModelMapScreen(peta)` (raster `file://` + titik sebaran sebagai layer circle di ATAS raster, ketuk = titik terdekat dalam 28 px, GPS radius 5 m). Service: `PetaBlockModelService` (cache peta & titik per peta; folder lokal `blockmodel_maps`; menghapus sisa unduhan `sebaran_maps` lama). Persebaran FSBS (`persebaran_*`, `peta_sebaran_*`, `sebaran_fsbs_service`) sudah dihapus dari app.

### Marker Peta
**Layer lingkaran bawaan plugin (`addCircle`/`addCircles`) berada DI BAWAH layer raster peta yang ditambah belakangan → marker tersembunyi di belakang peta** (itu sebabnya titik Persebaran FSBS tidak terlihat tapi masih bisa diklik; user menerima keadaan itu). **Marker plot FSBS di `MapViewerScreen` kini berupa PIN lokasi (ujung lancip di bawah)** digambar lewat `addImage` (PNG dibuat dengan `dart:ui`, kuning emas = draft, hijau = tersinkron) + `addGeoJsonSource('fsbs-markers')` + `addSymbolLayer` yang ditambah SETELAH layer raster, `iconAnchor: bottom`; refresh lewat `setGeoJsonSource`. Aturan lama di bawah berlaku untuk marker yang memakai annotation bawaan (Persebaran): style peta custom (`{"version":8,"sources":{},"layers":[]}`) tidak punya sprite — `addSymbol` gagal diam-diam (tidak error, cuma tidak render). Untuk data banyak (ribuan titik), WAJIB pakai `addCircles()` (plural, batch) bukan loop `await addCircle()` satu-satu — yang terakhir bisa bikin UI macet puluhan detik tanpa indikator loading.

### Modul Produksi (Flutter)
`ProduksiFormScreen` (buat/edit draft lokal, PIC1 auto-fill+disabled saat baru), `RiwayatProduksiScreen` (laporan tersync, status badge), `RevisiProduksiScreen` (fetch dari server, PUT update). Dokumentasi foto (`MultiPhotoPicker` widget reusable) — ambil kamera/galeri multi, upload ke Cloudinary saat sync.

### Modul FSBS, Sebaran FSBS, Approval, Permintaan
Lihat rekap lengkap di riwayat chat — semua modul ini SELESAI dan berfungsi. File-file kunci: `fsbs_form_screen.dart`, `persebaran_map_screen.dart`, `approval_screen.dart`, `permintaan_screen.dart`.

### Notifikasi + Suara (Polling, BUKAN push/FCM)
`StatusTrackerService` (shared_preferences, baseline-first-run-no-notify), `NotificationSoundService`, `NotificationPollingService`. Dipanggil di `initState` Home Personil & Home Pengawas.

---

## 7. PEKERJAAN BESAR

**STATUS TERBARU (27 Okt 2026):** Semua item A–E di bawah SUDAH LIVE dan SUDAH DIITES user di HP (berjalan normal). **Perkecualian — dua fitur sengaja DITAHAN di app (keputusan user, 17 Okt 2026): menu Cuti (Personil), Approval Cuti, dan Safety Meeting tampil nonaktif berlabel "Segera"; tap hanya memunculkan "… dalam tahap pengembangan".** Saklar: `cutiDitahan` dan `safetyDitahan` (const bool, keduanya `true`) di atas `lib/app/menu_config.dart` — ubah ke `false` + build ulang untuk mengaktifkan lagi; baris Cuti/Safety di kartu "Perlu Tindakan" & header stat juga disembunyikan oleh saklar itu. Backend, endpoint, dan menu Filament Cuti/Safety Meeting TETAP aktif; polling notifikasi cuti di latar belakang masih jalan. **Ekspor PDF Cuti: user masih memikirkan formatnya (belum ada).** Keputusan user yang DIBATALKAN/tidak perlu: sembunyikan Pengawas Senior dari dropdown "Pilih Pengawas"; opsi "ganti seluruh data lama" di import Block Model; import Sebaran FSBS hanya sheet pertama.

**Riwayat status (9 Okt 2026):**
- **A (Approval 2 Lapis, Fase 1–5): SELESAI & live di VPS.**
- **B (Rekonsiliasi): SELESAI & live di VPS.** Total ORE kadar = rata-rata TERTIMBANG BCM (sudah dikonfirmasi user). Form admin input per MINGGU (header sekali + blok HGSO/LGSO/Waste), logika di `RekonsiliasiService`.
- **Safety Meeting (versi 1): SELESAI di Flutter** — foto + panel keterangan (logo Antam di `assets/images/antam_logo.png`), simpan ke galeri, menu HANYA di beranda Pengawas/WUH. Tanpa server.
- **D (Arsip Safety Meeting): SUDAH DIKODING** (backend commit `d834dd2`, Flutter di repo mobile) — perlu `migrate` di VPS + build APK. **C (Cuti): backend + Filament SELESAI (logika di `CutiService`; endpoint `/cuti`, `/cuti/hitung`, `/approval/cuti`), Flutter SELESAI (menu Cuti Personil, Approval Cuti Pengawas Senior/WUH, polling notifikasi), ekspor PDF menunggu format user.** **E (Redesign menu + Ganti Password): SELESAI DIKODING** — Flutter: `lib/screens/app_shell.dart` (3 tab), `lib/app/home_controller.dart`, `lib/app/menu_config.dart` (TAMBAH MENU = tambah 1 entri di `daftarMenu`), `lib/theme/app_theme.dart`; beranda lama `home_screen`/`pengawas_home_screen` sudah dihapus. Backend: `PUT /akun/password` (`AkunService`, tanpa batas minimum, limiter `akun-password`).** Lihat bagian C, D, E di bawah.
- Repo Flutter: `github.com/Hamdanhams/sigc2_mobile` (branch `main`).

Catatan implementasi A yang tidak jelas dari kode:
- Status laporan: `menunggu` → `menunggu_wuh` → `disetujui`; `ditolak` = menunggu revisi (+ `catatan_penolakan`, `ditolak_oleh_jabatan`). Revisi mereset ke `menunggu`.
- FSBS: grup = Front + tanggal `created_at` dalam WITA (UTC+8) + status. Data FSBS lama & kiriman tanpa `user_pegawai_id` (APK lama) otomatis `disetujui`. Revisi FSBS = update di tempat (`PUT /my-fsbs/revisi`), `created_at` tidak berubah.
- Work Unit Head memakai `PengawasHomeScreen` yang sama (label & status pending mengikuti `AuthService.getPendingStatus()`).
- `sigc2_mobile` sekarang repo git sendiri (remote `github.com/Hamdanhams/sigc2_mobile`).

Requirement awal & urutan pengerjaan yang disepakati:

### A. Approval 2 Lapis (Produksi & FSBS)

**A1. Role baru — Work Unit Head**
- Tambah kolom `jabatan` di `user_pegawais`: `enum(['pengawas','work_unit_head'])`, default `pengawas`
- BUKAN dipilih per laporan. Begitu laporan lolos Pengawas, otomatis masuk "kotak masuk bersama" — SEMUA akun berjabatan `work_unit_head` bisa lihat & approve laporan itu (tidak ditujukan ke 1 orang spesifik)
- Login Work Unit Head pakai mekanisme SAMA dengan Pengawas (`loginPengawas`, sama-sama dari tabel `user_pegawais`) — yang beda cuma tampilan/routing di Flutter setelah login, berdasarkan `jabatan`

**A2. Approval Produksi jadi 2 lapis**
- Alur: `menunggu` → (Pengawas approve) → `menunggu_wuh` (nama status sementara, bisa disesuaikan) → (Work Unit Head approve) → `disetujui`
- Ditolak di LAPIS MANAPUN → balik ke `menunggu`, Personil revisi, HARUS ulang dari lapis 1 lagi (tidak ada "lanjut dari lapis yang gagal saja")
- Catatan penolakan + keterangan DARI LAPIS MANA (Pengawas/Work Unit Head) WAJIB disimpan dan ditampilkan ke Personil — kemungkinan butuh kolom tambahan semacam `ditolak_oleh_jabatan` atau sejenisnya

**A3. Approval FSBS (FITUR BARU 100% — FSBS belum pernah punya approval sama sekali)**
- Form plot FSBS: tambah field BARU **"Pilih Pengawas"** (`user_pegawai_id`) — field ini SEBELUMNYA TIDAK ADA di form FSBS
- Tabel `fsbs` perlu kolom approval baru: `status_approval`, `catatan_penolakan`, kemungkinan `ditolak_oleh_jabatan` (konsisten dengan Produksi)
- Alur approval SAMA PERSIS dengan Produksi (2 lapis, Pengawas → Work Unit Head)
- **PENTING — tampilan approval FSBS beda dari Produksi:** DIKELOMPOKKAN per Front + Tanggal (pakai `created_at`), BUKAN per-plot individual. Contoh: "2 Oktober 2026 - RB4 (10 plot)" sebagai 1 baris di daftar approval; tap baru buka daftar kode sampel di dalamnya.
- Approve/Tolak berlaku untuk **1 GRUP SEKALIGUS** (semua plot di Front+tanggal itu), bukan per kode sampel satu-satu
- Perlu endpoint baru yang GROUP BY front+tanggal untuk list approval (beda dari pola list biasa yang sudah ada)
- Perlu halaman revisi FSBS dari nol (belum pernah ada) — mirip `RevisiProduksiScreen` tapi untuk banyak plot sekaligus dalam 1 grup
- Perlu halaman riwayat status FSBS untuk Personil (belum pernah ada sama sekali — FSBS screen Personil saat ini cuma tampilkan draft lokal + sync, tidak ada tampilan status approval)

**Rencana fase (disepakati, belum dieksekusi):**
1. Fase 1: Backend dasar — `jabatan`, status 2 lapis Produksi, routing login
2. Fase 2: Flutter — Home Work Unit Head, approval Produksi 2 lapis
3. Fase 3: Backend — approval FSBS (grouping, endpoint baru)
4. Fase 4: Flutter — halaman approval FSBS (list grup, detail, approve/tolak)
5. Fase 5: Form plot FSBS tambah "Pilih Pengawas" + halaman revisi FSBS + riwayat status FSBS

### B. Menu Rekonsiliasi (FITUR BARU 100%)

**B1. Backend — tabel & input admin**
Tabel baru (nama belum ditentukan, sarankan `rekonsiliasis`), kolom:
```
minggu_ke (teks manual, format "W-40")
tanggal_mulai, tanggal_akhir (date)
parameter (enum: HGSO, LGSO, Waste — "Total ORE" TIDAK diinput manual, dihitung otomatis)
bcm_bm, ni_bm, fe_bm, sio2_bm, mgo_bm (number)
bcm_real, ni_real, fe_real, sio2_real, mgo_real (number)
```
Form input biasa di Filament, tidak ada kerumitan khusus di sisi admin.

**B2. Flutter — menu baru "Rekonsiliasi"**
3 dropdown:
- **Jenis**: BCM, Ni, Fe, SiO2, MgO
- **Parameter**: HGSO, LGSO, **Total ORE**, Waste
- **Periode**: diisi dari SELURUH nilai unik kolom `minggu_ke` yang pernah diinput

Begitu 3 dropdown terisi → tampilkan **grafik histogram** (2 batang: nilai BM vs nilai Real) untuk kombinasi Jenis+Parameter+Periode itu.

**B3. Perhitungan "Total ORE" — FINAL (SELESAI)**
- **BCM**: dijumlahkan (HGSO + LGSO).
- **Ni/Fe/SiO2/MgO** (kadar): rata-rata TERTIMBANG BCM (BCM dari sisi yang sama: BM dengan BCM BM, Real dengan BCM Real). Bukan rata-rata biasa.

### C. Pengajuan Cuti (BACKEND + FILAMENT + FLUTTER SELESAI & DITES; menu di app DITAHAN "Segera" sejak 17 Okt 2026; EKSPOR PDF belum ada format dari user)

**Keputusan user (sudah final):**
- Role baru: `pengawas_senior` ditambah ke enum `jabatan` di `user_pegawais` (kini: pengawas, work_unit_head). Hanya 1 orang. **Pengawas Senior TIDAK ditampilkan di dropdown "Pilih Pengawas"** form Produksi & FSBS (filter `jabatan = 'pengawas'` tetap, jangan sampai ikut).
- Saldo cuti = kolom baru di tabel `personils`, diisi MANUAL oleh admin di Filament (+ tabel riwayat perubahan saldo: siapa, kapan, alasan).
- Alur approval: **Pengawas Senior → WUH**. Dicek di server saat pengajuan dibuat: ada User Pegawai AKTIF berjabatan `pengawas_senior` → status awal `menunggu`; kalau tidak ada → langsung `menunggu_wuh`. Status: `menunggu` → `menunggu_wuh` → `disetujui` | `ditolak` (+ `catatan_penolakan`, `ditolak_oleh_jabatan`).
- Ditolak = final, alasan wajib, TIDAK ada edit; Personil bikin pengajuan BARU. Tidak ada jenis cuti (semua memotong saldo). **Saldo dipotong saat DISETUJUI WUH.**
- Hitung hari = hari kerja: Sabtu, Minggu, dan Hari Libur Nasional TIDAK dihitung. Hari libur disimpan di tabel yang diinput admin di Filament (bukan API luar; admin isi per tahun, termasuk cuti bersama).
- **Saldo boleh minus sampai −6.** Pengajuan ditolak sistem kalau `saldo − hari_menunggu − hari_diajukan < −6` (jatuh tepat di −6 masih boleh). Pengajuan yang masih menunggu ikut dihitung; cek ulang saat approval akhir WUH.
- Pengajuan harus ONLINE (tidak ada antrean offline untuk cuti).
- Admin panel (Filament): daftar pengajuan, ubah saldo + riwayat, daftar hari libur, dan **ekspor PDF untuk pengajuan yang sudah disetujui WUH** — **FORMAT PDF MENUNGGU DARI USER** (pola PDF sudah ada: `ProduksiPdfService`/`FsbsPdfService`).

**Aturan turunan (usulan Claude, disetujui user kecuali yang dikoreksi di atas):** jumlah hari 0 ditolak; tanggal tidak boleh beririsan dengan pengajuan sendiri yang menunggu/disetujui; tanggal lampau tidak boleh diajukan; kalau Pengawas Senior dihapus/nonaktif saat ada pengajuan `menunggu`, WUH boleh memprosesnya; admin menghapus pengajuan yang sudah disetujui → saldo otomatis dikembalikan + dicatat di riwayat; Personil tidak bisa membatalkan (batal lewat admin).

**Flutter:** Personil: menu "Cuti" (saldo, formulir, riwayat status, badge ditolak, notifikasi polling). Pengawas Senior & WUH: layar approval cuti di beranda yang sama (`PengawasHomeScreen`). Pengawas biasa TIDAK melihat cuti. Logika di Service class (`app/Services/`), bukan di form Filament.

**Urutan:** (1) backend + Filament, (2) Flutter Personil lalu approval, (3) ekspor PDF setelah format user siap.

### D. Arsip Safety Meeting (SELESAI & DITES; menu di app DITAHAN "Segera" sejak 17 Okt 2026, backend/Filament tetap aktif)

Saat ini Safety Meeting hanya simpan ke galeri HP (versi 1, selesai). Atasan ingin dokumentasi bisa DILIHAT semua Pengawas & WUH, jadi foto dikirim ke server.

**Keputusan user (final):**
- Semua Pengawas & WUH bisa melihat SEMUA dokumentasi. Personil tidak punya akses.
- Pembuat: hanya bisa **mengganti foto**. TIDAK ada edit keterangan dan TIDAK ada hapus di app. Edit keterangan (lokasi, anggota, pembahasan) dan hapus data → HANYA di panel admin Filament. Jangan buat endpoint `DELETE` di API.
- Keterangan yang diedit admin TIDAK mengubah teks di dalam gambar (panel menyatu di foto = catatan asli saat kejadian); layar arsip menampilkan teks terbaru dari database di samping foto. Saat pembuat mengganti foto, panel foto baru dibuat dari keterangan di database saat itu.
- Antrean offline: foto + data disimpan di HP dengan status "belum terkirim", terkirim otomatis/lewat tombol sinkronisasi yang sudah ada saat ada sinyal (pola sama dengan FSBS/Produksi).
- Saat foto diganti ATAU data dihapus admin (satuan/massal, via model event `deleted`), foto di Cloudinary otomatis dihapus (`CloudinaryCleanupService`, gagal hapus hanya di-log; penghapusan berantai dari DB karena User Pegawai dihapus TIDAK memicu ini). Cloudinary hanya **JPEG** (kualitas ± 85), bukan PNG. Salinan galeri HP juga disamakan jadi JPEG.
- Filament: menu Safety Meeting (daftar, filter tanggal & lokasi, lihat foto, edit keterangan, hapus).

**Rancangan:** tabel `safety_meetings` (user_pegawai_id pembuat, waktu, lokasi, anggota [id+nama], pembahasan, foto URL); endpoint `POST/GET (daftar+detail)/PUT (ganti foto saja)` di dalam group `auth:sanctum`, dijaga hanya User Pegawai (pembuat saja untuk PUT). Flutter: menu Safety Meeting dibagi "Buat Baru" dan "Arsip"; tombol "Ganti Foto" hanya untuk pembuat.

### E. Redesign Tampilan Menu Aplikasi (SELESAI & LIVE; shell 3 tab + Ganti Password)

**Latar:** menu makin banyak (Personil: 8, Pengawas/WUH: 7, dan akan bertambah Cuti, Approval Cuti, Arsip Safety Meeting). Grid 2 kolom polos di beranda sudah tidak skalabel. User minta tampilan lebih profesional, "seperti aplikasi-aplikasi ternama". Dikerjakan SETELAH/BERSAMA fitur baru supaya menu final langsung masuk desain baru (disarankan: kerjakan SETELAH Cuti & Arsip Safety Meeting).

**Arah rancangan (usulan Claude, detail final menunggu keputusan user):**
- **Menu dikelompokkan per kategori** dengan judul seksi, bukan satu grid datar. Usulan Personil: *Laporan Lapangan* (Produksi, Riwayat, Plot FSBS) · *Peta & Data* (Peta, Persebaran FSBS, Rekonsiliasi) · *Layanan* (Permintaan, Cuti). Usulan Pengawas/Senior/WUH: *Perlu Tindakan* (Approval Laporan, Approval FSBS, Approval Cuti — dengan angka) · *Peta & Data* · *Dokumentasi* (Safety Meeting: Buat Baru, Arsip).
- **Navigasi bawah (bottom navigation)**: Beranda · Menu · Akun (opsional: Aktivitas/Notifikasi). Tombol Keluar dan info akun pindah ke tab Akun (bukan di header).
- **Beranda lebih ringkas**: header + kartu "Perlu Tindakan" (laporan ditolak / menunggu approval) + akses cepat untuk 3–4 menu paling sering; daftar lengkap ada di tab Menu (grid ikon per kategori + kolom cari).
- **Sinkronisasi** jadi status bar/banner ("N data belum sinkron" + tombol Sinkronkan), bukan kartu menu.
- **Design system konsisten**: satu set warna/ikon/ukuran kartu/spasi/tipografi (tema terpusat di `ThemeData`), ikon seragam dalam tile berwarna lembut, badge angka seragam, skeleton/loading yang halus, transisi halus, state kosong yang ramah.
- **Refactor**: beranda Personil (`home_screen.dart`) dan Pengawas/WUH (`pengawas_home_screen.dart`) saat ini duplikat. Jadikan SATU shell dengan konfigurasi menu per peran (data-driven), supaya menambah menu cukup menambah 1 entri.
- **Aturan yang harus tetap**: hak akses menu per peran tidak berubah (Cuti: Personil + Pengawas Senior + WUH; Safety Meeting: hanya User Pegawai; Pengawas biasa tidak melihat Cuti). Offline support tidak boleh rusak (menu tetap jalan tanpa sinyal; angka badge dipertahankan saat offline).

**Usulan Claude yang sudah diajukan ke user (10 Okt 2026):** bottom navigation 3 tab (Beranda | Menu | Akun); Beranda = header + kartu "Perlu Tindakan" (laporan ditolak, belum sinkron, approval menunggu) + Akses Cepat; tab Menu = semua fitur dikelompokkan + kolom cari; tab Akun = nama, jabatan, versi app, **Ganti Password**, Keluar.

**Permintaan user: tab Akun harus punya fitur GANTI PASSWORD** (untuk Personil dan User Pegawai). Rancangan: endpoint baru di dalam group `auth:sanctum` (mis. `PUT /akun/password`, field `password_lama`, `password_baru`, `konfirmasi`), berlaku untuk model `Personil` MAUPUN `UserPegawai` (keduanya tidak meng-cast password sebagai hashed → pakai `Hash::check` dan `Hash::make`/bcrypt manual seperti form Filament); wajib verifikasi password lama; **TIDAK ada batas panjang/kompleksitas minimum** (keputusan user, 10 Okt 2026: terserah pengguna) — hanya wajib diisi (tidak boleh kosong) dan konfirmasi harus sama; password baru tidak boleh sama dengan yang lama; setelah berhasil, token LAIN milik akun itu dihapus tapi token sesi saat ini dipertahankan. Tidak ada fitur "lupa password" — reset lewat admin Filament (kolom password di form Personil/User Pegawai). Logika di Service class (bukan di controller/Filament).

**Keputusan yang masih terbuka (tanya user sebelum mengerjakan):** pakai bottom navigation atau tetap satu beranda? perlu halaman notifikasi/aktivitas? perlu mode gelap? preferensi gaya (lebih "korporat" biru-emas seperti sekarang, atau lebih berwarna)? Logo/nama aplikasi final belum diputuskan (lihat bagian 8) — jangan hardcode nama baru.

---

## 8. PEKERJAAN LAIN YANG MASIH TERTUNDA (prioritas lebih rendah dari bagian 7)

- **Redesign layar login: SELESAI & LIVE** (lihat bagian 6 "Role & Login"). Catatan: tidak ada gambar default di dalam APK — fallback-nya latar gradasi biru. Foto slide portrait ± 1080×1920, dikelola di Filament "Slide Login".
- **Nama aplikasi final** — belum diputuskan resmi. Brainstorming sempat dilakukan (kandidat: Kadar, Sigap, Gencar, dll); UI login sudah memakai "LACAK" (aset `assets/images/lacak_mark|wordmark|tagline.png`), tapi user belum menyatakan itu nama final.
- **Logo & Splash Screen animasi** — belum dikerjakan (logo mark LACAK sudah ada sebagai aset di login).
- **Persiapan Play Store**:
  - `applicationId` masih `com.example.sigc2_mobile` — WAJIB diganti sebelum publish (Google tolak prefix `com.example`), ganti di `build.gradle.kts` + pindah struktur folder Kotlin + `AndroidManifest.xml`
  - Signing key production belum di-setup (`flutter build apk/appbundle --release` masih pakai debug key kemungkinan besar)
  - Format wajib `.aab` (App Bundle), bukan `.apk`, untuk submit ke Play Store
  - Akun Google Play Developer LAMA (2020, nama "Putra Merpati Jaya") **sudah dihapus permanen** oleh Google (15 Okt 2024, gagal verifikasi tepat waktu) — ada opsi coba pulihkan (ajukan banding/selesaikan verifikasi) atau daftar baru ($25). Belum diputuskan user mau pilih yang mana.
  - **Akun demo untuk reviewer Google** — direncanakan: kolom `is_demo_account` di `personils`, kolom `is_demo_map` di `peta_layers`+`peta_sebaran_layers`. Akun demo cuma boleh lihat peta yang ditandai demo (data krusial), tapi Front/laporan/FSBS boleh tampil apa adanya (tidak terlalu sensitif). **Migration & filter controller BELUM DIKERJAKAN** — sempat direncanakan strukturnya tapi terhenti karena user bilang "nanti saja" setelah tahu backend sudah live di VPS (perlu proses git pull + migrate di server, bukan cuma edit lokal).
- **Testing:** user menyatakan (Okt 2026) semua fitur yang sudah dibuat dites dan berjalan normal. Yang BELUM terkonfirmasi eksplisit: skenario revisi laporan Produksi yang ditolak end-to-end, dan GPS radius 5 meter Persebaran FSBS di Front sungguhan.
- **Ide kecil belum disepakati/dikerjakan:** memuat plot FSBS dari server ke peta (marker saat ini hanya dari draft lokal HP itu, tidak ikut HP lain/terhapus-di-server); menampilkan titik Persebaran FSBS di atas raster (seperti pin plot FSBS); progres/notifikasi hasil import di admin.

---

## 9. GOTCHA TEKNIS LAIN (ringkasan, lihat riwayat chat untuk detail lengkap jika perlu)

- `queue:work` TIDAK baca ulang kode otomatis — restart manual tiap ubah Job/Import/Service yang dipanggil queue
- **VPS TIDAK punya worker queue yang selalu hidup** (tidak ada Supervisor). Import Excel (Block Model, Sebaran FSBS) hanya mengantrekan job; setelah upload di admin, jalankan manual di VPS: `php artisan queue:work --stop-when-empty` (error tampil di layar; lihat `failed_jobs`, `php artisan queue:failed`, `queue:flush`). Menyiapkan Supervisor = pekerjaan opsional. Satu job `ReadChunk` gagal menghentikan chunk sesudahnya (chain).
- **Import Excel membaca SEMUA sheet** dalam file, dengan asumsi format sama. Sheet lain (mis. sheet bantu) → `elevasi cannot be null`. Salin sheet data ke file baru sebelum upload. Sel berisi rumus (mis. `=VLOOKUP(...[1]...)`) atau `#N/A` membuat kolom decimal gagal (`Incorrect decimal value`) → ubah jadi nilai (Paste Special → Values). Satu baris buruk menggagalkan 500 baris satu chunk. Chunk sebelumnya yang sukses SUDAH masuk — hapus dulu (mis. berdasarkan `created_at`) sebelum import ulang agar tidak ganda.
- **Performa admin (13 Okt 2026):** migration `add_performance_indexes` menambah index di `block_models`, `fsbs`, `produksis`, `permintaans`. Pencarian kolom besar memakai awalan (`LIKE 'x%'`), bukan `%x%`; hindari `whereDate()` pada kolom ber-index (pakai rentang). Tabel besar pakai `deferLoading()`. Setelah `git pull` di VPS: `migrate --force`, `optimize:clear`, `optimize` (rute & konfigurasi ter-cache — perubahan `routes/*.php` baru terbaca setelah `optimize:clear`), kadang `filament:cache-components`, `icons:cache`.
- **Deploy VPS rutin:** `cd /var/www/lacakgc && git pull && php artisan migrate --force && php artisan optimize:clear && php artisan optimize`; `queue:restart` hanya jika kode yang dipanggil queue berubah.
- **Keamanan PHP (Okt 2026):** CVE-2026-109618/109619 (RCE kritis, PHP 8.5.0–8.5.11 / 8.4.0–8.4.26 / 8.3.0–8.3.35) → di Ubuntu perbaikan datang lewat backport (versi paket `8.5.4-0ubuntu1.5`, nomor `php -v` tidak berubah). Cek tambalan: `zgrep CVE-2026-10961 /usr/share/doc/php8.5-common/changelog.Debian.gz`, lalu `sudo systemctl restart php8.5-fpm`. Server produksi sudah ditambal & di-restart (27 Okt 2026). PHP Laragon dev (8.3.33) belum 8.3.36.
- **JANGAN pakai `throttle:N,1` di rute `auth:sanctum`** (ditemukan 30 Okt 2026): bawaannya memanggil `getAuthIdentifier()` yang TIDAK ada di `Personil`/`UserPegawai` → HTTP 500. Pakai limiter bernama di `AppServiceProvider` (`akun-password`, `api-akun` = 120/mnt per akun). Rute `/upload-signature` sebelumnya memakai `throttle:120,1` sehingga selalu 500 dan HP diam-diam jatuh ke unggah lewat server; sudah diperbaiki ke `throttle:api-akun` (unggah langsung HP→Cloudinary baru benar-benar aktif setelah deploy ini). `/login-slides` (publik, tanpa user) aman.
- **Hasil Ni di Flutter (30 Okt 2026):** `MapViewerScreen` menggambar plot dari draft HP + server (`FsbsNiService.fetchPlotServer`, cache per Front), ketuk badan/ujung pin → `_NiSheet`. Plot HP ini: simpan ke SQLite (`fsbs_draft.ni`, `ni_perlu_kirim`; DB versi 5) lalu PATCH; gagal → ikut terkirim saat sinkron (`SyncService` memanggil `kirimTertunda()`). Plot HP lain: langsung ke server (butuh koneksi). Draft yang diedit di form mempertahankan `ni`.
- **Sinkron FSBS (Flutter):** foto gagal diunggah = draft dihitung gagal (tidak dikirim tanpa foto lalu ditandai sinkron); setelah 2 kegagalan berturut-turut sinkron berhenti dan sisanya dilaporkan di dialog "Hasil Sinkronisasi". `client_uuid` (= `localId` draft) mencegah data ganda.
- **JANGAN pakai Filament `FileUpload` berdisk `cloudinary` untuk data yang tersimpan sebagai URL** (28 Okt 2026). Aplikasi menyimpan URL penuh (`https://res.cloudinary.com/...`) di `fsbs.foto_material` & `produksis.dokumentasi_*`; komponen itu (1) memeriksa tiap foto ke Cloudinary saat form diisi (~2 dtk/foto → halaman edit Produksi 16–30 dtk), (2) tidak mengenali URL sehingga state kosong, lalu (3) MENIMPA kolom jadi kosong/NULL saat disimpan (foto FSBS & daftar dokumentasi produksi hilang dari database), dan (4) saat membuat data baru hanya menyimpan path (tidak terpakai Download Dokumentasi/PDF). **Pola yang benar (sudah dipakai):** kolom URL TIDAK ada di form; foto yang ada ditampilkan lewat `Placeholder` (`ProduksiResource::pratinjauFoto`); foto baru lewat `FileUpload` berdisk `local` (kolom `foto_upload` / `tambah_dokumentasi_*`), diunggah ke Cloudinary di `mutateFormDataBefore*` (trait `UnggahFotoFsbs`, `GabungDokumentasi`) lalu URL-nya DIGABUNG (Produksi) atau MENGGANTI + foto lama dihapus lewat `CloudinaryCleanupService` setelah simpan (FSBS). `visibleOn()` TIDAK cukup (komponen tersembunyi tetap di-hydrate). Pola sama: `LoginSlideResource` (`UnggahGambarSlide`).
- **Unggah foto langsung HP → Cloudinary (28 Okt 2026):** `POST /upload-signature` (auth:sanctum, throttle 120/mnt, `UploadController::signature`) memberi tanda tangan (parameter `folder` dari whitelist `fsbs`/`produksi`/`kendala`/`permintaan`/`safety-meeting`, default `fsbs` untuk APK lama — sebelum 30 Okt 2026 SEMUA foto aplikasi masuk `fsbs`; `/upload-foto` menerima `folder` yang sama; `ApiUtils::signParameters`; rahasia API tetap di server). Flutter `FotoUploadService` (`lib/services/foto_upload_service.dart`) mengunggah langsung ke `upload_url`, memakai ulang 1 tanda tangan 40 menit, ulang sekali jika ditolak, lalu cadangan lewat `POST /upload-foto` (cara lama, lewat VPS; mencatat lama unggah ke `laravel.log`). Dipakai untuk foto FSBS & dokumentasi Produksi. Alasan: jalur lama HP→VPS→Cloudinary sinkron lambat & menahan proses PHP-FPM. Sudah dibuktikan Cloudinary menerima tanda tangan (HTTP 200).
- **Sinkron di latar belakang (Flutter, 28 Okt 2026):** `SyncLatarBelakang.jalankan()` membungkus sinkron dengan layanan latar depan Android (paket `flutter_foreground_task`, jenis `dataSync`; izin & `<service>` ada di `AndroidManifest.xml`, notifikasi "Mengirim 5 dari 29") agar jalan saat layar mati; `SyncProgress` + widget `SyncProgressBar` menampilkan kemajuan di layar Plot FSBS, Laporan Produksi, dan Beranda. Dipakai di 3 titik sinkron (fsbs_screen, produksi_screen, `HomeController.sinkronSemua`). Foto Produksi yang gagal diunggah = laporan dihitung gagal (tidak dikirim dengan foto kurang). BELUM dites di device: perilaku saat layar mati & merek HP dengan penghemat baterai agresif; Android 15 membatasi dataSync 6 jam/24 jam.
- **Admin Produksi/FSBS (28 Okt 2026):** tabel Produksi punya kolom PIC 1 + Status (badge) + filter status, urut tanggal terbaru; tabel FSBS punya kolom Status + filter, urut `created_at` terbaru; label status ada di `ProduksiResource::STATUS_APPROVAL`. Fleet di Produksi: `1`,`2`,`3`,`MKR`,`BOULDER` (server menyimpan huruf besar; app & admin sinkron).
- **Penghapusan foto otomatis (28 Okt 2026):** model `Produksi` (dokumentasi produksi + kendala) dan `Fsbs` (`foto_material`) punya event `deleted` → `CloudinaryCleanupService::hapusBanyakDariUrl()` (bulk `Cloudinary::admin()->deleteAssets`, 100 id/panggilan; URL non-Cloudinary diabaikan; gagal hanya di-log). Berlaku untuk hapus satuan & massal di admin; TIDAK terpicu oleh penghapusan berantai database. Tidak bisa dibatalkan. Foto yatim dari penghapusan SEBELUM ini tidak ikut terhapus; foto yang dibuang lewat revisi laporan (PUT /produksi/{id}) juga belum dibersihkan.
- **Pembersihan foto yatim: `php artisan cloudinary:bersihkan-yatim`** (`--hapus` untuk benar-benar menghapus, `--umur=3`, `--folder=*`, `--paksa`). DEFAULT HANYA LAPORAN. **HANYA dijalankan di VPS** (akun Cloudinary lokal = produksi, DB lokal kosong → semua foto akan tampak yatim; perintah menolak jika 0 rujukan atau >50% yatim). Rujukan dicocokkan ke SEMUA tabel berfoto: `fsbs.foto_material`, `produksis.dokumentasi_*`, `safety_meetings.foto`, `permintaans.gambar`, `login_slides.gambar` (foto Safety Meeting & Permintaan juga di folder `fsbs` karena diunggah lewat `/upload-foto`). Foto lebih muda dari `--umur` hari dilewati (melindungi sinkron berjalan). Jika menambah tabel/kolom baru yang menyimpan URL Cloudinary, TAMBAHKAN ke `kumpulkanRujukan()` agar fotonya tidak ikut dihapus.
- **VPS:** 2 vCPU / 2 GB RAM / 40 GB disk, Jakarta (SouthJKT-a). Dugaan paket cukup; cek `free -h`/`swapon --show` (belum ada swap?) jika RAM menipis.
- **Tanggal & zona waktu:** database UTC, tampilan admin & filter FSBS WITA (lihat bagian 3 FSBS). Modul lain: `produksis.tanggal` diisi manual (tidak terpengaruh); Cuti & Saldo log sudah ditampilkan `Asia/Makassar`.
- Filament `FileUpload` disk `'local'` default ke `storage/app/private/`, bukan `storage/app/` (Laravel 11+)
- Method yang implement interface Maatwebsite Excel (`map($row)`, `styles($sheet)`) — JANGAN tambah type hint yang beda dari signature interface aslinya (`map($row)` tanpa type hint, `styles(): array` WAJIB ada return type). Pakai PHPDoc comment kalau mau bantu linter, bukan type hint asli.
- Emulator Android: hindari system image "16K Page Size" (bikin GPS crash `DeadSystemException`). Device fisik via USB debugging jauh lebih baik untuk testing network/GPS (lebih cepat dari build APK berulang) — `flutter run -d <device_id>`.
- Selalu pakai terminal Laragon untuk command `php`/`composer`/`gdal*`, PowerShell kadang bikin masalah dengan Laravel Prompts interaktif (navigasi panah ke-input sebagai teks) — solusinya ketik langsung teks pilihannya.
