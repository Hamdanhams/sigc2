<?php

return [
    // Interpreter Python (harus punya onnxruntime, pillow, numpy). Di VPS: venv khusus Loop.
    'python' => env('LOOP_PYTHON', 'python'),

    // Skrip sidik jari dan model ONNX (model tidak ada di git; salin manual ke storage/app/loop/).
    'skrip' => base_path('tools/loop/embed.py'),
    'model' => storage_path('app/loop/loop_int8.onnx'),

    // Batas waktu menghitung satu foto (detik).
    'timeout' => 120,
];
