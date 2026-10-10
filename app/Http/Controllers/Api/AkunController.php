<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AkunService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AkunController extends Controller
{
    public function gantiPassword(Request $request, AkunService $service)
    {
        $validator = Validator::make($request->all(), [
            'password_lama' => 'required|string',
            'password_baru' => 'required|string',
            'konfirmasi_password' => 'required|same:password_baru',
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'konfirmasi_password.required' => 'Konfirmasi password wajib diisi.',
            'konfirmasi_password.same' => 'Konfirmasi password tidak sama dengan password baru.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        try {
            $service->gantiPassword(
                $user,
                $request->password_lama,
                $request->password_baru,
                $user->currentAccessToken()?->id
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Password berhasil diganti.']);
    }
}
