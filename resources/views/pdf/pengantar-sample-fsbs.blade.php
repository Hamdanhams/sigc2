<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: avoid; }
        table { border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 3px; vertical-align: middle; }
        .no-border { border: none; padding: 2px; }
        .half { width: 49%; }
        .header-table td { border: 1px solid #000; }
        .title-cell { text-align: center; font-weight: bold; font-size: 11px; }
        .form-info-cell { font-size: 8px; }
        .info-table td { border: none; padding: 1px 3px; }
        .ttd-img { height: 40px; object-fit: contain; }
    </style>
</head>
<body>

@foreach ($halaman as $sisi)
<div class="page">
    <table style="width: 100%;">
        <tr>
            @foreach ($sisi as $data)
            <td class="no-border" style="width: 50%; vertical-align: top; padding-right: 5px;">

                <table class="header-table" style="width: 100%;">
                    <tr>
                        <td style="width: 30%; text-align: center;">
                            @if ($logoBase64)
                                <img src="{{ $logoBase64 }}" style="height: 30px; object-fit: contain;">
                            @else
                                <span style="font-weight: bold; font-size: 11px;">antam</span>
                            @endif
                        </td>
                        <td class="title-cell" style="width: 45%;">
                            FORM PENGANTAR SAMPLE<br>
                            FLOOR ATAU BENCH SAMPLING
                        </td>
                        <td class="form-info-cell" style="width: 25%;">
                            Form : F-270.503.001<br>
                            Rev &nbsp;&nbsp;: 0<br>
                            Tgl &nbsp;&nbsp;&nbsp;:
                        </td>
                    </tr>
                </table>

                <table class="info-table" style="width: 100%; margin-top: 4px;">
                    <tr>
                        <td width="15%">Hari</td>
                        <td width="35%">: {{ $data['hari'] }}</td>
                        <td width="15%">Shift</td>
                        <td width="35%">: 1</td>
                    </tr>
                    <tr>
                        <td>Tanggal Terima</td>
                        <td>: {{ \Carbon\Carbon::parse($data['tanggal'])->format('d/m/Y') }}</td>
                        <td>Lokasi</td>
                        <td>: {{ $data['lokasi'] }}</td>
                    </tr>
                </table>

                <table style="width: 100%; margin-top: 4px;">
                    <thead>
                        <tr>
                            <th style="width: 6%;">NO</th>
                            <th style="width: 34%;">KODE SAMPLE</th>
                            <th style="width: 15%;">JUMLAH INCR</th>
                            <th style="width: 15%;">GRIDDING</th>
                            <th style="width: 30%;">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['records'] as $i => $r)
                        <tr>
                            <td style="text-align: center;">{{ $i + 1 }}</td>
                            <td>{{ $r->kode_sampel }}</td>
                            <td style="text-align: center;">{{ $r->increment }}</td>
                            <td style="text-align: center;">{{ $r->gridding }}</td>
                            <td>{{ $r->keterangan }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="no-border" style="width: 100%; margin-top: 10px;">
                    <tr>
                        <td class="no-border" style="width: 50%;">
                            Pengawas<br>
                            PT. ANTAM, Tbk. UBPN SULTRA
                            <br><br>
                            @if ($userPegawaiTtd)
                                <img src="{{ $userPegawaiTtd }}" class="ttd-img"><br>
                            @else
                                <br><br>
                            @endif
                            _____________________<br>
                            NPP : {{ $userPegawai->npp ?? '.....' }}
                        </td>
                        <td class="no-border" style="width: 50%;">
                            Pomalaa, {{ \Carbon\Carbon::parse($data['tanggal'])->translatedFormat('d F Y') }}<br>
                            Pengawas,<br>
                            GRADE CONTROL
                            <br><br>
                            @if ($data['personilTtd'])
                                <img src="{{ $data['personilTtd'] }}" class="ttd-img"><br>
                            @else
                                <br><br>
                            @endif
                            _____________________<br>
                            NPP : {{ $data['personilNama'] ? ($data['records']->first()->personil->id_personil ?? '.....') : '.....' }}
                        </td>
                    </tr>
                </table>

            </td>
            @endforeach
            @if (count($sisi) < 2)
            <td class="no-border" style="width: 50%;"></td>
            @endif
        </tr>
    </table>
</div>
@endforeach

</body>
</html>