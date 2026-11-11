"""Siapkan paket data Loop dari Excel + folder foto, untuk diimpor dengan `php artisan loop:impor`.

Isi paket (folder keluaran):
  sampel.json      data tiap sampel + sidik jari fotonya
  foto/{code}.jpg  foto dikecilkan (sisi terpanjang 1024 px, JPEG)

Struktur folder foto: dataset/{Code}/raw.(jpg|jpeg|png|heic) — foto "scale" diabaikan.

Kebutuhan: pip install onnxruntime pillow pillow-heif numpy openpyxl
Pemakaian:
  python siapkan_paket.py --excel data.xlsx --dataset datasheet --model loop_int8.onnx --keluar paket
"""
import argparse
import json
import os
import re
import sys

import openpyxl
import pillow_heif
from PIL import Image, ImageOps

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import embed  # noqa: E402

pillow_heif.register_heif_opener()

MODEL_VERSI = "dinov2s-int8-v1"  # samakan dengan App\Models\LoopSampel::MODEL_VERSI


def norm_code(s):
    return re.sub(r"\s+", "", str(s))


def angka(v):
    if v is None or str(v).strip() == "":
        return None
    try:
        return float(str(v).replace(",", "."))
    except ValueError:
        return None


def teks(v):
    if v is None:
        return None
    s = str(v).strip()
    return s or None


def cari_raw(folder):
    for f in sorted(os.listdir(folder)):
        if os.path.splitext(f)[0].lower() == "raw":
            return os.path.join(folder, f)
    return None


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--excel", required=True)
    ap.add_argument("--dataset", required=True)
    ap.add_argument("--model", required=True)
    ap.add_argument("--keluar", required=True)
    a = ap.parse_args()

    os.makedirs(os.path.join(a.keluar, "foto"), exist_ok=True)
    folder_asli = {norm_code(d): d for d in os.listdir(a.dataset) if os.path.isdir(os.path.join(a.dataset, d))}

    ws = openpyxl.load_workbook(a.excel, data_only=True).worksheets[0]
    rows = list(ws.iter_rows(values_only=True))
    idx = {str(h).strip().lower(): i for i, h in enumerate(rows[0]) if h is not None}

    def ambil(r, nama):
        i = idx.get(nama)
        return r[i] if i is not None else None

    sesi = embed.muat_sesi(a.model)
    hasil, tanpa_foto = [], []

    for r in rows[1:]:
        code = norm_code(ambil(r, "code") or "")
        if not code:
            continue
        item = {
            "code": code,
            "kode_sample": teks(ambil(r, "kode sample")),
            "incr": teks(ambil(r, "incr")),
            "inisial_front": teks(ambil(r, "front")),
            "zona": teks(ambil(r, "zona")),
            "warna": teks(ambil(r, "warna")),
            "ukuran": teks(ambil(r, "ukuran")),
            "komposisi": teks(ambil(r, "komposisi")),
            "keterangan": teks(ambil(r, "keterangan")),
            "ni": angka(ambil(r, "ni")), "co": angka(ambil(r, "co")), "fe": angka(ambil(r, "fe")),
            "sio2": angka(ambil(r, "sio2")), "cao": angka(ambil(r, "cao")), "mgo": angka(ambil(r, "mgo")),
            "cr2o3": angka(ambil(r, "cr2o3")), "al2o3": angka(ambil(r, "al2o3")), "cr": angka(ambil(r, "cr")),
            "fe_ni": angka(ambil(r, "fe/ni")), "s_m": angka(ambil(r, "s/m")), "bc": angka(ambil(r, "bc")),
            "foto": None, "embedding": None, "model_versi": None,
        }

        folder = folder_asli.get(code)
        raw = cari_raw(os.path.join(a.dataset, folder)) if folder else None
        if raw:
            img = ImageOps.exif_transpose(Image.open(raw)).convert("RGB")
            img.thumbnail((1024, 1024))
            tujuan = os.path.join(a.keluar, "foto", f"{code}.jpg")
            img.save(tujuan, "JPEG", quality=85)
            item["foto"] = f"foto/{code}.jpg"
            # Sidik jari dihitung dari JPEG yang sama dengan yang nanti disimpan di server.
            item["embedding"] = embed.embedding(sesi, tujuan)
            item["model_versi"] = MODEL_VERSI
        else:
            tanpa_foto.append(code)
        hasil.append(item)
        print(f"{code} ok" if raw else f"{code} TANPA FOTO", flush=True)

    with open(os.path.join(a.keluar, "sampel.json"), "w", encoding="utf-8") as f:
        json.dump(hasil, f, ensure_ascii=False)
    print(f"\nselesai: {len(hasil)} sampel, {len(hasil) - len(tanpa_foto)} dengan foto")
    if tanpa_foto:
        print("tanpa foto:", ", ".join(tanpa_foto))


if __name__ == "__main__":
    main()
