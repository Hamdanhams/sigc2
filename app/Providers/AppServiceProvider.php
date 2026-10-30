<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batasi percobaan ganti password (cegah menebak password lama berulang).
        // Dikunci per akun; tidak memakai limiter bawaan karena model Personil/UserPegawai
        // bukan Authenticatable (tidak punya getAuthIdentifier()).
        RateLimiter::for('akun-password', function (Request $request) {
            $user = $request->user();
            $kunci = $user ? class_basename($user) . ':' . $user->getKey() : $request->ip();

            return Limit::perMinute(10)->by($kunci);
        });

        // Pembatas umum per akun untuk rute API biasa. JANGAN pakai "throttle:N,1" di rute
        // auth:sanctum: bawaannya memanggil getAuthIdentifier() yang tidak ada di Personil/UserPegawai
        // sehingga rute error 500.
        RateLimiter::for('api-akun', function (Request $request) {
            $user = $request->user();
            $kunci = $user ? class_basename($user) . ':' . $user->getKey() : $request->ip();

            return Limit::perMinute(120)->by($kunci);
        });
    }
}
