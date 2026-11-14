<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="color-scheme" content="light">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/geolens-favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/geolens-icon-192.png">
    <link rel="apple-touch-icon" href="/images/geolens-apple-touch-icon.png">
    <title>GeoLens — Pembanding Material</title>
    <style>
        :root {
            --primary: #0B3F94;   /* biru logo GeoLens */
            --accent: #F26B00;    /* oranye logo GeoLens */
            --bg: #F4F6F9;
            --card: #FFFFFF;
            --text: #1B2430;
            --muted: #6B7685;
            --success: #2E7D32;
            --blue: #1565C0;
            --warn: #8D6E00;
            --danger: #C62828;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 15px;
            line-height: 1.4;
            -webkit-text-size-adjust: 100%;
        }
        header {
            background: #fff;
            padding: 10px 16px;
            position: sticky;
            top: 0;
            z-index: 5;
            border-bottom: 1px solid #E3E7ED;
        }
        header .isi { max-width: 560px; margin: 0 auto; display: flex; align-items: center; gap: 12px; }
        header img { display: block; height: 34px; width: auto; flex: none; }
        header small { flex: 1; min-width: 0; text-align: right; font-size: 11.5px; line-height: 1.25; color: var(--muted); }

        .aturan {
            margin-bottom: 14px; padding: 14px 14px 12px; border-radius: 14px; background: var(--card);
            border-left: 4px solid var(--accent); box-shadow: 0 0 10px rgba(0,0,0,.04);
        }
        .aturan h2 { margin: 0 0 8px; font-size: 14px; color: var(--primary); }
        .aturan ol { margin: 0; padding-left: 20px; font-size: 12.5px; line-height: 1.45; color: #2B3645; }
        .aturan li { margin-bottom: 5px; }
        .aturan li:last-child { margin-bottom: 0; }
        main { max-width: 560px; margin: 0 auto; padding: 16px 16px 40px; }

        .tombol-baris { display: flex; gap: 10px; }
        .tombol {
            appearance: none; border: 0; cursor: pointer; font: inherit; font-weight: 700;
            height: 54px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .tombol svg { width: 22px; height: 22px; flex: none; }
        .tombol.utama { flex: 3; background: var(--accent); color: #fff; font-size: 15px; }
        .tombol.kedua { flex: 2; background: transparent; color: var(--primary); border: 1.5px solid rgba(30,58,95,.4); font-weight: 600; }
        .tombol:disabled { opacity: .5; cursor: not-allowed; }
        .tombol:not(:disabled):active { transform: scale(.985); }
        input[type=file] { display: none; }

        .catatan {
            margin-top: 12px; padding: 12px; border-radius: 12px; font-size: 12px; line-height: 1.4;
            background: #FFF8E1; border: 1px solid #FFE082; color: #5D4B00; display: flex; gap: 10px;
        }
        .catatan svg { width: 18px; height: 18px; flex: none; color: #8D6E00; margin-top: 1px; }

        .status {
            margin-top: 14px; background: var(--card); border-radius: 12px; padding: 12px 14px; font-size: 13px;
            box-shadow: 0 0 10px rgba(0,0,0,.04);
        }
        .status .baris { display: flex; justify-content: space-between; gap: 8px; color: var(--muted); }
        .bar { height: 6px; border-radius: 4px; background: #E3E7ED; overflow: hidden; margin-top: 8px; }
        .bar > i { display: block; height: 100%; width: 0; background: var(--accent); transition: width .2s; }
        .galat { color: var(--danger); }

        .foto { margin-top: 16px; border-radius: 14px; overflow: hidden; background: #dde2e9; }
        .foto img { display: block; width: 100%; max-height: 300px; object-fit: cover; }

        .proses { text-align: center; color: var(--muted); font-size: 13px; padding: 26px 0 6px; }
        .spinner {
            width: 30px; height: 30px; margin: 0 auto 10px; border-radius: 50%;
            border: 3px solid #D5DBE3; border-top-color: var(--accent); animation: putar .8s linear infinite;
        }
        @keyframes putar { to { transform: rotate(360deg); } }

        h2 { font-size: 15px; margin: 22px 0 0; font-weight: 700; }
        .ringkas { font-size: 12.5px; color: #4A5565; margin: 4px 0 10px; }
        .peringatan {
            margin-top: 20px; padding: 12px; border-radius: 12px; font-size: 12px; line-height: 1.4;
            background: #FFEBEE; border: 1px solid #EF9A9A; color: #8E1B1B; display: flex; gap: 10px;
        }
        .peringatan svg { width: 18px; height: 18px; flex: none; color: var(--danger); margin-top: 1px; }
        .kosong { margin-top: 16px; background: var(--card); padding: 16px; border-radius: 14px; font-size: 13px; }

        .kartu {
            margin-top: 12px; background: var(--card); border-radius: 14px; padding: 16px;
            box-shadow: 0 0 10px rgba(0,0,0,.04);
        }
        .kartu-atas { display: flex; align-items: center; gap: 10px; }
        .urutan {
            width: 30px; height: 30px; border-radius: 8px; background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 700; flex: none;
        }
        .judul { flex: 1; min-width: 0; }
        .judul b { display: block; font-size: 15px; overflow-wrap: anywhere; }
        .judul span { font-size: 11.5px; color: var(--muted); }
        .chip-mirip { padding: 5px 10px; border-radius: 8px; text-align: center; flex: none; }
        .chip-mirip b { display: block; font-size: 14px; font-weight: 800; }
        .chip-mirip span { font-size: 9.5px; font-weight: 600; }
        .zona {
            display: inline-block; margin-top: 12px; padding: 5px 10px; border-radius: 8px;
            background: rgba(242,107,0,.12); color: #A84300; font-weight: 700; font-size: 12.5px;
        }
        .info { margin-top: 10px; }
        .info div { display: flex; gap: 8px; font-size: 12.5px; margin-bottom: 4px; }
        .info dt, .info .k { width: 82px; flex: none; color: var(--muted); }
        .info .v { flex: 1; overflow-wrap: anywhere; }
        .kadar-judul { margin: 10px 0 6px; font-size: 11.5px; color: var(--muted); }
        .kadar { display: flex; flex-wrap: wrap; gap: 8px; }
        .kadar div { width: 74px; padding: 6px 0; text-align: center; background: var(--bg); border-radius: 8px; }
        .kadar small { display: block; font-size: 10.5px; color: var(--muted); }
        .kadar b { font-size: 13px; }

        .btn-ref {
            margin-top: 12px; display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px;
            border-radius: 8px; border: 1px solid var(--primary); background: transparent; color: var(--primary);
            font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer;
        }
        .btn-ref svg { width: 16px; height: 16px; }
        .modal {
            position: fixed; inset: 0; z-index: 50; background: rgba(0,0,0,.72);
            display: flex; align-items: center; justify-content: center; padding: 16px;
        }
        .modal-isi { position: relative; max-width: 560px; width: 100%; }
        .modal-isi img { display: block; width: 100%; max-height: 78vh; object-fit: contain; border-radius: 12px; background: #000; }
        .modal-cap { margin-top: 8px; color: #fff; font-size: 12.5px; text-align: center; }
        .modal-tutup {
            position: absolute; top: -14px; right: -6px; width: 34px; height: 34px; border-radius: 50%;
            border: 0; background: #fff; color: #111; font-size: 20px; line-height: 1; cursor: pointer;
        }

        footer { margin-top: 24px; text-align: center; font-size: 11.5px; color: #98A1AE; }
        .sembunyi { display: none !important; }
    </style>
</head>
<body data-model-versi="{{ $modelVersi }}">
<header>
    <div class="isi">
        <img src="/images/geolens-logo.png" alt="GeoLens" width="155" height="34">
        <small>Pembanding material berdasarkan foto</small>
    </div>
</header>

<main>
    <section class="aturan" aria-label="Aturan pengambilan gambar">
        <h2>Aturan pengambilan gambar</h2>
        <ol>
            <li>Ambil gambar dengan warna material yang asli.</li>
            <li>Jika material batu, bersihkan dulu permukaannya. Bila perlu, belah batu tersebut lalu ambil gambar pada belahannya.</li>
            <li>Jika material soil, bersihkan pengotor di permukaan sebelum mengambil gambar.</li>
            <li>Ambil gambar di tempat terang atau dengan cahaya yang cukup.</li>
        </ol>
    </section>

    <div class="tombol-baris">
        <button class="tombol utama" id="btn-kamera" type="button" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3.5"/></svg>
            Ambil Foto
        </button>
        <button class="tombol kedua" id="btn-galeri" type="button" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            Galeri
        </button>
    </div>
    <input type="file" id="in-kamera" accept="image/*" capture="environment">
    <input type="file" id="in-galeri" accept="image/*">

    <div class="catatan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <div>Hanya referensi berdasarkan kemiripan foto, bukan hasil lab. Kadar yang tampil milik sampel referensi, bukan material yang difoto. Foto diproses di perangkat Anda dan tidak diunggah.</div>
    </div>

    <div class="status" id="status">
        <div class="baris"><span id="status-teks">Menyiapkan…</span><span id="status-angka"></span></div>
        <div class="bar" id="status-bar"><i id="status-isi"></i></div>
    </div>

    <div id="foto-wadah" class="foto sembunyi"><img id="foto" alt="Foto yang dipindai"></div>
    <div id="hasil"></div>

    <footer id="footer"></footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/onnxruntime-web@1.31.1/dist/ort.wasm.min.js"></script>
@verbatim
<script>
(() => {
    'use strict';

    // ---------- Pengaturan (samakan dengan aplikasi: lib/models/loop_sampel.dart) ----------
    const UKURAN = 224;
    const BATAS_MINIMUM = 0.70;
    const MODEL_VERSI = document.body.dataset.modelVersi;
    const URL_MODEL = `/geolens/model/${encodeURIComponent(MODEL_VERSI)}.onnx`;
    const URL_DATA = '/api/loop/sampel';
    const KUNCI_CACHE = 'loop_data_v1';
    const URUTAN_KADAR = [
        ['ni', 'Ni'], ['fe', 'Fe'], ['mgo', 'MgO'], ['sio2', 'SiO2'],
        ['co', 'Co'], ['cao', 'CaO'], ['cr2o3', 'Cr2O3'], ['al2o3', 'Al2O3'],
        ['cr', 'Cr'], ['fe_ni', 'Fe/Ni'], ['s_m', 'S/M'], ['bc', 'BC'],
    ];

    ort.env.wasm.wasmPaths = 'https://cdn.jsdelivr.net/npm/onnxruntime-web@1.31.1/dist/';
    ort.env.wasm.numThreads = 1; // tanpa cross-origin isolation, 1 utas paling stabil

    const $ = (id) => document.getElementById(id);
    const el = {
        kamera: $('btn-kamera'), galeri: $('btn-galeri'),
        inKamera: $('in-kamera'), inGaleri: $('in-galeri'),
        statusBox: $('status'), statusTeks: $('status-teks'), statusAngka: $('status-angka'),
        statusBar: $('status-bar'), statusIsi: $('status-isi'),
        fotoWadah: $('foto-wadah'), foto: $('foto'), hasil: $('hasil'), footer: $('footer'),
    };

    let sampel = [];
    let sesi = null;
    let sibuk = false;

    // ---------- Util ----------
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    function setStatus(teks, { angka = '', persen = null, galat = false } = {}) {
        el.statusBox.classList.remove('sembunyi');
        el.statusTeks.textContent = teks;
        el.statusTeks.classList.toggle('galat', galat);
        el.statusAngka.textContent = angka;
        el.statusBar.classList.toggle('sembunyi', persen === null);
        if (persen !== null) el.statusIsi.style.width = `${Math.max(0, Math.min(100, persen))}%`;
    }

    function perbaruiTombol() {
        const siap = !!sesi && sampel.length > 0 && !sibuk;
        el.kamera.disabled = !siap;
        el.galeri.disabled = !siap;
    }

    function angka(v) {
        const s = Number(v).toFixed(2);
        return s.includes('.') ? s.replace(/0+$/, '').replace(/\.$/, '') : s;
    }

    // ---------- Data sampel (cache di browser; hanya unduh bila versi server berubah) ----------
    function bacaCache() {
        try {
            const raw = localStorage.getItem(KUNCI_CACHE);
            return raw ? JSON.parse(raw) : null;
        } catch { return null; }
    }

    function tampungData(json) {
        sampel = json.sampel.map((s) => ({ ...s, vektor: Float32Array.from(s.embedding) }));
    }

    async function muatData() {
        const cache = bacaCache();
        if (cache && cache.model_versi === MODEL_VERSI) tampungData(cache);

        try {
            const url = cache?.versi ? `${URL_DATA}?versi=${encodeURIComponent(cache.versi)}` : URL_DATA;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (res.ok) {
                const json = await res.json();
                if (!json.tidak_berubah && json.model_versi === MODEL_VERSI) {
                    tampungData(json);
                    try { localStorage.setItem(KUNCI_CACHE, JSON.stringify(json)); } catch { /* penyimpanan penuh: abaikan */ }
                }
            }
        } catch { /* offline: pakai cache */ }

        if (!sampel.length) throw new Error('Data sampel referensi belum bisa diunduh. Periksa koneksi lalu muat ulang halaman.');
        el.footer.textContent = `${sampel.length} sampel referensi`;
    }

    // ---------- Model ----------
    async function muatModel() {
        const res = await fetch(URL_MODEL);
        if (!res.ok) throw new Error(`Model tidak dapat diunduh (HTTP ${res.status}).`);
        const total = Number(res.headers.get('Content-Length')) || 0;
        const reader = res.body.getReader();
        const potongan = [];
        let diterima = 0;
        for (;;) {
            const { done, value } = await reader.read();
            if (done) break;
            potongan.push(value);
            diterima += value.length;
            setStatus('Mengunduh model…', {
                angka: total ? `${(diterima / 1048576).toFixed(1)} / ${(total / 1048576).toFixed(1)} MB` : `${(diterima / 1048576).toFixed(1)} MB`,
                persen: total ? (diterima / total) * 100 : 50,
            });
        }
        const buf = new Uint8Array(diterima);
        let o = 0;
        for (const p of potongan) { buf.set(p, o); o += p.length; }

        setStatus('Menyiapkan model…', { persen: 100 });
        sesi = await ort.InferenceSession.create(buf, { executionProviders: ['wasm'] });
    }

    // ---------- Pra-proses foto: harus sama dengan aplikasi / server ----------
    // putar sesuai EXIF -> RGB -> kecilkan (maks 1280) -> pepatkan ke 224x224 -> piksel 0-255 urutan CHW
    async function bukaGambar(file) {
        if (window.createImageBitmap) {
            try { return await createImageBitmap(file, { imageOrientation: 'from-image' }); } catch { /* lanjut ke cara cadangan */ }
        }
        return await new Promise((ok, gagal) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { URL.revokeObjectURL(url); ok(img); };
            img.onerror = () => { URL.revokeObjectURL(url); gagal(new Error('format')); };
            img.src = url;
        });
    }

    function kanvas(w, h) {
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        const ctx = c.getContext('2d', { willReadFrequently: true });
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        return { c, ctx };
    }

    async function siapkanPiksel(file) {
        const gambar = await bukaGambar(file);
        const w0 = gambar.width || gambar.naturalWidth;
        const h0 = gambar.height || gambar.naturalHeight;
        if (!w0 || !h0) throw new Error('format');

        // langkah 1: maks 1280 px (seperti thumbnail di server), supaya pengecilan ke 224 lebih halus
        const skala = Math.min(1, 1280 / Math.max(w0, h0));
        const a = kanvas(Math.max(1, Math.round(w0 * skala)), Math.max(1, Math.round(h0 * skala)));
        a.ctx.drawImage(gambar, 0, 0, a.c.width, a.c.height);
        if (gambar.close) gambar.close();

        // langkah 2: dipepatkan ke 224x224
        const b = kanvas(UKURAN, UKURAN);
        b.ctx.drawImage(a.c, 0, 0, UKURAN, UKURAN);
        const rgba = b.ctx.getImageData(0, 0, UKURAN, UKURAN).data;

        const luas = UKURAN * UKURAN;
        const out = new Float32Array(3 * luas);
        for (let i = 0; i < luas; i++) {
            out[i] = rgba[i * 4];
            out[luas + i] = rgba[i * 4 + 1];
            out[2 * luas + i] = rgba[i * 4 + 2];
        }
        return out;
    }

    async function sidikJari(file) {
        const piksel = await siapkanPiksel(file);
        const hasil = await sesi.run({ piksel: new ort.Tensor('float32', piksel, [1, 3, UKURAN, UKURAN]) });
        return hasil.sidik_jari.data;
    }

    // ---------- Pencocokan ----------
    function cocokkan(kueri, jumlah = 3) {
        const hasil = [];
        for (const s of sampel) {
            if (s.vektor.length !== kueri.length) continue;
            let dot = 0;
            for (let i = 0; i < kueri.length; i++) dot += kueri[i] * s.vektor[i];
            hasil.push({ sampel: s, kemiripan: dot });
        }
        hasil.sort((x, y) => y.kemiripan - x.kemiripan);
        return hasil.slice(0, jumlah);
    }

    function tingkat(sim) {
        if (sim >= 0.90) return { label: 'Sangat mirip', warna: 'var(--success)' };
        if (sim >= 0.80) return { label: 'Mirip', warna: 'var(--blue)' };
        if (sim >= BATAS_MINIMUM) return { label: 'Cukup mirip', warna: 'var(--warn)' };
        return { label: 'Kemiripan rendah', warna: 'var(--danger)' };
    }

    // ---------- Tampilan hasil ----------
    const IKON_PERINGATAN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.7 18-8-14a2 2 0 0 0-3.5 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3z"/><path d="M12 9v4M12 17h.01"/></svg>';

    function baris(label, nilai) {
        if (nilai == null || String(nilai).trim() === '') return '';
        return `<div><span class="k">${esc(label)}</span><span class="v">${esc(nilai)}</span></div>`;
    }

    function kartu(urutan, h) {
        const s = h.sampel;
        const t = tingkat(h.kemiripan);
        const kadar = URUTAN_KADAR
            .filter(([k]) => s.kadar && s.kadar[k] != null)
            .map(([k, l]) => `<div><small>${esc(l)}</small><b>${esc(angka(s.kadar[k]))}</b></div>`)
            .join('');

        return `
        <article class="kartu">
            <div class="kartu-atas">
                <div class="urutan">${urutan}</div>
                <div class="judul">
                    <b>${esc(s.kode_sample || s.code)}</b>
                    <span>Sampel referensi ${esc(s.code)}${s.inisial_front ? ' • ' + esc(s.inisial_front) : ''}</span>
                </div>
                <div class="chip-mirip" style="background:color-mix(in srgb, ${t.warna} 12%, transparent);color:${t.warna}">
                    <b>${Math.round(h.kemiripan * 100)}%</b><span>${esc(t.label)}</span>
                </div>
            </div>
            ${s.zona ? `<span class="zona">${esc(s.zona)}</span>` : ''}
            <div class="info">
                ${baris('Warna', s.warna)}${baris('Ukuran', s.ukuran)}${baris('Komposisi', s.komposisi)}${baris('Keterangan', s.keterangan)}
            </div>
            ${kadar ? `<div class="kadar-judul">Kadar sampel referensi (%)</div><div class="kadar">${kadar}</div>` : ''}
            <button type="button" class="btn-ref" data-code="${esc(s.code)}" data-judul="${esc(s.kode_sample || s.code)}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>
                Lihat foto referensi
            </button>
        </article>`;
    }

    function tampilkanHasil(hasil) {
        if (!hasil.length) {
            el.hasil.innerHTML = '<div class="kosong">Tidak ada sampel referensi untuk dibandingkan.</div>';
            return;
        }

        const hitung = {};
        for (const h of hasil) if (h.sampel.zona) hitung[h.sampel.zona] = (hitung[h.sampel.zona] || 0) + 1;
        let ringkasan = '';
        const zonaTop = Object.entries(hitung).sort((a, b) => b[1] - a[1])[0];
        if (zonaTop && hasil.length > 1) {
            ringkasan = `<div class="ringkas">${zonaTop[1]} dari ${hasil.length} sampel termirip berzona ${esc(zonaTop[0])}</div>`;
        }

        const rendah = hasil[0].kemiripan < BATAS_MINIMUM;
        el.hasil.innerHTML = `
            ${rendah ? `<div class="peringatan">${IKON_PERINGATAN}<div>Kemiripan rendah: tidak ada sampel referensi yang benar-benar mirip dengan foto ini. Hasil di bawah kurang dapat diandalkan. Coba foto lebih dekat, satu jenis material per bingkai, dengan cahaya yang cukup.</div></div>` : ''}
            <h2>Sampel referensi termirip</h2>
            ${ringkasan}
            ${hasil.map((h, i) => kartu(i + 1, h)).join('')}`;
    }

    // ---------- Alur utama ----------
    async function proses(file) {
        if (!file || sibuk) return;
        sibuk = true;
        perbaruiTombol();

        el.foto.src = URL.createObjectURL(file);
        el.fotoWadah.classList.remove('sembunyi');
        el.hasil.innerHTML = '<div class="proses"><div class="spinner"></div>Membandingkan dengan sampel referensi…</div>';

        try {
            const vektor = await sidikJari(file);
            tampilkanHasil(cocokkan(vektor));
            el.hasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e) {
            el.hasil.innerHTML = '<div class="kosong galat">Foto tidak dapat diproses. Gunakan foto JPG atau PNG, lalu coba lagi.</div>';
            console.error('[Loop]', e);
        } finally {
            sibuk = false;
            perbaruiTombol();
        }
    }

    function pilih(input) {
        input.addEventListener('change', () => {
            const f = input.files && input.files[0];
            input.value = ''; // supaya foto yang sama bisa dipilih lagi
            proses(f);
        });
    }

    // ---------- Modal foto referensi ----------
    function tutupModal() {
        const m = document.getElementById('modal-ref');
        if (m) m.remove();
    }

    function bukaModal(code, judul) {
        tutupModal();
        const m = document.createElement('div');
        m.id = 'modal-ref';
        m.className = 'modal';
        m.innerHTML = `<div class="modal-isi">
            <button type="button" class="modal-tutup" aria-label="Tutup">×</button>
            <img alt="Foto referensi" src="/geolens/foto/${encodeURIComponent(code)}">
            <div class="modal-cap">${esc(judul)} • foto referensi, bukan foto Anda</div>
        </div>`;
        m.addEventListener('click', (e) => {
            if (e.target === m || e.target.classList.contains('modal-tutup')) tutupModal();
        });
        m.querySelector('img').addEventListener('error', () => {
            m.querySelector('.modal-cap').textContent = 'Foto referensi tidak tersedia.';
            m.querySelector('img').remove();
        });
        document.body.appendChild(m);
    }

    el.hasil.addEventListener('click', (e) => {
        const b = e.target.closest('.btn-ref');
        if (b) bukaModal(b.dataset.code, b.dataset.judul);
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutupModal(); });

    el.kamera.addEventListener('click', () => el.inKamera.click());
    el.galeri.addEventListener('click', () => el.inGaleri.click());
    pilih(el.inKamera);
    pilih(el.inGaleri);

    (async () => {
        try {
            setStatus('Mengunduh data sampel…', { persen: null });
            await muatData();
            await muatModel();
            setStatus('Siap. Ambil foto material untuk dibandingkan.', { persen: null });
            el.statusBox.style.display = 'none'; // sembunyikan setelah siap; muncul lagi hanya bila galat
        } catch (e) {
            el.statusBox.style.display = '';
            setStatus(e.message || 'Gagal menyiapkan GeoLens.', { galat: true, persen: null });
            console.error('[Loop]', e);
        }
        perbaruiTombol();
    })();
})();
</script>
@endverbatim
</body>
</html>
