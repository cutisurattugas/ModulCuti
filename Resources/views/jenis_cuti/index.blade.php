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
                        <a href="#" class="btn btn-primary btn-sm float-right" data-toggle="modal"
                            data-target="#modalTambahJenisCuti">Tambah Jenis
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
                        @foreach ($jenis as $item)
                            <tr>
                                <td>
                                    <center>{{ $loop->iteration }}</center>
                                </td>
                                <td>
                                    {{ $item->nama_cuti }}
                                </td>
                                <td>
                                    <center>{{ $item->jumlah_cuti }} hari</center>
                                </td>
                                <td>
                                    <center>
                                        @if ($item->deskripsi != null)
                                            {{ Str::limit($item->deskripsi, 20, '...') }}
                                        @else
                                            <p>-</p>
                                        @endif
                                    </center>
                                </td>
                                <td>
                                    <center>
                                        <a class="btn btn-warning btn-sm" data-toggle="modal"
                                            data-target="#modalEditJenisCuti{{ $item->id }}">
                                            <i class="nav-icon fas fa-edit"></i>
                                        </a>

                                        <!-- Tambahkan di dalam loop untuk setiap data -->
                                        <form action="{{ route('jenis_cuti.destroy', $item->id) }}" method="POST"
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

                            <!-- Modal Edit Data-->
                            <div class="modal fade" id="modalEditJenisCuti{{ $item->id }}" tabindex="-1" role="dialog"
                                aria-labelledby="modalEditJenisCutiLabel{{ $item->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <form action="{{ route('jenis_cuti.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalEditJenisCutiLabel{{ $item->id }}">Edit
                                                    Jenis Cuti</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="nama">Nama Cuti</label>
                                                    <input type="text" name="nama_cuti" class="form-control"
                                                        value="{{ $item->nama_cuti }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="jumlah_cuti" class="form-label">Jumlah Cuti</label>
                                                    <input value="{{ $item->jumlah_cuti }}" type="number"
                                                        class="form-control" name="jumlah_cuti"
                                                        placeholder="Isikan batas hari cuti" min="1"
                                                        oninput="this.value = Math.abs(this.value)"
                                                        onkeydown="return event.key !== '-'" required>

                                                    @if ($errors->has('jumlah_cuti'))
                                                        <span
                                                            class="text-danger text-left">{{ $errors->first('jumlah_cuti') }}</span>
                                                    @endif
                                                </div>
                                                <div class="form-group">
                                                    <label for="deskripsi">Deskripsi</label>
                                                    <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="5">{{ $item->deskripsi }}</textarea>
                                                </div>


                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </table>

                    <div class="d-flex">
                        {{-- {!! $jenis->links('pagination::bootstrap-4') !!} --}}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Data --}}
    <div class="modal fade" id="modalTambahJenisCuti" tabindex="-1" role="dialog"
        aria-labelledby="modalTambahJenisCutiLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('jenis_cuti.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTambahJenisCutiLabel">Tambah Jenis Cuti</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nama_cuti" class="form-label">Nama Cuti</label>
                            <input value="{{ old('nama_cuti') }}" type="text" class="form-control" name="nama_cuti"
                                placeholder="Tuliskan nama cuti yang ingin ditambahkan" required>

                            @if ($errors->has('nama_cuti'))
                                <span class="text-danger text-left">{{ $errors->first('nama_cuti') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="jumlah_cuti" class="form-label">Jumlah Cuti</label>
                            <input value="{{ old('jumlah_cuti', 1) }}" type="number" class="form-control"
                                name="jumlah_cuti" placeholder="Isikan batas hari cuti" min="1"
                                oninput="this.value = Math.abs(this.value)" onkeydown="return event.key !== '-'" required>

                            @if ($errors->has('jumlah_cuti'))
                                <span class="text-danger text-left">{{ $errors->first('jumlah_cuti') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="nama">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" cols="30" rows="5"></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
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

@stop
