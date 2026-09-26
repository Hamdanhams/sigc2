<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: avoid; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px; vertical-align: top; }
        .bordered td, .bordered th { border: 1px solid #000; }
        .header-table td { border: 1px solid #000; vertical-align: middle; }
        .kop-left { font-weight: bold; font-size: 12px; width: 25%; }
        .kop-title { text-align: center; font-weight: bold; font-size: 13px; width: 48%; }
        .kop-doc { text-align: right; font-weight: bold; width: 27%; }
        .info-table td { border: none; padding: 3px 4px; }
        .strip-box { border: 1px solid #000; padding: 5px; }
        .signature-box { text-align: center; width: 33%; }
        .ttd-img { height: 50px; object-fit: contain; }
    </style>
</head>
<body>

@foreach ($laporanData as $item)
<div class="page">

    <table class="header-table">
        <tr>
            <td class="kop-left">
                PT ANTAM Tbk<br>
                UBPN Kolaka
            </td>
            <td class="kop-title">
                LAPORAN HARIAN<br>
                GRADE CONTROL PENAMBANGAN BIJIH NIKEL
            </td>
            <td class="kop-doc">
                No.Dok.: F-270.501.R0
            </td>
        </tr>
    </table>

    <br>

    <table class="info-table">
        <tr>
            <td width="12%">Hari / Tanggal</td>
            <td width="38%">: {{ \Carbon\Carbon::parse($item['produksi']->tanggal)->translatedFormat('l, d F Y') }}</td>
            <td width="12%">Lokasi Tambang</td>
            <td width="38%">: {{ $item['produksi']->front->lokasi ?? '-' }}</td>
        </tr>
        <tr>
            <td>Shift</td>
            <td>: {{ $item['produksi']->shift }}</td>
            <td>Nama Front</td>
            <td>: {{ $item['produksi']->front->nama_front ?? '-' }}</td>
        </tr>
        <tr>
            <td>Jam Kerja</td>
            <td>: {{ $item['produksi']->jam_mulai ? \Carbon\Carbon::parse($item['produksi']->jam_mulai)->format('H:i') : '-' }} - {{ $item['produksi']->jam_selesai ? \Carbon\Carbon::parse($item['produksi']->jam_selesai)->format('H:i') : '-' }}</td>
            <td>Fleet</td>
            <td>: {{ $item['produksi']->fleet ?? '-' }}</td>
        </tr>
    </table>

    <br>

    <table class="bordered">
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Kode Produksi Tambang</th>
                <th colspan="2">Elevasi</th>
                <th colspan="2">Blok Model</th>
                <th rowspan="2">Tujuan Dumping</th>
                <th rowspan="2">Jumlah Ritase</th>
                <th rowspan="2">Gridding</th>
                <th rowspan="2">Keterangan</th>
            </tr>
            <tr>
                <th>Dari</th>
                <th>Ke</th>
                <th>Ni (%)</th>
                <th>Fe (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($item['details'] as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $d['kode_produksi_tambang'] }}</td>
                <td>{{ $d['dari'] }}</td>
                <td>{{ $d['ke'] }}</td>
                <td>{{ $d['ni_bm'] !== null ? number_format($d['ni_bm'], 2) : '-' }}</td>
                <td>{{ $d['fe_bm'] !== null ? number_format($d['fe_bm'], 2) : '-' }}</td>
                <td>{{ $d['tujuan_dumping'] }}</td>
                <td>{{ $d['ritase'] !== null ? number_format($d['ritase'], 0) : '-' }}</td>
                <td>{{ $d['gridding'] }}</td>
                @if ($i === 0)
                    <td rowspan="{{ count($item['details']) }}">{{ $item['produksi']->keterangan }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="strip-box" style="border-top: none;">
        <strong>Perencanaan penambangan esok hari:</strong> {{ $item['produksi']->rencana_produksi_besok }}
    </div>

    <div class="strip-box" style="border-top: none; text-align: center; font-weight: bold;">
        Total Ritase: {{ number_format($item['totalRitase'], 0) }}
    </div>

    <br>

    <div style="text-align: right;">Pomalaa, {{ $tanggalCetak }}</div>

    <br><br>

    <table class="info-table">
        <tr>
            <td class="signature-box">
                Mengetahui,<br>
                Grade Control Work Unit Head
                <br><br>
                @if ($headTtd)
                    <img src="{{ $headTtd }}" class="ttd-img"><br>
                @else
                    <br><br><br>
                @endif
                <strong>({{ $workUnitHead->nama ?? '.....................' }})</strong>
            </td>
            <td class="signature-box">
                Mengetahui,<br>
                Pengawas Grade Control
                <br><br>
                @if ($item['pengawasTtd'])
                    <img src="{{ $item['pengawasTtd'] }}" class="ttd-img"><br>
                @else
                    <br><br><br>
                @endif
                <strong>({{ $item['produksi']->userPegawai->nama ?? '.....................' }})</strong>
            </td>
            <td class="signature-box">
                Dibuat Oleh,<br>
                Grade Control Shift {{ $item['produksi']->shift }}
                <br><br>
                @if ($item['picTtd'])
                    <img src="{{ $item['picTtd'] }}" class="ttd-img"><br>
                @else
                    <br><br><br>
                @endif
                <strong>({{ $item['produksi']->pic1->nama ?? '.....................' }})</strong>
            </td>
        </tr>
    </table>

</div>
@endforeach

</body>
</html>