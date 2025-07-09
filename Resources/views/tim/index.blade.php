@extends('adminlte::page')
@section('title', 'Tim')
@section('content_header')
<h1 class="m-0 text-dark"></h1>
@stop
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h1>Tim</h1>
                <div class="lead d-flex flex-wrap justify-content-start" style="gap: 5px;">
                    <a href="#" class="btn btn-primary btn-sm mb-2" data-toggle="modal"
                        data-target="#modalTambahTim">
                        <i class="nav-icon fas fa-plus"></i>
                        Tambah Tim Kerja
                    </a>
                    <a href="#" class="btn btn-primary btn-sm mb-2" data-toggle="modal"
                        data-target="#modalTambahAnggota">
                        <i class="nav-icon fas fa-user-plus"></i>
                        Tambah Anggota
                    </a>
                    <a href="#" class="btn btn-info btn-sm mb-2" data-toggle="modal"
                        data-target="#modalTambahTim">
                        <i class="nav-icon fas fa-user-plus"></i>
                        Tambah Anggota (Bulk)
                    </a>
                    <a href="#" class="btn btn-danger btn-sm mb-2" data-toggle="modal"
                        data-target="#modalTambahTim">
                        <i class="nav-icon fas fa-user-minus"></i>
                        Belum Punya Tim
                    </a>
                </div>
                <div class="mt-2">
                    @include('layouts.partials.messages')
                </div>

                <table class="table table-bordered">
                    <!-- Root Tim Kerja -->
                    <tr>
                        <th width="1%"><i class="nav-icon fas fa-folder-open"></i></th>
                        <th colspan="2">{{ $unitInduk->unit->nama ?? 'Politeknik Negeri Banyuwangi' }}</th>

                        <th>
                            <center>
                                <a class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditPegawai">
                                    <i class="nav-icon fas fa-edit"></i>
                                </a>
                                <form action="#" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-sm delete-btn">
                                        <i class="nav-icon fas fa-trash"></i>
                                    </button>
                                </form>
                            </center>
                        </th>
                    </tr>

                    <!-- Ketua Utama -->
                    @if ($ketuaUtama)
                    <tr>
                        <td></td>
                        <td width="1%">
                            <center><i class="nav-icon fas fa-user"></i></center>
                        </td>
                        <td colspan="2">
                            <span>
                                {{ $ketuaUtama->pegawai->nama }}  [Ketua]

                                {{-- Ikon Edit --}}
                                <a class="text-info ms-2" style="text-decoration: none;" data-toggle="modal" data-target="#modalEditPegawai" title="Edit">
                                    <i class="fas fa-star"></i>
                                </a>

                                {{-- Ikon Delete --}}
                                <form action="#" method="POST" class="d-inline delete-form ms-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-link text-danger p-0 m-0 align-baseline delete-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </span>
                            <br>
                            <small>
                                {{ $ketuaUtama->pegawai->nip }} |
                                {{ $ketuaUtama->jabatan->jabatan }} |
                                Sudah Buat SKP dengan Peran Ini
                            </small>
                        </td>
                    </tr>

                    @endif

                    <!-- Tim-Tim Kerja Anak -->
                    @foreach ($timKerja as $tim)
                    <tr>
                        <td></td>
                        <td width="1%">
                            <center><i class="nav-icon fas fa-folder"></i></center>
                        </td>
                        <td>
                            <a href="javascript:void(0);" class="toggle-child" data-id="{{ $tim->id }}">
                                {{ $tim->unit->nama }}
                            </a>
                            <div class="child-detail mt-2" id="child-{{ $tim->id }}">
                            </div>
                        </td>
                        <td>
                            <center>
                                <a class="btn btn-warning btn-sm" data-toggle="modal"
                                    data-target="#modalEditPegawai">
                                    <i class="nav-icon fas fa-edit"></i>
                                </a>
                                <form action="#" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm delete-btn">
                                        <i class="nav-icon fas fa-trash"></i>
                                    </button>
                                </form>
                            </center>
                        </td>
                    </tr>
                    @endforeach
                </table>

                <br>
                <div class="d-flex">
                    {{-- {!! $pegawai->links('pagination::bootstrap-4') !!} --}}
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Modal Tim Kerja --}}
@include('cuti::tim.components.modal-tim-kerja')
{{-- Modal Tambah Anggota --}}
@include('cuti::tim.components.modal-anggota')
@stop
@section('adminlte_js')

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.body.addEventListener('click', function(e) {
            // Mengecek apakah elemen yang diklik adalah link dengan class 'toggle-child'
            if (e.target.classList.contains('toggle-child')) {
                const id = e.target.dataset.id; // Mengambil ID dari data-id
                const containerId = 'child-container-' + id; // ID kontainer tempat child akan dimuat
                let container = document.getElementById(containerId);

                // Kalau belum ada, buat kontainernya
                if (!container) {
                    container = document.createElement('div');
                    container.id = containerId;
                    container.classList.add('ms-3', 'mt-2');
                    e.target.insertAdjacentElement('afterend', container);
                }

                // Toggle jika sudah dimuat
                if (container.dataset.loaded === "true") {
                    container.style.display = (container.style.display === 'none' || container.style
                        .display === '') ? 'block' : 'none';
                    return;
                }

                // Loading state
                container.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="spinner-border text-primary me-2" role="status"></div>
                            <span>Memuat data...</span>
                        </div>
                    `;

                // Fetch data anak dari API
                fetch(`/api/tim-kerja/${id}/children`)
                    .then(res => res.json())
                    .then(data => {
                        container.innerHTML = data.html;
                        container.style.display = 'block';
                        container.dataset.loaded = "true"; // Tandai kontainer sudah dimuat

                        // Pasang event listener pada elemen baru yang dimuat
                        attachToggleChildEventListeners();
                    })
                    .catch(err => {
                        container.innerHTML =
                            '<span class="text-danger">Gagal memuat data anak.</span>';
                    });
            }
        });
    });

    // Fungsi untuk menambahkan event listener pada elemen baru
    function attachToggleChildEventListeners() {
        // Memastikan event listener terpasang pada semua link dengan class 'toggle-child'
        document.querySelectorAll('.toggle-child').forEach(link => {
            link.addEventListener('click', function() {
                const id = this.dataset.id;
                const container = document.getElementById('child-container-' + id);

                if (container.style.display === 'block') {
                    container.style.display = 'none';
                    return;
                }

                if (container.dataset.loaded === "true") {
                    container.style.display = 'block';
                    return;
                }

                container.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="spinner-border text-primary me-2" role="status"></div>
                            <span>Memuat data...</span>
                        </div>
                    `;

                fetch(`/api/tim-kerja/${id}/children`)
                    .then(res => res.json())
                    .then(data => {
                        container.innerHTML = data.html;
                        container.style.display = 'block';
                        container.dataset.loaded = "true";

                        // Tunggu sebentar sebelum manipulasi lebih lanjut (opsional)
                        setTimeout(() => {
                            attachToggleChildEventListeners();
                        }, 10);
                    })
            });
        });
    }
</script>



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
