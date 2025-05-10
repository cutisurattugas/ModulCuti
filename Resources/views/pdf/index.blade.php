<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Cuti - Versi Revisi</title>
  <style>
    body {
      font-family: "Times New Roman", serif;
      font-size: 12pt;
      margin: 2cm;
      color: #000;
    }

    h2, h3 {
      text-align: center;
      margin-bottom: 0;
    }

    .center {
      text-align: center;
    }

    .indent {
      padding-left: 40px;
      font-weight: bold;
    }

    .table-identitas {
      margin-top: 10px;
      margin-left: 40px;
      font-weight: bold;
    }

    .table-identitas td {
      padding: 4px;
      vertical-align: top;
    }

    .section {
      margin-top: 20px;
    }

    .ttd-layout {
      display: flex;
      justify-content: space-between;
      margin-top: 50px; /* Menurunkan posisi tanda tangan */
    }

    .ttd-kiri {
      width: 50%;
      margin-top: 275px; /* Menurunkan posisi tabel cuti dan catatan */
    }

    .ttd-kanan {
      width: 48%;
    }

    .signature-box {
      text-align: center;
      margin-bottom: 20px;
    }

    .qr-space {
      height: 70px;
      width: 70px;
      margin: 10px auto 0;
      border: 1px dashed #000;
    }

    .cuti-table {
      width: 100%;
      border: 1px solid #000;
      border-collapse: collapse;
      font-size: 10pt; /* Mengurangi ukuran font */
    }

    .cuti-table td {
      border: 1px solid #000;
      padding: 4px; /* Mengurangi padding */
    }

    .catatan-box {
      border: 1px solid #000;
      height: 60px; /* Menurunkan tinggi kotak catatan */
      margin-top: 20px; /* Menurunkan posisi kotak catatan */
    }

    .header-content {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
    }

    .header-content img {
      width: 150px; /* Memperbesar gambar */
      margin-right: 15px; /* Memberikan jarak antara gambar dan teks */
    }

  </style>
</head>
<body>

  <!-- Bagian header dengan gambar -->
  <div class="header-content">
    <img src="{{asset('assets/img/logo.png')}}" alt="Logo Instansi"> <!-- Ganti dengan path gambar logo -->
    <div>
      <h3>KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET DAN TEKNOLOGI</h3>
      <h2>POLITEKNIK NEGERI BANYUWANGI</h2>
      <p class="center">
        Jalan Raya Jember KM 13 Labanasem Kabat-Banyuwangi, 68461<br>
        Telp/Fax : (0333) 636780; E-m@il : poliwangi@poliwangi.ac.id; Laman : poliwangi.ac.id
      </p>
    </div>
  </div>

  <hr style="margin: 20px 0;">

  <h3 class="center"><u>Surat Permohonan {{$cuti->jenis_cuti->nama_cuti}}</u></h3>

  <p>Kepada Yth.<br>
  Direktur Politeknik Negeri Banyuwangi</p>

  <p>Yang bertanda tangan di bawah ini:</p>

  <table class="table-identitas">
    <tr><td style="width: 35%;">Nama</td><td>: {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}</td></tr>
    <tr><td>NIP / NIK</td><td>: {{$cuti->pegawai->nip}}</td></tr>
    <tr><td>Jabatan</td><td>: {{$cuti->pegawai->id_staff}}</td></tr>
    <tr><td>Jenis Cuti</td><td>: {{$cuti->jenis_cuti->nama_cuti}}</td></tr>
  </table>

  <div class="section">
    <p>
      Dengan ini mengajukan permohonan cuti berdasarkan: <br>
      {{$cuti->jenis_cuti->deskripsi}} terhitung mulai tanggal 
      <strong>{{ date('d M Y', strtotime($cuti->tanggal_mulai)) }}</strong> hingga tanggal <strong>{{ date('d M Y', strtotime($cuti->tanggal_selesai)) }}</strong>.
    </p>

    <p>Untuk keperluan: {{$cuti->keterangan}}</p>

    <p>
      Selama menjalankan izin, alamat saya adalah di: <br>
      Dsn. Kebonjeruk RT/RW: 01/02, Ds. Bojong Nangka, Kec. Poris. <br>
      Demikian permohonan ini saya buat untuk dipertimbangkan sebagaimana mestinya.
    </p>
  </div>

  <div class="ttd-layout">
    <!-- Kolom kiri -->
    <div class="ttd-kiri">
      <table class="cuti-table" style="font-size: 10pt; width: 90%;"> <!-- Mengurangi ukuran font -->
        <p style="margin-top: 15px;"><b>Cuti yang sudah diambil:</b></p>
        <tr><td>Cuti tahunan</td><td>: ___ Hari</td></tr>
        <tr><td>Cuti penting</td><td>: ___ Hari</td></tr>
        <tr><td>Cuti lahiran</td><td>: ___ Hari</td></tr>
        <tr><td>Cuti besar</td><td>: ___ Hari</td></tr>
      </table>

      <p style="margin-top: 15px;"><b>Catatan kepegawaian:</b></p>
      <div class="catatan-box" style="height: 50px; width: 90%;">
        <p style="margin-left: 10px">{{$cuti->catatan_kepegawaian}}</p>
      </div>
      <small style="margin-top: 10px;">2418111979031120212110021354</small>
    </div>

    <!-- Kolom kanan tanda tangan (termasuk pemohon) -->
    <div class="ttd-kanan">
      <div class="signature-box">
        Banyuwangi, {{ date('d M Y', strtotime($cuti->created_at)) }}<br>
        Pemohon,<br><br><br>
        <div class="qr-space"></div>
        {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}<br>
        NIP/NIPPPK/NIK. {{$cuti->pegawai->nip}}
      </div>

      <div class="signature-box">
        Mengetahui atasan langsung,<br><br><br>
        <div class="qr-space"></div>
        {{ $atasan->pegawai->gelar_dpn ?? '' }}{{ $atasan->pegawai->gelar_dpn ? ' ' : '' }}{{ $atasan->pegawai->nama }}{{ $atasan->pegawai->gelar_blk ? ', ' . $atasan->pegawai->gelar_blk : '' }}<br>
        NIP/NIPPPK/NIK. {{$atasan->pegawai->nip}}
      </div>

      <div class="signature-box">
        Pejabat yang berwenang,<br><br><br>
        <div class="qr-space"></div>
        {{ $pimpinan->pegawai->gelar_dpn ?? '' }}{{ $pimpinan->pegawai->gelar_dpn ? ' ' : '' }}{{ $pimpinan->pegawai->nama }}{{ $pimpinan->pegawai->gelar_blk ? ', ' . $pimpinan->pegawai->gelar_blk : '' }}<br>
        NIP. {{$pimpinan->pegawai->nip}}
      </div>
    </div>
  </div>

</body>
</html>
