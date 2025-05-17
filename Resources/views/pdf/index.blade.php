<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Permohonan Cuti</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 0.5in;
            color: #000;
            background: #f4f4f4;
            line-height: 1.2;
            font-size: 10pt;
            box-sizing: border-box;
        }

        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            /* Logo akan rata kiri */
        }

        .kop-surat img {
            width: 90px;
            height: auto;
            margin-right: 15px;
        }

        .kop-surat-text {
            text-align: center;
            font-size: 10pt;
        }

        h2 {
            font-size: 14pt;
            text-align: center;
            margin: 10px 0;
        }

        table.form {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10pt;
        }

        table.form td {
            padding: 3px 6px;
            vertical-align: top;
        }

        .container {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .table-cuti {
            width: 45%;
        }

        .signatures {
            width: 55%;
        }

        .ttd {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sign {
            text-align: center;
        }

        .catatan-kepegawaian {
            margin-top: 10px;
            font-size: 9pt;
        }

        /* Tombol Print hanya muncul di web */
        .web-only {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            display: inline-block;
            padding: 5px 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 9pt;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background-color: #0056b3;
            transform: scale(1.05);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .digital-stamp {
            display: flex;
            align-items: center;
            border: 1px solid #000;
            padding: 2px 4px;
            max-width: 260px;
            font-size: 7pt;
            line-height: 1.1;
            margin: 5px auto 0 auto;
            background-color: white;
        }

        .stamp-logo {
            flex-shrink: 0;
            margin-right: 6px;
        }

        .stamp-logo img {
            width: 28px;
            height: auto;
        }

        .stamp-text {
            flex-grow: 1;
        }

        .footer {
            margin-top: 30px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            font-size: 9pt;
            page-break-inside: avoid;
        }

        .signature-info {
            flex-grow: 1;
            margin-left: 10px;
        }

        /* Styling tambahan untuk Preview Web */
        @media screen {
            body {
                display: flex;
                justify-content: center;
                background-color: #f4f4f4;
            }

            .page-wrapper {
                width: 100%;
                max-width: 8.5in;
                background-color: white;
                padding: 20px;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
                border: 1px solid #ddd;
                margin-top: 30px;
                margin-bottom: 30px;
            }
        }

        /* Styling untuk Print */
        @media print {
            .web-only {
                display: none;
            }

            body {
                font-size: 10pt;
                margin: 0.5in;
                max-width: 8.5in;
                background-color: white;
            }

            .page-wrapper {
                box-shadow: none;
                padding: 0;
            }

            .signatures {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <!-- Tombol hanya muncul di browser -->
    <div class="page-wrapper">

        <!-- Tombol Print Preview -->
        <div class="web-only">
            <button class="btn-print" onclick="window.print()">🖨️ Tampilkan Print Preview</button>
        </div>

        <!-- Kop Surat -->
        <div class="kop-surat">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi">
            <div class="kop-surat-text">
                <strong>KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET DAN TEKNOLOGI</strong><br>
                <strong>POLITEKNIK NEGERI BANYUWANGI</strong><br>
                Jalan Raya Jember KM 13 Labanasem Kabat-Banyuwangi, 68461<br>
                Telp/Fax: (0333) 636780; E-mail: poliwangi@poliwangi.ac.id; Laman: poliwangi.ac.id
            </div>
        </div>

        <hr style="margin: 10px 0;">

        <h2>Surat Permohonan {{ $cuti->jenis_cuti->nama_cuti }}</h2>

        <p>Kepada Yth.<br>
            Direktur Politeknik Negeri Banyuwangi</p>

        <p>Yang bertanda tangan dibawah ini:</p>

        <table class="form">
            <tr>
                <td>Nama</td>
                <td>:
                    {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}
                </td>
            </tr>
            <tr>
                <td>NIP / NIK</td>
                <td>: {{ $cuti->pegawai->nip }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ $cuti->pegawai->id_staff }}</td>
            </tr>
            <tr>
                <td>Jenis Cuti</td>
                <td>: {{ $cuti->jenis_cuti->nama_cuti }}</td>
            </tr>
        </table>

        <p>Dengan ini mengajukan permohonan cuti berdasarkan: {{ $cuti->jenis_cuti->deskripsi }}, terhitung mulai
            tanggal <strong>{{ date('d M Y', strtotime($cuti->tanggal_mulai)) }}</strong> hingga tanggal
            <strong>{{ date('d M Y', strtotime($cuti->tanggal_selesai)) }}</strong>.
        </p>

        <p>Untuk keperluan: {{ $cuti->keterangan }}.</p>

        <p>Selama menjalankan izin, alamat saya adalah di: {{ $cuti->pegawai->kelurahan ?? '-' }}.
        </p>

        <p>Demikian permohonan ini saya buat untuk dipertimbangkan sebagaimana mestinya.</p>

        <!-- Container untuk Tabel Cuti dan Tanda Tangan -->
        <div class="container">
            <!-- Tabel Cuti -->
            <div class="table-cuti">
                <h4>Cuti yang sudah diambil:</h4>
                <table class="form">
                    <tr>
                        <td>Cuti tahunan</td>
                        <td>: {{ $cutiCounts[1] ?? 0 }} Hari</td>
                    </tr>
                    <tr>
                        <td>Cuti penting</td>
                        <td>: {{ $cutiCounts[2] ?? 0 }} Hari</td>
                    </tr>
                    <tr>
                        <td>Cuti lahiran</td>
                        <td>: {{ $cutiCounts[3] ?? 0 }} Hari</td>
                    </tr>
                    <tr>
                        <td>Cuti besar</td>
                        <td>: {{ $cutiCounts[4] ?? 0 }} Hari</td>
                    </tr>
                </table>

                <!-- Catatan kepegawaian langsung di bawah tabel cuti -->
                <div class="catatan-kepegawaian">
                    Catatan kepegawaian:<br>
                    {{ $cuti->catatan_kepegawaian }}
                    <hr>
                    2418111979031120212110021354
                </div>
            </div>

            <!-- Kolom Tanda Tangan Bertiga Vertikal -->
            <div class="signatures">
                <div class="ttd">
                    <div class="sign">
                        Banyuwangi, {{ date('d M Y', strtotime($cuti->created_at)) }}<br>
                        Pemohon,<br>
                        <div class="digital-stamp">
                            <div class="stamp-logo">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo Instansi">
                            </div>
                            <div class="stamp-text">
                                Ditandatangani secara elektronik oleh<br>
                                Direktur Politeknik Negeri Banyuwangi<br>
                                selaku Pejabat yang Berwenang
                                
                            </div>
                        </div>
                        {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}<br>
                        NIP/NIPPPK/NIK. {{ $cuti->pegawai->nip }}
                    </div>
                    <div class="sign">
                        Mengetahui atasan langsung,<br>
                        <div class="digital-stamp">
                            <div class="stamp-logo">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo Instansi">
                            </div>
                            <div class="stamp-text">
                                Ditandatangani secara elektronik oleh<br>
                                Direktur Politeknik Negeri Banyuwangi<br>
                                selaku Pejabat yang Berwenang
                                
                            </div>
                        </div>
                        {{ $atasan->pegawai->gelar_dpn ?? '' }}{{ $atasan->pegawai->gelar_dpn ? ' ' : '' }}{{ $atasan->pegawai->nama }}{{ $atasan->pegawai->gelar_blk ? ', ' . $atasan->pegawai->gelar_blk : '' }}<br>
                        NIP/NIPPPK/NIK. {{ $atasan->pegawai->nip }}
                    </div>
                    <div class="sign">
                        Pejabat yang berwenang,<br>
                        <div class="digital-stamp">
                            <div class="stamp-logo">
                                <img src="{{ asset('assets/img/logo.png') }}" alt="Logo Instansi">
                            </div>
                            <div class="stamp-text">
                                Ditandatangani secara elektronik oleh<br>
                                Direktur Politeknik Negeri Banyuwangi<br>
                                selaku Pejabat yang Berwenang
                                
                            </div>
                        </div>
                        {{ $pimpinan->pegawai->gelar_dpn ?? '' }}{{ $pimpinan->pegawai->gelar_dpn ? ' ' : '' }}{{ $pimpinan->pegawai->nama }}{{ $pimpinan->pegawai->gelar_blk ? ', ' . $pimpinan->pegawai->gelar_blk : '' }}<br>
                        NIP. {{ $pimpinan->pegawai->nip }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer dengan QR Code -->
        <div class="footer">
            <div style="display: flex; align-items: center;">
                <!-- Contoh QR Code Placeholder -->
                <img src="data:image/svg+xml;base64,{{ base64_encode($qrCodeImage) }}" alt="QR Code"
                    style="width: 50px; height: 50px;" />

                <div class="signature-info">
                    Surat ini sudah ditandatangani secara digital,<br>
                    sehingga tidak perlu tanda tangan basah dan stempel.
                </div>
            </div>
        </div>

    </div> <!-- Tutup .page-wrapper -->

</body>

</html>
