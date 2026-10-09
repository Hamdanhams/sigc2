<?php

namespace App\Services;

use App\Models\Cuti;
use App\Models\HariLibur;
use App\Models\Personil;
use App\Models\SaldoCutiLog;
use App\Models\UserPegawai;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Seluruh aturan Pengajuan Cuti ada di sini (dipakai API dan Filament).
 *
 * Aturan:
 *  - Hari yang dihitung = hari kerja (tanpa Sabtu, Minggu, dan hari libur di tabel hari_liburs).
 *  - Alur: Pengawas Senior -> Work Unit Head. Kalau tidak ada Pengawas Senior aktif
 *    saat pengajuan dibuat, langsung ke Work Unit Head.
 *  - Saldo dipotong saat disetujui Work Unit Head (persetujuan akhir).
 *  - Saldo boleh minus sampai BATAS_SALDO_MINUS. Pengajuan yang masih menunggu ikut dihitung.
 */
class CutiService
{
    public const BATAS_SALDO_MINUS = -6;

    public const ZONA = 'Asia/Makassar';

    public const MAKS_RENTANG_HARI = 366;

    public const RIWAYAT_HARI = 30;

    public function hariIni(): Carbon
    {
        return Carbon::now(self::ZONA)->startOfDay();
    }

    public function parseTanggal(string $ymd): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $ymd, self::ZONA)->startOfDay();
    }

    /** Hari kerja di antara dua tanggal (inklusif), tanpa Sabtu/Minggu/hari libur. */
    public function hitungHariKerja(Carbon $mulai, Carbon $selesai): int
    {
        $libur = HariLibur::whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->pluck('tanggal')
            ->map(fn($t) => $t->toDateString())
            ->flip();

        $hari = 0;
        for ($d = $mulai->copy(); $d->lte($selesai); $d->addDay()) {
            if ($d->isWeekend() || isset($libur[$d->toDateString()])) {
                continue;
            }
            $hari++;
        }
        return $hari;
    }

    public function adaPengawasSeniorAktif(): bool
    {
        return UserPegawai::where('jabatan', 'pengawas_senior')->where('status', 'active')->exists();
    }

    public function hariMenunggu(Personil $personil): int
    {
        return (int) Cuti::where('personil_id', $personil->id)
            ->whereIn('status', ['menunggu', 'menunggu_wuh'])
            ->sum('jumlah_hari');
    }

    /** Ringkasan saldo untuk tampilan Personil. */
    public function ringkasan(Personil $personil): array
    {
        $menunggu = $this->hariMenunggu($personil);

        return [
            'saldo' => (int) $personil->saldo_cuti,
            'hari_menunggu' => $menunggu,
            'batas_minus' => self::BATAS_SALDO_MINUS,
            // Hari terbanyak yang masih boleh diajukan sekarang.
            'maks_hari_diajukan' => max(0, (int) $personil->saldo_cuti - $menunggu - self::BATAS_SALDO_MINUS),
        ];
    }

    /**
     * Validasi pengajuan. Mengembalikan jumlah hari kerja, atau melempar CutiException.
     */
    public function validasi(Personil $personil, Carbon $mulai, Carbon $selesai): int
    {
        if ($selesai->lt($mulai)) {
            throw new CutiException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }
        if ($mulai->lt($this->hariIni())) {
            throw new CutiException('Tanggal cuti tidak boleh di masa lalu.');
        }
        if ($mulai->diffInDays($selesai) > self::MAKS_RENTANG_HARI) {
            throw new CutiException('Rentang tanggal terlalu panjang.');
        }

        $hari = $this->hitungHariKerja($mulai, $selesai);
        if ($hari === 0) {
            throw new CutiException('Rentang tanggal ini tidak berisi hari kerja (hanya Sabtu, Minggu, atau hari libur).');
        }

        $bertumpuk = Cuti::where('personil_id', $personil->id)
            ->whereIn('status', ['menunggu', 'menunggu_wuh', 'disetujui'])
            ->whereDate('tanggal_mulai', '<=', $selesai->toDateString())
            ->whereDate('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();
        if ($bertumpuk) {
            throw new CutiException('Tanggal bertumpuk dengan pengajuan cuti Anda yang masih menunggu atau sudah disetujui.');
        }

        $saldo = (int) $personil->saldo_cuti;
        $menunggu = $this->hariMenunggu($personil);
        if ($saldo - $menunggu - $hari < self::BATAS_SALDO_MINUS) {
            throw new CutiException(
                "Saldo cuti tidak cukup. Saldo $saldo hari, sedang menunggu $menunggu hari, diajukan $hari hari. "
                . 'Saldo tidak boleh kurang dari ' . self::BATAS_SALDO_MINUS . ' hari.'
            );
        }

        return $hari;
    }

    /** Pratinjau untuk formulir (tanpa menyimpan). */
    public function pratinjau(Personil $personil, Carbon $mulai, Carbon $selesai): array
    {
        $ringkasan = $this->ringkasan($personil);
        $hari = $this->hitungHariKerja($mulai, $selesai);

        $pesan = null;
        try {
            $this->validasi($personil, $mulai, $selesai);
        } catch (CutiException $e) {
            $pesan = $e->getMessage();
        }

        return $ringkasan + [
            'jumlah_hari' => $hari,
            'saldo_setelah_disetujui' => $ringkasan['saldo'] - $ringkasan['hari_menunggu'] - $hari,
            'boleh_diajukan' => $pesan === null,
            'pesan' => $pesan,
        ];
    }

    public function ajukan(Personil $personil, Carbon $mulai, Carbon $selesai, string $alasan): Cuti
    {
        return DB::transaction(function () use ($personil, $mulai, $selesai, $alasan) {
            // Kunci baris Personil agar dua pengajuan bersamaan tidak sama-sama lolos cek saldo.
            $personil = Personil::whereKey($personil->id)->lockForUpdate()->firstOrFail();

            $hari = $this->validasi($personil, $mulai, $selesai);

            return Cuti::create([
                'personil_id' => $personil->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'jumlah_hari' => $hari,
                'alasan' => $alasan,
                'status' => $this->adaPengawasSeniorAktif() ? 'menunggu' : 'menunggu_wuh',
            ]);
        });
    }

    // ------------------------------------------------------------- approval

    /** Apakah pengajuan ini sedang menunggu aksi akun tersebut. */
    public function bisaDiproses(Cuti $cuti, UserPegawai $user): bool
    {
        if ($user->jabatan === 'pengawas_senior') {
            return $cuti->status === 'menunggu';
        }
        if ($user->jabatan === 'work_unit_head') {
            return $cuti->status === 'menunggu_wuh'
                // Pengawas Senior sudah tidak ada/aktif -> Work Unit Head boleh memproses langsung.
                || ($cuti->status === 'menunggu' && !$this->adaPengawasSeniorAktif());
        }
        return false;
    }

    public function setujui(int $cutiId, UserPegawai $user): Cuti
    {
        return DB::transaction(function () use ($cutiId, $user) {
            $cuti = Cuti::whereKey($cutiId)->lockForUpdate()->firstOrFail();

            if (!$this->bisaDiproses($cuti, $user)) {
                throw new CutiException('Pengajuan ini tidak sedang menunggu approval Anda.');
            }

            if ($user->jabatan === 'pengawas_senior') {
                $cuti->update([
                    'status' => 'menunggu_wuh',
                    'disetujui_senior_oleh' => $user->id,
                    'disetujui_senior_at' => now(),
                ]);
                return $cuti;
            }

            // Persetujuan akhir Work Unit Head: potong saldo (cek ulang saldo sebenarnya).
            $personil = Personil::whereKey($cuti->personil_id)->lockForUpdate()->firstOrFail();
            $sesudah = (int) $personil->saldo_cuti - $cuti->jumlah_hari;
            if ($sesudah < self::BATAS_SALDO_MINUS) {
                throw new CutiException(
                    "Saldo {$personil->nama} tidak mencukupi (saldo {$personil->saldo_cuti} hari, diajukan {$cuti->jumlah_hari} hari, "
                    . 'batas minimum ' . self::BATAS_SALDO_MINUS . ' hari).'
                );
            }

            $this->ubahSaldo($personil, -$cuti->jumlah_hari, 'cuti_disetujui', $cuti, "Cuti {$cuti->tanggal_mulai->toDateString()} s/d {$cuti->tanggal_selesai->toDateString()}", $user->nama);

            $cuti->update([
                'status' => 'disetujui',
                'disetujui_wuh_oleh' => $user->id,
                'disetujui_wuh_at' => now(),
            ]);
            return $cuti;
        });
    }

    public function tolak(int $cutiId, UserPegawai $user, string $catatan): Cuti
    {
        return DB::transaction(function () use ($cutiId, $user, $catatan) {
            $cuti = Cuti::whereKey($cutiId)->lockForUpdate()->firstOrFail();

            if (!$this->bisaDiproses($cuti, $user)) {
                throw new CutiException('Pengajuan ini tidak sedang menunggu approval Anda.');
            }

            $cuti->update([
                'status' => 'ditolak',
                'catatan_penolakan' => $catatan,
                'ditolak_oleh_jabatan' => $user->jabatan,
            ]);
            return $cuti;
        });
    }

    /** Query daftar pengajuan untuk Pengawas Senior / Work Unit Head. */
    public function queryUntukApprover(UserPegawai $user): Builder
    {
        $riwayat = now()->subDays(self::RIWAYAT_HARI);
        $query = Cuti::with('personil:id,nama,saldo_cuti');

        if ($user->jabatan === 'pengawas_senior') {
            return $query->where(function ($q) use ($riwayat) {
                $q->where('status', 'menunggu')
                    ->orWhere(fn($h) => $h->whereIn('status', ['menunggu_wuh', 'disetujui', 'ditolak'])->where('updated_at', '>=', $riwayat));
            });
        }

        $tanpaSenior = !$this->adaPengawasSeniorAktif();
        return $query->where(function ($q) use ($riwayat, $tanpaSenior) {
            $q->where('status', 'menunggu_wuh')
                ->when($tanpaSenior, fn($w) => $w->orWhere('status', 'menunggu'))
                ->orWhere(fn($h) => $h->where('updated_at', '>=', $riwayat)->where(function ($x) {
                    $x->where('status', 'disetujui')
                        ->orWhere(fn($t) => $t->where('status', 'ditolak')->where('ditolak_oleh_jabatan', 'work_unit_head'));
                }));
        });
    }

    // ---------------------------------------------------------------- saldo

    /** Admin mengatur saldo secara manual (dicatat di riwayat). */
    public function aturSaldo(Personil $personil, int $saldoBaru, ?string $catatan, ?string $oleh): void
    {
        DB::transaction(function () use ($personil, $saldoBaru, $catatan, $oleh) {
            $personil = Personil::whereKey($personil->id)->lockForUpdate()->firstOrFail();
            $selisih = $saldoBaru - (int) $personil->saldo_cuti;
            if ($selisih === 0) {
                return;
            }
            $this->ubahSaldo($personil, $selisih, 'manual', null, $catatan, $oleh);
        });
    }

    /** Dipanggil saat pengajuan yang sudah disetujui dihapus admin. */
    public function kembalikanSaldo(Cuti $cuti): void
    {
        DB::transaction(function () use ($cuti) {
            $personil = Personil::whereKey($cuti->personil_id)->lockForUpdate()->first();
            if (!$personil) {
                return;
            }
            $this->ubahSaldo($personil, $cuti->jumlah_hari, 'cuti_dikembalikan', $cuti, "Pengajuan cuti #{$cuti->id} dihapus admin", 'Admin');
        });
    }

    private function ubahSaldo(Personil $personil, int $perubahan, string $jenis, ?Cuti $cuti, ?string $catatan, ?string $oleh): void
    {
        $sebelum = (int) $personil->saldo_cuti;
        $sesudah = $sebelum + $perubahan;
        $personil->update(['saldo_cuti' => $sesudah]);

        SaldoCutiLog::create([
            'personil_id' => $personil->id,
            'perubahan' => $perubahan,
            'saldo_sebelum' => $sebelum,
            'saldo_sesudah' => $sesudah,
            'jenis' => $jenis,
            'cuti_id' => $cuti?->id,
            'catatan' => $catatan,
            'oleh' => $oleh,
        ]);
    }
}
