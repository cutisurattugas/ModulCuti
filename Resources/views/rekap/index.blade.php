@extends('adminlte::page')
@section('title', 'Rekap Cuti')
@section('content_header')
    <h1 class="m-0 text-dark"></h1>
@stop
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1>Rekap Cuti</h1>

                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>
                    <form method="GET" action="{{ route('rekap.index') }}" class="form-inline mb-3">

                        <!-- Tahun -->
                        <label for="tahun" class="mr-2">Tahun:</label>
                        <select name="tahun" id="tahun" class="form-control mr-2">
                            @foreach ($daftarTahun as $thn)
                                <option value="{{ $thn }}" {{ $tahun == $thn ? 'selected' : '' }}>
                                    {{ $thn }}</option>
                            @endforeach
                        </select>

                        <!-- Nama Pegawai -->
                        <input type="text" name="nama" class="form-control mr-2" placeholder="Cari nama pegawai..."
                            value="{{ request('nama') }}">

                        <!-- Rentang Tanggal -->
                        <label class="mr-2">Dari:</label>
                        <input type="date" name="tanggal_awal" class="form-control mr-2"
                            value="{{ request('tanggal_awal') }}">
                        <label class="mr-2">Sampai:</label>
                        <input type="date" name="tanggal_akhir" class="form-control mr-2"
                            value="{{ request('tanggal_akhir') }}">

                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                    <!-- Tombol Print -->
                    <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#printModal">
                        <i class="fas fa-print"></i> Print
                    </button>

                    <table class="table table-bordered">
                        <tr>
                            <th width="1%">No</th>
                            <th>
                                <center>Nama Pegawai</center>
                            </th>
                            <th>
                                <center>Cuti Tahunan</center>
                            </th>
                            <th>
                                <center>Cuti Alasan Penting</center>
                            </th>
                            <th>
                                <center>Cuti Melahirkan</center>
                            </th>
                            <th>
                                <center>Cuti Besar</center>
                            </th>
                            <th>
                                <center>Sisa Cuti Tahunan</center>
                            </th>
                            <th>
                                <center>Aksi</center>
                            </th>
                        </tr>
                        @foreach ($pegawaiList as $pegawai)
                            <tr>
                                <td>
                                    <center>{{ $loop->iteration }}</center>
                                </td>
                                <td>{{ $pegawai->gelar_dpn ?? '' }}{{ $pegawai->gelar_dpn ? ' ' : '' }}{{ $pegawai->nama }}{{ $pegawai->gelar_blk ? ', ' . $pegawai->gelar_blk : '' }}
                                </td>
                                <td>{{ $pegawai->jumlah_cuti_1 }}</td>
                                <td>{{ $pegawai->jumlah_cuti_2 }}</td>
                                <td>{{ $pegawai->jumlah_cuti_3 }}</td>
                                <td>{{ $pegawai->jumlah_cuti_4 }}</td>
                                <td>
                                    @php
                                        $totalJatah = 12;
                                        $cutiTahunan = $pegawai->jumlah_cuti_1;
                                        $sisaCuti = $totalJatah - $cutiTahunan;
                                        $persen = ($cutiTahunan / $totalJatah) * 100;
                                    @endphp

                                    <div class="mb-1">{{ $sisaCuti }} hari</div>
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar 
                                            @if ($persen >= 90) bg-danger
                                            @elseif ($persen >= 70)
                                                bg-warning
                                            @else
                                                bg-success @endif
                                            "role="progressbar"
                                            style="width: {{ $persen }}%;" aria-valuenow="{{ $persen }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <!-- Tombol buka modal -->
                                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal"
                                        data-target="#detailCutiModal-{{ $pegawai->id }}">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>

                                    <!-- Include modal untuk pegawai ini -->
                                    @include('cuti::rekap.components.show', ['pegawai' => $pegawai])
                                </td>

                            </tr>
                        @endforeach
                    </table>
                    <br>
                    <div class="d-flex">
                        {{ $pegawaiList->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Pilih Format Print -->
    <div class="modal fade" id="printModal" tabindex="-1" role="dialog" aria-labelledby="printModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Format Print</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p>Silakan pilih format export data rekap cuti:</p>
                    <div class="d-flex justify-content-around">
                        <a href="{{ route('rekap.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="{{ route('rekap.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


@stop
@section('adminlte_js')

@stop
