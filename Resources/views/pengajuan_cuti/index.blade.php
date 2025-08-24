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
                        Manajemen pengajuan cuti.
                        @if (auth()->user()->role_aktif !== 'admin' && auth()->user()->role_aktif !== 'direktur')
                            <a href="{{ route('cuti.create') }}" class="btn btn-primary btn-sm float-right">Buat Pengajuan</a>
                        @endif
                    </div>

                    <div class="mt-2">
                        @include('layouts.partials.messages')
                    </div>

                    <ul class="nav nav-tabs mb-3" id="cutiTab" role="tablist">
                        @if ($cuti_pribadi)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pribadi-tab" data-bs-toggle="tab"
                                    data-bs-target="#pribadi" type="button" role="tab">Cuti Pribadi</button>
                            </li>
                        @endif
                        @if ($cuti_anggota)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ !$cuti_pribadi ? 'active' : '' }}" id="anggota-tab"
                                    data-bs-toggle="tab" data-bs-target="#anggota" type="button" role="tab">Cuti
                                    Anggota</button>
                            </li>
                        @endif
                        @if ($cuti)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ !$cuti_pribadi && !$cuti_anggota ? 'active' : '' }}"
                                    id="semua-tab" data-bs-toggle="tab" data-bs-target="#semua" type="button"
                                    role="tab">Semua Cuti</button>
                            </li>
                        @endif
                    </ul>

                    <div class="tab-content" id="cutiTabContent">
                        @if ($cuti_pribadi)
                            <div class="tab-pane fade show active" id="pribadi" role="tabpanel">
                                @include('cuti::pengajuan_cuti.components.tabel', [
                                    'cuti_data' => $cuti_pribadi,
                                    'hide_nama' => true, // <-- Tambahkan parameter ini
                                ])
                            </div>
                        @endif

                        @if ($cuti_anggota)
                            <div class="tab-pane fade {{ !$cuti_pribadi ? 'show active' : '' }}" id="anggota"
                                role="tabpanel">
                                @include('cuti::pengajuan_cuti.components.tabel', [
                                    'cuti_data' => $cuti_anggota,
                                    'hide_nama' => false, // <-- Pastikan kolom nama tetap muncul
                                ])
                            </div>
                        @endif

                        @if ($cuti)
                            <div class="tab-pane fade {{ !$cuti_pribadi && !$cuti_anggota ? 'show active' : '' }}"
                                id="semua" role="tabpanel">
                                @include('cuti::pengajuan_cuti.components.tabel', [
                                    'cuti_data' => $cuti,
                                    'hide_nama' => false, // <-- Kolom nama tetap muncul
                                ])
                            </div>
                        @endif
                    </div>

                    <div class="d-flex">
                        {{-- {!! $cuti->links('pagination::bootstrap-4') !!} --}}
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="cutiIframeModal" tabindex="-1" aria-labelledby="cutiIframeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" style="height: 90%;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header">
                    <h5 class="modal-title" id="cutiIframeModalLabel">Detail Cuti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0" style="height: 100%;">
                    <iframe id="cuti-detail-iframe" src="" frameborder="0"
                        style="width:100%; height:100%;"></iframe>
                </div>
            </div>
        </div>
    </div>

@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.open-cuti-iframe').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.dataset.url;
                document.getElementById('cuti-detail-iframe').src = url;
                let modal = new bootstrap.Modal(document.getElementById('cutiIframeModal'));
                modal.show();
            });
        });
    </script>
@endsection
