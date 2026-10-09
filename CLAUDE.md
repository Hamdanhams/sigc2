# SIGC2 — Sistem Informasi Grade Control PT Antam

Dokumen ini dibaca otomatis oleh Claude Code di setiap sesi. Berisi arsitektur, konvensi, gotcha teknis, dan status pengerjaan project ini.

---

## 1. GAMBARAN UMUM

**Tujuan:** Sistem pelaporan Grade Control untuk tim lapangan tambang nikel PT Antam, area Kolaka/Pomalaa (UBPN Kolaka/Sultra).

**Dua sisi sistem:**
- **Backend admin panel** — Laravel + Filament, diakses tim office lewat browser
- **Aplikasi mobile** — Flutter, dipakai tim lapangan, terhubung ke backend yang sama lewat REST API

**Repo:** `github.com/Hamdanhams/sigc2` (branch `main`). Dua folder terpisah: `sigc2` (Laravel) dan `sigc2_mobile` (Flutter).

**Status deployment:** Backend sudah **live di VPS production** — domain `https://lacakgc.my.id`. Flutter sudah di-release (APK, distribusi manual, belum di Play Store). **Nama final aplikasi belum diputuskan** — masih memakai nama kerja "SIGC2"; brainstorming nama baru sempat dilakukan tapi belum final.

**Environment development:** Laragon (PHP 8.3, MySQL 8.4) di Windows, Laravel 13, Filament 3.x, Flutter 3.47.5.

---

## 2. PRINSIP ARSITEKTUR UTAMA (WAJIB DIPATUHI)

**Semua logic bisnis harus hidup di Service class (`app/Services/`), bukan menempel di form Filament.** Form Filament hanya jalan kalau data diinput lewat admin panel — begitu Flutter mengirim data lewat API, logic yang menempel di Filament tidak akan pernah terpanggil. Logic seperti lookup Block Model, generate Running Number, parsing kode sampel FSBS harus dipanggil dari Form Filament, Model Observer, DAN API Controller sekaligus.

---

## 3. BACKEND — DATABASE & MODEL (STATUS SELESAI)

### Master Data
- **`fronts`** — `nama_front`, `inisial` (3 huruf), `lokasi`, `status`
- **`personils`** — field worker, bisa login (`username`/`password`, Sanctum). `ttd` = file tanda tangan.
- **`user_pegawais`** — Pengawas, bisa login (`username`/`password` nullable, Sanctum). `ttd` = file tanda tangan. **Field `jabatan` BELUM ADA — akan ditambahkan di fase berikutnya (lihat bagian 7).**
- **`block_models`** — lookup Ni/Fe untuk Produksi & FSBS. `inisial_front` TEKS BEBAS (bukan FK, sengaja — data Excel sering tidak match sempurna). Import via `BlockModelImport` (queue, chunk 500). **PENTING:** `ShouldQueue` harus dari `Illuminate\Contracts\Queue\ShouldQueue`, bukan dari Maatwebsite Excel.

### Modul Produksi
- **`produksis`** (header) — `tanggal`, `shift`, `jam_mulai/selesai`, `front_id`, `fleet`, `pic_1_id`, `pic_2_id`, `user_pegawai_id`, `dokumentasi_produksi`/`dokumentasi_kendala` (JSON array URL Cloudinary), `keterangan`, `rencana_produksi_besok`, `status_approval` (enum: menunggu/disetujui/ditolak — **akan direvisi jadi 2 lapis**), `catatan_penolakan`
- **`detail_produksis`** — `titik_produksi`, `elevasi_atas`, `running_number` (manual, user isi bebas, tombol "Tambah Detail" auto-increment angka di akhir string), `tujuan_dumping`, `ni_bm`/`fe_bm` (hasil lookup), `ritase`, `gridding`

