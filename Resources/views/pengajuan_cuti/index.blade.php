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
                        @if (auth()->user()->role_aktif === 'terdaftar' || auth()->user()->role_aktif === 'operator')
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
                                ])
                            </div>
                        @endif

                        @if ($cuti_anggota)
                            <div class="tab-pane fade {{ !$cuti_pribadi ? 'show active' : '' }}" id="anggota"
                                role="tabpanel">
                                @include('cuti::pengajuan_cuti.components.tabel', [
                                    'cuti_data' => $cuti_anggota,
                                ])
                            </div>
                        @endif

                        @if ($cuti)
                            <div class="tab-pane fade {{ !$cuti_pribadi && !$cuti_anggota ? 'show active' : '' }}"
                                id="semua" role="tabpanel">
                                @include('cuti::pengajuan_cuti.components.tabel', ['cuti_data' => $cuti])
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
@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection
