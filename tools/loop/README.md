# Loop — sampel referensi + pencocokan foto

Pengguna memotret material; **di HP** foto diubah jadi sidik jari visual (model ONNX 24 MB dibundel di APK)
lalu dicocokkan dengan sidik jari sampel referensi yang diunduh dari `GET /api/loop/sampel` (publik).
Hasilnya hanya REFERENSI, bukan hasil lab.

## Berkas
- `embed.py` — sidik jari satu foto (dipakai server & skrip paket; HARUS sama dengan pra-proses di aplikasi).
- `siapkan_paket.py` — Excel + folder foto -> paket (`sampel.json` + `foto/*.jpg`), dijalankan di komputer.
- Model: `storage/app/loop/loop_int8.onnx` (TIDAK ada di git; ada juga di aplikasi: `assets/loop/`).
- Versi model: `App\Models\LoopSampel::MODEL_VERSI`. Ganti model = ganti konstanta ini, hitung ulang semua
  sidik jari, dan rilis APK baru.

## Menyiapkan paket data (komputer)
    pip install onnxruntime pillow pillow-heif numpy openpyxl
    python tools/loop/siapkan_paket.py --excel data_komparator.xlsx --dataset datasheet \
        --model storage/app/loop/loop_int8.onnx --keluar storage/app/loop_paket
Folder foto: `dataset/{Code}/raw.(jpg|jpeg|png|heic)`. Foto `scale.*` diabaikan.

## Pertama kali di VPS
1. `git pull && php artisan migrate --force && php artisan optimize:clear && php artisan optimize`
2. Salin model dan paket (mis. dengan scp) ke `/var/www/lacakgc/storage/app/loop/loop_int8.onnx`
   dan `/var/www/lacakgc/storage/app/loop_paket/` (sampel.json + foto/).
3. `php artisan loop:impor storage/app/loop_paket` (sidik jari ikut paket; tidak butuh Python).
   Pastikan `php artisan storage:link` sudah ada dan folder `storage/app/public/loop` bisa dibaca web (755).
4. Python untuk sampel baru lewat Filament (diproses worker queue, jalan sebagai user yang sama dengan worker):
       python3 -m venv /var/www/lacakgc/storage/app/loop/venv
       /var/www/lacakgc/storage/app/loop/venv/bin/pip install onnxruntime pillow numpy
   Tambahkan ke `.env`: `LOOP_PYTHON=/var/www/lacakgc/storage/app/loop/venv/bin/python`, lalu
   `php artisan config:clear && php artisan queue:restart`.
   Foto Loop memakai disk `public` (bukan `local`) supaya worker bisa membacanya (lihat gotcha izin file).
   Bila `pip` gagal karena belum ada wheel onnxruntime untuk versi Python server, pakai Python lain
   (uv/pyenv) atau hitung sidik jari di komputer lalu `loop:impor` lagi.

## Menambah / mengubah sampel
- Filament menu "Loop (Sampel Referensi)": isi data + unggah foto -> status Memproses -> Siap (otomatis,
  lewat antrean). Mengubah data teks/kadar tidak menghitung ulang; mengganti foto menghitung ulang.
- Aplikasi memuat ulang data bila `versi` di server berubah (cache-first; offline memakai data tersimpan).