### Modul FSBS (flat, 1 kode_sampel = 1 koordinat)
- **`fsbs`** — `kode_sampel`, `front`/`titik_produksi`/`elevasi`/`huruf_running` (hasil parsing), `koordinat_x`/`koordinat_y` (decimal 16,8 — WAJIB presisi ini untuk UTM), `personil_id`, `foto_material` (URL Cloudinary), `keterangan`, `increment`, `gridding`, `ni_bm`, `fe_bm`
- Kolom kadar lab (`ni`,`co`,`fe`,`sio2`,`cao`,`mgo`,`cr2o3`,`al2o3`) dan `status` **SUDAH DIHAPUS** — data lab diproses di luar aplikasi. Fitur "Import Hasil Analisa" **sudah dihapus total**.
- Export CSV/GPX/KML, Export PDF "Pengantar Sample" (F-270.503.001), Download Dokumentasi (ZIP foto per Front+tanggal)

### Modul Sebaran FSBS (terpisah dari modul FSBS di atas)
- **`sebaran_fsbs`** — `inisial_front` (teks bebas), `titik_produksi`, `elevasi`, `koordinat_x`/`koordinat_y`, `ni`, `fe`, `si_mg_ratio` (1 kolom rasio gabungan). Import Excel+queue. Tombol "Sisakan 2 Elevasi Terendah" (bulk cleanup — simpan SEMUA baris di 2 LEVEL elevasi terendah, bukan 2 baris literal).
- **`peta_sebaran_layers`** — struktur identik `peta_layers`, tabel terpisah.

### Modul Peta
- **`peta_layers`** — `nama_peta`, `front_id`, `file_geopdf`, `file_mbtiles` (isinya path .zip), `status`, `center_lat/lon`, `min_zoom`/`max_zoom`. Model punya event `deleting` — otomatis hapus file GeoPDF + .zip + `.aux.xml` sidecar saat record dihapus.

### Modul Permintaan
- **`permintaans`** — `personil_id`, `front_id`, `jenis_permintaan` (teks bebas), `gambar` (nullable), `keterangan`, `status` (enum: menunggu/diproses/selesai), `hasil_pdf`. Filament punya `getNavigationBadge()`.

### Modul Approval Produksi (SAAT INI 1 lapis — akan direvisi jadi 2 lapis, lihat bagian 7)
- Pengawas (User Pegawai) approve/reject laporan Produksi yang `user_pegawai_id`-nya dia
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
PUBLIC: POST /login, POST /login-pengawas
PROTECTED (auth:sanctum):
  /logout, /me, /upload-foto
  /produksi (POST/GET/{id}GET/{id}PUT), /my-produksi
  /fsbs (POST/GET)
  /peta, /peta/{id}/download
  /master/fronts, /master/personils, /master/user-pegawais
  /sebaran-fsbs/{front}, /peta-sebaran, /peta-sebaran/{id}/download
  /approval/produksi (GET), /approval/produksi/{id}/approve|reject (PATCH)
  /permintaan (POST/GET/{id}GET/{id}/download-hasil)
```

---

## 6. FLUTTER APP (`sigc2_mobile`) — STRUKTUR SAAT INI

### Role & Login
- `AuthService` — `enum UserRole { personil, pengawas }`. Method `login()` (Personil) dan `loginPengawas()` (User Pegawai). `LoginScreen` ada toggle Personil/Pengawas.
- **`AuthGateScreen`** — layar perantara di `main.dart`, cek token tersimpan sebelum arahkan ke Login/Home (auto-login, tidak perlu login ulang tiap buka app).

### Offline Support (SELESAI, prioritas utama project ini)
- **Master Data, Peta list, Sebaran FSBS data** — semua pakai pola cache: fetch online → simpan `shared_preferences` → kalau gagal/offline, fallback ke cache terakhir (timeout 8 detik per request)
- **Background peta offline — SOLVED.** JANGAN PAKAI tile server HTTP lokal (`shelf`/`TileServer`, sudah tidak dipakai lagi, boleh dihapus). MapLibre Native punya `NetworkStatus` check yang BLOK semua request HTTP (termasuk ke `127.0.0.1`!) saat device offline — tidak bisa diakali dari Dart. **Solusi final:** pakai skema `file://` langsung di tiles array (`'file://$tilesDir/{z}/{x}/{y}.png'`), bypass network stack sepenuhnya karena ini baca file lokal, bukan request jaringan.
- **JANGAN PERNAH coba modifikasi `MainActivity.kt` untuk urusan MapLibre/network.** Sudah dicoba 2x (reflection call ke `MapLibre.setConnected()`), keduanya menyebabkan native crash/freeze susah didiagnosis. `MainActivity.kt` HARUS tetap versi kosong:
  ```kotlin
  package com.example.sigc2_mobile
  import io.flutter.embedding.android.FlutterActivity
  class MainActivity : FlutterActivity()
  ```
- GPS tracking (titik biru, kompas, deteksi radius) selalu jalan offline — itu murni sensor HP, tidak butuh internet sama sekali, tidak terpengaruh masalah di atas.

### Struktur Menu Peta (BARU — 3 tingkat)
`PetaFrontListScreen` (pilih Front, dari `/master/fronts`) → `PetaScreen(front)` (daftar peta UNTUK Front itu saja, filter `frontId`, bisa lebih dari 1 peta per Front) → `MapViewerScreen(peta)` (buka peta). Pola serupa sudah lama dipakai di Persebaran FSBS (`PersebaranFsbsScreen` → `PersebaranMapScreen`).

### Marker Peta
**SELALU pakai `addCircle`/`CircleOptions`, JANGAN `addSymbol`/`SymbolOptions`.** Style peta custom (`{"version":8,"sources":{},"layers":[]}`) tidak punya sprite — `addSymbol` gagal diam-diam (tidak error, cuma tidak render). Untuk data banyak (ribuan titik), WAJIB pakai `addCircles()` (plural, batch) bukan loop `await addCircle()` satu-satu — yang terakhir bisa bikin UI macet puluhan detik tanpa indikator loading.

### Modul Produksi (Flutter)
`ProduksiFormScreen` (buat/edit draft lokal, PIC1 auto-fill+disabled saat baru), `RiwayatProduksiScreen` (laporan tersync, status badge), `RevisiProduksiScreen` (fetch dari server, PUT update). Dokumentasi foto (`MultiPhotoPicker` widget reusable) — ambil kamera/galeri multi, upload ke Cloudinary saat sync.

### Modul FSBS, Sebaran FSBS, Approval, Permintaan
Lihat rekap lengkap di riwayat chat — semua modul ini SELESAI dan berfungsi. File-file kunci: `fsbs_form_screen.dart`, `persebaran_map_screen.dart`, `approval_screen.dart`, `permintaan_screen.dart`.

### Notifikasi + Suara (Polling, BUKAN push/FCM)
`StatusTrackerService` (shared_preferences, baseline-first-run-no-notify), `NotificationSoundService`, `NotificationPollingService`. Dipanggil di `initState` Home Personil & Home Pengawas.

---

## 7. PEKERJAAN BESAR

**STATUS (9 Okt 2026):**
- **A (Approval 2 Lapis, Fase 1–5): SELESAI & live di VPS.**
- **B (Rekonsiliasi): SELESAI & live di VPS.** Total ORE kadar = rata-rata TERTIMBANG BCM (sudah dikonfirmasi user). Form admin input per MINGGU (header sekali + blok HGSO/LGSO/Waste), logika di `RekonsiliasiService`.
- **Safety Meeting (versi 1): SELESAI di Flutter** — foto + panel keterangan (logo Antam di `assets/images/antam_logo.png`), simpan ke galeri, menu HANYA di beranda Pengawas/WUH. Tanpa server.
- **D (Arsip Safety Meeting): SUDAH DIKODING** (backend commit `d834dd2`, Flutter di repo mobile) — perlu `migrate` di VPS + build APK. **C (Cuti) dan E (Redesign menu): SUDAH DIRENCANAKAN, BELUM DIKERJAKAN** — tunggu perintah user. Lihat bagian C, D, E di bawah.
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

### C. Pengajuan Cuti (DIRENCANAKAN — BELUM DIKERJAKAN, tunggu perintah user)

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

### D. Arsip Safety Meeting (SELESAI DIKODING 9 Okt 2026 — belum dites di device; backend belum di-deploy ke VPS saat ini ditulis)

Saat ini Safety Meeting hanya simpan ke galeri HP (versi 1, selesai). Atasan ingin dokumentasi bisa DILIHAT semua Pengawas & WUH, jadi foto dikirim ke server.

