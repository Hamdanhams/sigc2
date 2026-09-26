<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UploadController extends Controller
{
    public function uploadFoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $uploadedFile = Cloudinary::upload(
            $request->file('foto')->getRealPath(),
            ['folder' => 'fsbs']
        );

        return response()->json(['url' => $uploadedFile->getSecurePath()]);
    }
}
