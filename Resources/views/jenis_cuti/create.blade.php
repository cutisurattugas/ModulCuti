@extends('adminlte::page')
@section('title', 'Tambah Jenis Cuti')
@section('content_header')
    <h1 class="m-0 text-dark"></h1>
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1>Tambah Jenis Cuti</h1>
                    <form method="POST" action="{{ route('jenis_cuti.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="nama_cuti" class="form-label">Nama Cuti</label>
                            <input value="{{ old('nama_cuti') }}" type="text" class="form-control" name="nama_cuti"
                                placeholder="Tuliskan nama cuti yang ingin ditambahkan" required>

                            @if ($errors->has('nama_cuti'))
                                <span class="text-danger text-left">{{ $errors->first('nama_cuti') }}</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="jumlah_cuti" class="form-label">Jumlah Cuti</label>
                            <input value="{{ old('jumlah_cuti') }}" type="number" class="form-control" name="jumlah_cuti"
                                placeholder="Isikan batas dari cuti" required>

                            @if ($errors->has('jumlah_cuti'))
                                <span class="text-danger text-left">{{ $errors->first('jumlah_cuti') }}</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi Cuti</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="10"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('jenis_cuti.index') }}" class="btn btn-default">Kembali</a>
                    </form>
                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop