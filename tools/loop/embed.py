"""Sidik jari visual (embedding) sebuah foto untuk fitur Loop.

Dipakai oleh server (job antrean) dan skrip penyiapan paket di komputer, supaya cara
memproses fotonya SAMA dengan yang dilakukan aplikasi di HP:
  foto -> putar sesuai EXIF -> RGB -> dipepatkan ke 224x224 (bicubic) -> model ONNX.

Kebutuhan: pip install onnxruntime pillow numpy
Pemakaian: python embed.py MODEL.onnx FOTO.jpg   -> mencetak JSON (daftar angka)
"""
import json
import sys

import numpy as np
from PIL import Image, ImageOps

UKURAN = 224
DESIMAL = 5


def muat_sesi(model_path):
    from onnxruntime import InferenceSession

    return InferenceSession(model_path, providers=["CPUExecutionProvider"])


def siapkan(path):
    img = Image.open(path)
    img = ImageOps.exif_transpose(img).convert("RGB")
    img.thumbnail((1280, 1280))
    img = img.resize((UKURAN, UKURAN), Image.BICUBIC)
    a = np.asarray(img, dtype=np.float32)  # HWC, 0-255
    return a.transpose(2, 0, 1)[None]


def embedding(sesi, path):
    hasil = sesi.run(None, {"piksel": siapkan(path)})[0][0]
    return [round(float(v), DESIMAL) for v in hasil]


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("pemakaian: python embed.py MODEL.onnx FOTO.jpg", file=sys.stderr)
        sys.exit(2)
    print(json.dumps(embedding(muat_sesi(sys.argv[1]), sys.argv[2])))
