@extends('adminlte::page')
@section('title', 'Jenis Cuti')
@section('content_header')
    <h1 class="m-0 text-dark"></h1>
@stop
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1>Jenis Cuti</h1>
                    <div class="lead">
                        Manaje jenis cuti.
                        <a href="{{ route('jenis_cuti.create') }}" class="btn btn-primary btn-sm float-right">Tambah Jenis
                            Cuti</a>
                    </div>

                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>

                    <table class="table table-bordered">
                        <tr>
                            <th width="1%">No</th>
                            <th>
                                <center>Nama Cuti</center>
                            </th>
                            <th>
                                <center>Jumlah Cuti</center>
                            </th>
                            <th>
                                <center>Deskripsi</center>
                            </th>
                            <th colspan="2">
                                <center>Opsi</center>
                            </th>
                        </tr>
                        @foreach ($jenis as $jenis)
                            <tr>
                                <td>
                                    <center>{{ $loop->iteration }}</center>
                                </td>
                                <td>
                                    <center>{{ $jenis->nama_cuti }}</center>
                                </td>
                                <td>
                                    <center>{{ $jenis->jumlah_cuti }}</center>
                                </td>
                                <td>
                                    <center>{{ Str::limit($jenis->deskripsi, 20, '...') }}
                                    </center>
                                </td>
                                <td>
                                    <center>
                                        <a class="btn btn-warning btn-sm" href="{{ route('jenis_cuti.edit', $jenis->id) }}">
                                            <i class="nav-icon fas fa-edit"></i>
                                        </a>

                                        <!-- Tambahkan di dalam loop untuk setiap data -->
                                        <form action="{{ route('jenis_cuti.destroy', $jenis->id) }}" method="POST"
                                            class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')

                                            <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                <i class="nav-icon fas fa-trash"></i>
                                            </button>
                                        </form>

                                    </center>
                                </td>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".delete-btn").forEach(button => {
                button.addEventListener("click", function(e) {
                    e.preventDefault(); // Cegah form terkirim langsung

                    let form = this.closest("form");

                    Swal.fire({
                        title: "Apakah Anda yakin?",
                        text: "Data yang dihapus tidak bisa dikembalikan!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Ya, hapus!",
                        cancelButtonText: "Batal"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit(); // Kirim form jika dikonfirmasi
                        }
                    });
                });
            });
        });
    </script>

@endsection
