<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Cuti Pegawai - {{ $tahun }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 6px;
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        h2 {
            margin-bottom: 5px;
        }

        .small {
            font-size: 11px;
        }
    </style>
</head>

<body>
    <h2>Rekapitulasi Cuti Pegawai</h2>
    <p class="small">Tahun: {{ $tahun }}</p>

    @if (!empty($filterNama))
        <p class="small">Filter Nama: {{ $filterNama }}</p>
    @endif

    @if (!empty($tanggalAwal) && !empty($tanggalAkhir))
        <p class="small">Rentang Tanggal: {{ $tanggalAwal }} s.d. {{ $tanggalAkhir }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th class="text-left">Nama Pegawai</th>
                <th>Cuti Tahunan</th>
                <th>Cuti Alasan Penting</th>
                <th>Cuti Melahirkan</th>
                <th>Cuti Besar</th>
                <th>Sisa Cuti Tahunan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pegawaiList as $index => $pegawai)
                @php
                    $totalJatah = 12;
                    $cutiTahunan = $pegawai->jumlah_cuti_1;
                    $sisaCuti = $totalJatah - $cutiTahunan;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">
                        {{ $pegawai->gelar_dpn ?? '' }}{{ $pegawai->gelar_dpn ? ' ' : '' }}{{ $pegawai->nama }}{{ $pegawai->gelar_blk ? ', ' . $pegawai->gelar_blk : '' }}
                    </td>
                    <td>{{ $pegawai->jumlah_cuti_1 }}</td>
                    <td>{{ $pegawai->jumlah_cuti_2 }}</td>
                    <td>{{ $pegawai->jumlah_cuti_3 }}</td>
                    <td>{{ $pegawai->jumlah_cuti_4 }}</td>
                    <td>{{ $sisaCuti }} hari</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
