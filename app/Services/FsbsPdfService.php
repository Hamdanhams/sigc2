<?php

namespace App\Services;

use App\Models\Fsbs;
use App\Models\Front;
use App\Models\UserPegawai;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FsbsPdfService
{
    public function generate(string $frontInisial, string $tanggalMulai, string $tanggalSelesai, int $userPegawaiId)
    {
        $front = Front::where('inisial', $frontInisial)->first();
        $userPegawai = UserPegawai::find($userPegawaiId);
        $userPegawaiTtd = $this->getTtdBase64($userPegawai?->ttd);

        $tanggalList = [];
        $current = Carbon::parse($tanggalMulai);
        $end = Carbon::parse($tanggalSelesai);

        while ($current->lte($end)) {
            $tanggalList[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Kelompokkan jadi pasangan 2 tanggal per halaman
        $halaman = [];
        foreach (array_chunk($tanggalList, 2) as $pasangan) {
            $sisi = [];
            foreach ($pasangan as $tanggal) {
                $records = Fsbs::where('front', $frontInisial)
                    ->whereDate('created_at', $tanggal)
                    ->with('personil')
                    ->orderBy('created_at')
                    ->get();

                $personilPertama = $records->first()?->personil;

                $sisi[] = [
                    'tanggal' => $tanggal,
                    'hari' => Carbon::parse($tanggal)->translatedFormat('l'),
                    'lokasi' => $front->lokasi ?? '-',
                    'records' => $records,
                    'personilNama' => $personilPertama?->nama,
                    'personilTtd' => $this->getTtdBase64($personilPertama?->ttd),
                ];
            }
            $halaman[] = $sisi;
        }
        $logoBase64 = $this->getLogoBase64();
        $pdf = Pdf::loadView('pdf.pengantar-sample-fsbs', [
            'halaman' => $halaman,
            'userPegawai' => $userPegawai,
            'userPegawaiTtd' => $userPegawaiTtd,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'landscape');

        return $pdf;
    }

    protected function getTtdBase64(?string $path): ?string
    {
        if (!$path) return null;

        $fullPath = storage_path('app/private/' . $path);

        if (!file_exists($fullPath)) return null;

        $data = base64_encode(file_get_contents($fullPath));
        $mime = mime_content_type($fullPath);

        return "data:$mime;base64,$data";
    }
    protected function getLogoBase64(): ?string
    {
        $path = public_path('images/logo-antam.png');

        if (!file_exists($path)) return null;

        $data = base64_encode(file_get_contents($path));

        return "data:image/png;base64,$data";
    }
}
