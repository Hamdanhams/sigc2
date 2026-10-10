<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Pengelolaan akun oleh pemilik akun sendiri (Personil maupun User Pegawai).
 * Sengaja tanpa batas panjang/kompleksitas password (keputusan pemilik sistem).
 */
class AkunService
{
    /**
     * Ganti password. Melempar CutiException-style pesan lewat \InvalidArgumentException
     * yang pesannya aman ditampilkan ke pengguna.
     *
     * @param  Model  $user  Personil atau UserPegawai (memakai HasApiTokens)
     * @param  int|null  $tokenSaatIniId  token sesi yang dipertahankan; token lain dihapus
     */
    public function gantiPassword(Model $user, string $passwordLama, string $passwordBaru, ?int $tokenSaatIniId): void
    {
        if (!Hash::check($passwordLama, (string) $user->password)) {
            throw new \InvalidArgumentException('Password lama salah.');
        }

        if ($passwordBaru === $passwordLama) {
            throw new \InvalidArgumentException('Password baru tidak boleh sama dengan password lama.');
        }

        $user->forceFill(['password' => Hash::make($passwordBaru)])->save();

        // Perangkat lain yang masih login dengan password lama otomatis keluar.
        $user->tokens()
            ->when($tokenSaatIniId, fn($q) => $q->where('id', '!=', $tokenSaatIniId))
            ->delete();
    }
}
