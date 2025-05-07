@extends('adminlte::page')
@section('title', 'Show Pengajuan Cuti')
@section('content_header')
    <h1 class="m-0 text-dark"></h1>
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1>Informasi Pegawai</h1>
                    <div class="row">
                        <!-- Identitas Pegawai -->
                        <div class="col-md-6">
                            <h6 style="color: grey">
                                <center>Identitas Pegawai</center>
                            </h6>
                            <table class="table">
                                <tr>
                                    <td>Nama</td>
                                    <td>:
                                        {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{ $cuti->pegawai->nip }}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{ $anggota->timKerja->unit->nama }}</td>
                                </tr>
                                <tr>
                                    <td>Sisa Cuti Tahunan</td>
                                    <td>:
                                        <span class="badge rounded-pill bg-warning">
                                            {{ $sisa_cuti }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!-- Identitas Atasan -->
                        <div class="col-md-6">
                            <h6 style="color: grey">
                                <center>Identitas Atasan</center>
                            </h6>
                            <table class="table">
                                <tr>
                                    <td>Nama</td>
                                    <td>:
                                        {{ $pejabat->pegawai->gelar_dpn ?? '' }}{{ $pejabat->pegawai->gelar_dpn ? ' ' : '' }}{{ $pejabat->pegawai->nama }}{{ $pejabat->pegawai->gelar_blk ? ', ' . $pejabat->pegawai->gelar_blk : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{ $pejabat->pegawai->nip }}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{ $ketua->unit->nama ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h1>Form Pengajuan Cuti</h1>
                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="jenis_cuti" class="form-label">Jenis Cuti</label>
                            <select class="form-control" name="jenis_cuti" id="jenis_cuti">
                                <option value="{{ $cuti->jenis_cuti->id }}">{{ $cuti->jenis_cuti->nama_cuti }}
                                </option>
                                @foreach ($jenis_cuti as $item)
                                    <option value="{{ $item->id }}">{{ $item->nama_cuti }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="rentang_cuti" class="form-label">Rentang Cuti</label>
                            <input type="text" class="form-control" name="rentang_cuti" id="rentang_cuti"
                                value="{{ $cuti->tanggal_mulai }} - {{ $cuti->tanggal_selesai }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="dok_pendukung" class="form-label">Dokumen Pendukung</label>
                        <br>
                        @if ($cuti->dok_pendukung != null)
                            <a href="{{ asset('storage/' . $cuti->dok_pendukung) }}" target="_blank"
                                class="btn btn-sm btn-info">Lihat Dokumen</a>
                        @elseif($cuti->dok_pendukung === null)
                            <div class="btn btn-sm btn-secondary" disabled>Lihat Dokumen</div>
                        @endif
                        <br>
                        <small>*Selain cuti tahunan wajib menyertakan dokumen pendukung!</small>
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="keterangan" cols="10" rows="">{{ $cuti->keterangan }}</textarea>
                    </div>
                    @if (auth()->user()->role_aktif === 'admin')
                        <div class="row mt-2">
                            <div class="col mb-2 me-2" style="max-width: 180px;">
                                <form action="{{ route('cuti.approve.unit', $cuti->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">Teruskan ke atasan</button>
                                </form>
                            </div>
                            <div class="col mb-2 me-2" style="max-width: 150px;">
                                <button type="button" class="btn btn-danger w-100">Batalkan</button>
                            </div>
                            <div class="col mb-2" style="max-width: 150px;">
                                <button class="btn btn-secondary w-100" onclick="history.back()">Kembali</button>
                            </div>
                        </div>
                    @elseif(auth()->user()->role_aktif === 'operator' && $id_pejabat_login != 1)
                        <div class="row mt-2">
                            <div class="col mb-2 me-2" style="max-width: 220px;">
                                <form action="{{ route('cuti.approve.atasan', $cuti->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">Teruskan ke pimpinan</button>
                                </form>
                            </div>
                            <div class="col mb-2 me-2" style="max-width: 150px;">
                                <button type="button" class="btn btn-danger w-100">Batalkan</button>
                            </div>
                            <div class="col mb-2" style="max-width: 150px;">
                                <button class="btn btn-secondary w-100" onclick="history.back()">Kembali</button>
                            </div>
                        </div>
                    @elseif(auth()->user()->role_aktif === 'operator' && $id_pejabat_login == 1)
                        <div class="row mt-2">
                            <div class="col mb-2 me-2" style="max-width: 100px;">
                                <form action="{{ route('cuti.approve.pimpinan', $cuti->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">Setujui</button>
                                </form>
                            </div>
                            <div class="col mb-2 me-2" style="max-width: 150px;">
                                <button type="button" class="btn btn-danger w-100">Batalkan</button>
                            </div>
                            <div class="col mb-2" style="max-width: 150px;">
                                <button class="btn btn-secondary w-100" onclick="history.back()">Kembali</button>
                            </div>
                        </div>
                    @elseif(auth()->user()->role_aktif === 'terdaftar')
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-danger">Batalkan</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script>
        new Litepicker({
            element: document.getElementById('rentang_cuti'),
            singleMode: false,
            format: 'YYYY-MM-DD',
        });
    </script>

@stop
