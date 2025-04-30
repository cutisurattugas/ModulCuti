@extends('adminlte::page')
@section('title', 'Tambah Pengajuan Cuti')
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
                                    <td>: {{ $pegawai->gelar_dpn ?? '' }}{{ $pegawai->gelar_dpn ? ' ' : '' }}{{ $pegawai->nama }}{{ $pegawai->gelar_blk ? ', ' . $pegawai->gelar_blk : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{$pegawai->nip}}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{$tim->unit->nama}}</td>
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
                                    <td>: {{ $ketua->pegawai->gelar_dpn ?? '' }}{{ $ketua->pegawai->gelar_dpn ? ' ' : '' }}{{ $ketua->pegawai->nama }}{{ $ketua->pegawai->gelar_blk ? ', ' . $ketua->pegawai->gelar_blk : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{$ketua->nip}}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{$tim->unit->nama}}</td>
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
                    <form method="POST" action="{{ route('cuti.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <input type="hidden" name="pegawai_id" value="{{$pegawai->id}}">
                            <input type="hidden" name="pegawai_nip" value="{{$pegawai->nip}}">
                            <input type="hidden" name="atasan_id" value="{{$ketua->id}}">
                            <input type="hidden" name="atasan_nip" value="{{$ketua->nip}}">
                            <div class="col-md-6">
                                <label for="jenis_cuti" class="form-label">Jenis Cuti</label>
                                <select class="form-control" name="jenis_cuti" id="jenis_cuti">
                                    <option value="">-- Pilih Jenis Cuti --</option>
                                    @foreach ($jenis_cuti as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_cuti }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="rentang_cuti" class="form-label">Rentang Cuti</label>
                                <input type="text" class="form-control" name="rentang_cuti" id="rentang_cuti" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="dok_pendukung" class="form-label">Dokumen Pendukung</label>
                            <input type="file" class="form-control" name="dok_pendukung" id="dok_pendukung">
                            <small>*Selain cuti tahunan wajib menyertakan dokumen pendukung!</small>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="keterangan" cols="10" rows=""></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="#" class="btn btn-default">Kembali</a>
                    </form>
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