**Keputusan user (final):**
- Semua Pengawas & WUH bisa melihat SEMUA dokumentasi. Personil tidak punya akses.
- Pembuat: hanya bisa **mengganti foto**. TIDAK ada edit keterangan dan TIDAK ada hapus di app. Edit keterangan (lokasi, anggota, pembahasan) dan hapus data → HANYA di panel admin Filament. Jangan buat endpoint `DELETE` di API.
- Keterangan yang diedit admin TIDAK mengubah teks di dalam gambar (panel menyatu di foto = catatan asli saat kejadian); layar arsip menampilkan teks terbaru dari database di samping foto. Saat pembuat mengganti foto, panel foto baru dibuat dari keterangan di database saat itu.
- Antrean offline: foto + data disimpan di HP dengan status "belum terkirim", terkirim otomatis/lewat tombol sinkronisasi yang sudah ada saat ada sinyal (pola sama dengan FSBS/Produksi).
- Saat foto diganti ATAU data dihapus admin (satuan/massal, via model event `deleted`), foto di Cloudinary otomatis dihapus (`CloudinaryCleanupService`, gagal hapus hanya di-log; penghapusan berantai dari DB karena User Pegawai dihapus TIDAK memicu ini). Cloudinary hanya **JPEG** (kualitas ± 85), bukan PNG. Salinan galeri HP juga disamakan jadi JPEG.
- Filament: menu Safety Meeting (daftar, filter tanggal & lokasi, lihat foto, edit keterangan, hapus).

**Rancangan:** tabel `safety_meetings` (user_pegawai_id pembuat, waktu, lokasi, anggota [id+nama], pembahasan, foto URL); endpoint `POST/GET (daftar+detail)/PUT (ganti foto saja)` di dalam group `auth:sanctum`, dijaga hanya User Pegawai (pembuat saja untuk PUT). Flutter: menu Safety Meeting dibagi "Buat Baru" dan "Arsip"; tombol "Ganti Foto" hanya untuk pembuat.

### E. Redesign Tampilan Menu Aplikasi (DIRENCANAKAN — BELUM DIKERJAKAN, tunggu perintah user)

**Latar:** menu makin banyak (Personil: 8, Pengawas/WUH: 7, dan akan bertambah Cuti, Approval Cuti, Arsip Safety Meeting). Grid 2 kolom polos di beranda sudah tidak skalabel. User minta tampilan lebih profesional, "seperti aplikasi-aplikasi ternama". Dikerjakan SETELAH/BERSAMA fitur baru supaya menu final langsung masuk desain baru (disarankan: kerjakan SETELAH Cuti & Arsip Safety Meeting).

**Arah rancangan (usulan Claude, detail final menunggu keputusan user):**
- **Menu dikelompokkan per kategori** dengan judul seksi, bukan satu grid datar. Usulan Personil: *Laporan Lapangan* (Produksi, Riwayat, Plot FSBS) · *Peta & Data* (Peta, Persebaran FSBS, Rekonsiliasi) · *Layanan* (Permintaan, Cuti). Usulan Pengawas/Senior/WUH: *Perlu Tindakan* (Approval Laporan, Approval FSBS, Approval Cuti — dengan angka) · *Peta & Data* · *Dokumentasi* (Safety Meeting: Buat Baru, Arsip).
- **Navigasi bawah (bottom navigation)**: Beranda · Menu · Akun (opsional: Aktivitas/Notifikasi). Tombol Keluar dan info akun pindah ke tab Akun (bukan di header).
- **Beranda lebih ringkas**: header + kartu "Perlu Tindakan" (laporan ditolak / menunggu approval) + akses cepat untuk 3–4 menu paling sering; daftar lengkap ada di tab Menu (grid ikon per kategori + kolom cari).
- **Sinkronisasi** jadi status bar/banner ("N data belum sinkron" + tombol Sinkronkan), bukan kartu menu.
- **Design system konsisten**: satu set warna/ikon/ukuran kartu/spasi/tipografi (tema terpusat di `ThemeData`), ikon seragam dalam tile berwarna lembut, badge angka seragam, skeleton/loading yang halus, transisi halus, state kosong yang ramah.
- **Refactor**: beranda Personil (`home_screen.dart`) dan Pengawas/WUH (`pengawas_home_screen.dart`) saat ini duplikat. Jadikan SATU shell dengan konfigurasi menu per peran (data-driven), supaya menambah menu cukup menambah 1 entri.
- **Aturan yang harus tetap**: hak akses menu per peran tidak berubah (Cuti: Personil + Pengawas Senior + WUH; Safety Meeting: hanya User Pegawai; Pengawas biasa tidak melihat Cuti). Offline support tidak boleh rusak (menu tetap jalan tanpa sinyal; angka badge dipertahankan saat offline).

