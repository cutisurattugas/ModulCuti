<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Progres Pengajuan Cuti</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 850px;
            margin: auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .info-section table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-section td {
            padding: 8px;
            font-size: 14px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 30px;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #e0e0e0;
            z-index: 0;
        }

        .step {
            width: 20%;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .circle {
            width: 40px;
            height: 40px;
            margin: auto;
            border-radius: 50%;
            background-color: #ccc;
            color: white;
            line-height: 40px;
            font-weight: bold;
        }

        .step.active .circle {
            background-color: #007bff;
        }

        .step.complete .circle {
            background-color: #28a745;
        }

        .step.cancelled .circle {
            background-color: #dc3545;
            /* merah untuk dibatalkan */
        }

        .step p {
            margin-top: 10px;
            font-size: 13px;
        }

        .status-label {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
        }

        .status-label span {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Progres Pengajuan Cuti</h2>

        <div class="info-section">
            <table>
                <tr>
                    <td><strong>Nama</strong></td>
                    <td>: {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}</td>
                </tr>
                <tr>
                    <td><strong>NIP</strong></td>
                    <td>: {{ $cuti->pegawai->nip }}</td>
                </tr>
                <tr>
                    <td><strong>Jenis Cuti</strong></td>
                    <td>: {{ $cuti->jenis_cuti->nama_cuti }}</td>
                </tr>
                <tr>
                    <td><strong>Tanggal Cuti</strong></td>
                    <td>: {{ date('d M Y', strtotime($cuti->tanggal_mulai)) }} s.d
                        {{ date('d M Y', strtotime($cuti->tanggal_selesai)) }}</td>
                </tr>
                <tr>
                    <td><strong>Status Saat Ini</strong></td>
                    <td>: <strong style="color: #007bff;">{{ ucfirst($cuti->status) }}</strong></td>
                </tr>
            </table>
        </div>

        @php
            $status = strtolower($cuti->status);
        @endphp

        <div class="progress-steps">
            @if ($status === 'dibatalkan')
                <div class="step complete">
                    <div class="circle">1</div>
                    <p>Diajukan</p>
                </div>
                <div class="step cancelled active">
                    <div class="circle">2</div>
                    <p>Dibatalkan</p>
                </div>
            @else
                @php
                    $steps = ['Diajukan', 'Diproses', 'Disetujui', 'Selesai'];
                    $currentIndex = array_search(ucfirst($cuti->status), $steps);
                @endphp

                @foreach ($steps as $index => $step)
                    @php
                        $stepClass = '';
                        if ($index < $currentIndex) {
                            $stepClass = 'complete';
                        } elseif ($index === $currentIndex) {
                            $stepClass = 'active';
                        }
                    @endphp
                    <div class="step {{ $stepClass }}">
                        <div class="circle">{{ $index + 1 }}</div>
                        <p>{{ $step }}</p>
                    </div>
                @endforeach
            @endif
        </div>


        <div class="status-label">
            Status saat ini: <span>{{ ucfirst($cuti->status) }}</span>
        </div>
    </div>

</body>

</html>
