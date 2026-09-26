<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPegawai;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $personil = Personil::where('username', $request->username)->first();

        if (!$personil || !Hash::check($request->password, $personil->password)) {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }

        $token = $personil->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'personil' => [
                'id' => $personil->id,
                'nama' => $personil->nama,
                'inisial' => $personil->inisial,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function loginPengawas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $userPegawai = UserPegawai::where('username', $request->username)->first();

        if (!$userPegawai || !Hash::check($request->password, $userPegawai->password)) {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }

        $token = $userPegawai->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user_pegawai' => [
                'id' => $userPegawai->id,
                'nama' => $userPegawai->nama,
                'npp' => $userPegawai->npp,
            ],
        ]);
    }
}