**Keputusan yang masih terbuka (tanya user sebelum mengerjakan):** pakai bottom navigation atau tetap satu beranda? perlu halaman notifikasi/aktivitas? perlu mode gelap? preferensi gaya (lebih "korporat" biru-emas seperti sekarang, atau lebih berwarna)? Logo/nama aplikasi final belum diputuskan (lihat bagian 8) — jangan hardcode nama baru.

---

## 8. PEKERJAAN LAIN YANG MASIH TERTUNDA (prioritas lebih rendah dari bagian 7)

- **Nama aplikasi final** — belum diputuskan. Brainstorming sempat dilakukan (kandidat: Kadar, Sigap, Gencar, dll), user masih mencari yang "klik".
- **Logo & Splash Screen animasi** — belum dikerjakan sama sekali, menunggu nama final.
- **Persiapan Play Store**:
  - `applicationId` masih `com.example.sigc2_mobile` — WAJIB diganti sebelum publish (Google tolak prefix `com.example`), ganti di `build.gradle.kts` + pindah struktur folder Kotlin + `AndroidManifest.xml`
  - Signing key production belum di-setup (`flutter build apk/appbundle --release` masih pakai debug key kemungkinan besar)
  - Format wajib `.aab` (App Bundle), bukan `.apk`, untuk submit ke Play Store
  - Akun Google Play Developer LAMA (2020, nama "Putra Merpati Jaya") **sudah dihapus permanen** oleh Google (15 Okt 2024, gagal verifikasi tepat waktu) — ada opsi coba pulihkan (ajukan banding/selesaikan verifikasi) atau daftar baru ($25). Belum diputuskan user mau pilih yang mana.
  - **Akun demo untuk reviewer Google** — direncanakan: kolom `is_demo_account` di `personils`, kolom `is_demo_map` di `peta_layers`+`peta_sebaran_layers`. Akun demo cuma boleh lihat peta yang ditandai demo (data krusial), tapi Front/laporan/FSBS boleh tampil apa adanya (tidak terlalu sensitif). **Migration & filter controller BELUM DIKERJAKAN** — sempat direncanakan strukturnya tapi terhenti karena user bilang "nanti saja" setelah tahu backend sudah live di VPS (perlu proses git pull + migrate di server, bukan cuma edit lokal).
- **Testing yang belum pernah dilakukan user:**
  - Skenario revisi laporan Produksi yang ditolak (kode sudah ada, belum pernah dites end-to-end)
  - GPS tracking radius 5 meter di Persebaran FSBS di lokasi Front sungguhan (baru pernah dites pakai lokasi dummy)

---

## 9. GOTCHA TEKNIS LAIN (ringkasan, lihat riwayat chat untuk detail lengkap jika perlu)

- `queue:work` TIDAK baca ulang kode otomatis — restart manual tiap ubah Job/Import/Service yang dipanggil queue
- Filament `FileUpload` disk `'local'` default ke `storage/app/private/`, bukan `storage/app/` (Laravel 11+)
- Method yang implement interface Maatwebsite Excel (`map($row)`, `styles($sheet)`) — JANGAN tambah type hint yang beda dari signature interface aslinya (`map($row)` tanpa type hint, `styles(): array` WAJIB ada return type). Pakai PHPDoc comment kalau mau bantu linter, bukan type hint asli.
- Emulator Android: hindari system image "16K Page Size" (bikin GPS crash `DeadSystemException`). Device fisik via USB debugging jauh lebih baik untuk testing network/GPS (lebih cepat dari build APK berulang) — `flutter run -d <device_id>`.
- Selalu pakai terminal Laragon untuk command `php`/`composer`/`gdal*`, PowerShell kadang bikin masalah dengan Laravel Prompts interaktif (navigasi panah ke-input sebagai teks) — solusinya ketik langsung teks pilihannya.
