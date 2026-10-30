<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UploadController extends Controller
{
    /**
     * Tanda tangan unggah langsung dari HP ke Cloudinary (foto tidak lewat VPS).
     * Rahasia API tetap di server; HP hanya menerima tanda tangan berumur pendek untuk
     * folder tetap. Satu tanda tangan boleh dipakai untuk banyak foto dalam waktu singkat.
     */
    /** Folder Cloudinary yang boleh diminta aplikasi; selain ini jatuh ke 'fsbs' (perilaku APK lama). */
    private const FOLDER_BOLEH = ['fsbs', 'produksi', 'kendala', 'permintaan', 'safety-meeting'];

    private function folderDariRequest(Request $request): string
    {
        $folder = (string) $request->input('folder', 'fsbs');

        return in_array($folder, self::FOLDER_BOLEH, true) ? $folder : 'fsbs';
    }

    public function signature(Request $request)
    {
        $url = parse_url((string) config('cloudinary.cloud_url'));
        $cloud = $url['host'] ?? null;
        $apiKey = $url['user'] ?? null;
        $secret = $url['pass'] ?? null;

        if (!$cloud || !$apiKey || !$secret) {
            return response()->json(['message' => 'Cloudinary belum dikonfigurasi'], 500);
        }

        $params = ['folder' => $this->folderDariRequest($request), 'timestamp' => time()];

        return response()->json([
            'upload_url' => "https://api.cloudinary.com/v1_1/{$cloud}/image/upload",
            'api_key' => $apiKey,
            'folder' => $params['folder'],
            'timestamp' => $params['timestamp'],
            'signature' => \Cloudinary\Api\ApiUtils::signParameters($params, $secret),
        ]);
    }

    public function uploadFoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $file = $request->file('foto');

        // Catat lama unggah ke Cloudinary untuk mendiagnosis sinkron yang lambat.
        $mulai = microtime(true);
        $uploadedFile = Cloudinary::upload(
            $file->getRealPath(),
            ['folder' => $this->folderDariRequest($request)]
        );
        $detik = round(microtime(true) - $mulai, 2);

        Log::info("upload-foto: {$detik} dtk ke Cloudinary, " . round($file->getSize() / 1024) . ' KB');

        return response()->json(['url' => $uploadedFile->getSecurePath()]);
    }
}
