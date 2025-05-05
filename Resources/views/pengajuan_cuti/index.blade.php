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
                        {{-- @if (auth()->user()->role_aktif === 'terdaftar' && auth()->user()->role_aktif === 'operator') --}}
                            <a href="{{ route('cuti.create') }}" class="btn btn-primary btn-sm float-right">Buat Pengajuan</a>
                        {{-- @endif --}}
                    </div>

                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>

                    <table class="table table-bordered">
                        <tr>
                            <th width="1%">No</th>
                            <th>
                                <center>Nama</center>
                            </th>
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
                            <th colspan="3">
                                <center>Opsi</center>
                            </th>
                        </tr>
                        @if ($cuti != null)
                            @foreach ($cuti as $item)
                                <tr>
                                    <td>
                                        <center>{{ $loop->iteration }}</center>
                                    </td>
                                    <td>
                                        <center>
                                            {{ $item->pegawai->gelar_dpn ?? '' }}{{ $item->pegawai->gelar_dpn ? ' ' : '' }}{{ $item->pegawai->nama }}{{ $item->pegawai->gelar_blk ? ', ' . $item->pegawai->gelar_blk : '' }}
                                        </center>
                                    </td>
                                    <td>
                                        <center>{{ date('d M Y', strtotime($item->tanggal_mulai)) }}
                                        </center>
                                    </td>
                                    <td>
                                        <center>{{ date('d M Y', strtotime($item->tanggal_selesai)) }}
                                        </center>
                                    </td>
                                    <td>
                                        <center>{{ $item->jenis_cuti->nama_cuti }}</center>
                                    </td>
                                    <td>
                                        <center>
                                            <span class="badge rounded-pill bg-info">
                                                {{ $item->status }}
                                            </span>
                                        </center>
                                    </td>
                                    <td>
                                        <center>
                                            <a class="btn btn-warning btn-sm" href="#">
                                                <i class="nav-icon fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-danger btn-sm" href="#">
                                                <i class="nav-icon fas fa-trash"></i>
                                            </a>
                                            <a class="btn btn-info btn-sm" href="{{ route('cuti.show', $item->id) }}">
                                                <i class="nav-icon fas fa-eye"></i>
                                            </a>
                                        </center>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center">Belum ada data cuti</td>
                            </tr>
                        @endif
                    </table>

                    <div class="d-flex">
                        {{-- {!! $cuti->links('pagination::bootstrap-4') !!} --}}
                    </div>

                </div>
            </div>
        </div>
    </div>
@stop
