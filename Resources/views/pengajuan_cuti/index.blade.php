@extends('adminlte::page')
@section('title', 'Cuti')
@section('content_header')
    <h1 class="m-0 text-dark"></h1>
@stop
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1>Cuti</h1>
                    <div class="lead">
                        Manaje pengajuan cuti.
                        <a href="#" class="btn btn-primary btn-sm float-right">Buat Pengajuan</a>
                    </div>

                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>

                    <table class="table table-bordered">
                        <tr>
                            <th width="1%">No</th>
                            <th>
                                <center>Tanggal Awal</center>
                            </th>
                            <th>
                                <center>Tanggal Selesai</center>
                            </th>
                            <th>
                                <center>Jenis</center>
                            </th>
                            <th>
                                <center>Status</center>
                            </th>
                            <th>
                                <center>Opsi</center>
                            </th>
                        </tr>
                        @foreach ($cuti as $cuti)
                        <tr>
                            <td>
                                <center>{{ $loop->iteration }}</center>
                            </td>
                            <td>
                                <center>{{$cuti->tanggal_mulai}}</center>
                            </td>
                            <td>
                                <center>{{$cuti->tanggal_selesai}}</center>
                            </td>
                            <td>
                                <center>{{$cuti->jenis_cuti->nama_cuti}}</center>
                            </td>
                            <td>
                                <center>
                                    <span class="badge rounded-pill bg-info">
                                        {{$cuti->status}}
                                    </span>
                                </center>
                            </td>
                            <td>
                                <center>
                                    <a class="btn btn-warning btn-sm" href="#">
                                        <i class="nav-icon fas fa-edit"></i>
                                    </a>
                                </center>
                            </td>
                        </tr>
                        @endforeach
                    </table>

                    <div class="d-flex">
                        {{-- {!! $cuti->links('pagination::bootstrap-4') !!} --}}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
