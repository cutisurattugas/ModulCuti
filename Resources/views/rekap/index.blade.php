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

                    <table class="table table-bordered">
                        <tr>
                            <th width="1%">No</th>
                            <th>
                                <center>Tahun</center>
                            </th>
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
                        </tr>
                        @foreach ($pegawaiList as $pegawai)
                            <tr>
                                <td>
                                    <center>{{ $loop->iteration }}</center>
                                </td>
                                <td>{{ $pegawai->nama }}</td>
                                <td>{{ $pegawai->nama }}</td>
                                <td>{{ $pegawai->jumlah_cuti_1 }}</td>
                                <td>{{ $pegawai->jumlah_cuti_2 }}</td>
                                <td>{{ $pegawai->jumlah_cuti_3 }}</td>
                                <td>{{ $pegawai->jumlah_cuti_4 }}</td>
                                <td>{{ 12 - $pegawai->jumlah_cuti_1 }}</td>
                            </tr>
                        @endforeach
                    </table>

                    <div class="d-flex">
                        {{-- {!! $jenis->links('pagination::bootstrap-4') !!} --}}
                    </div>

                </div>
            </div>
        </div>
    </div>
@stop
@section('adminlte_js')

@stop
