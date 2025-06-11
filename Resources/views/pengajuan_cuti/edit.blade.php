@extends('adminlte::page')
@section('title', 'Edit Pengajuan Cuti')
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css ">
@endsection
@section('content_header')
    <h1>Edit Pengajuan Cuti</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Informasi Pegawai --}}
            <div class="card">
                <div class="card-body">
                    <h5>Informasi Pegawai</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td>Nama</td>
                                    <td>: {{ $cuti->pegawai->gelar_dpn ?? '' }}{{ $cuti->pegawai->gelar_dpn ? ' ' : '' }}{{ $cuti->pegawai->nama }}{{ $cuti->pegawai->gelar_blk ? ', ' . $cuti->pegawai->gelar_blk : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{ $cuti->pegawai->nip }}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{ $anggota->timKerja->unit->nama }}</td>
                                </tr>
                                <tr>
                                    <td>Sisa Cuti Tahunan</td>
                                    <td>: <span class="badge rounded-pill bg-warning">{{ $sisa_cuti }}</span></td>
                                </tr>
                                <tr>
                                    <td>Status Pengajuan</td>
                                    <td>:
                                        @php
                                            $status = $cuti->status;
                                            switch ($status) {
                                                case 'Diajukan':
                                                    $badgeClass = 'primary';
                                                    break;
                                                case 'Diproses':
                                                    $badgeClass = 'info';
                                                    break;
                                                case 'Disetujui':
                                                    $badgeClass = 'success';
                                                    break;
                                                case 'Ditolak':
                                                    $badgeClass = 'danger';
                                                    break;
                                                case 'Dibatalkan':
                                                    $badgeClass = 'secondary';
                                                    break;
                                                default:
                                                    $badgeClass = 'light';
                                            }
                                        @endphp
                                        <span class="badge rounded-pill bg-{{ $badgeClass }}">{{ $status }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td>Nama Atasan</td>
                                    <td>: {{ $pejabat->pegawai->gelar_dpn ?? '' }}{{ $pejabat->pegawai->gelar_dpn ? ' ' : '' }}{{ $pejabat->pegawai->nama }}{{ $pejabat->pegawai->gelar_blk ? ', ' . $pejabat->pegawai->gelar_blk : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{ $pejabat->pegawai->nip }}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{ $pejabat->unit->nama ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Edit Pengajuan Cuti --}}
            <div class="card">
                <div class="card-body">
                    <h5>Form Edit Pengajuan Cuti</h5>
                    <div class="mt-2">
                        @include('layouts.partials.messages')
                        @if (session('error'))
                            <div class="alert alert-warning" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>

                    <form action="{{ route('cuti.update', $cuti->access_token) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="jenis_cuti" class="form-label">Jenis Cuti</label>
                                <select class="form-control" name="jenis_cuti" id="jenis_cuti" required>
                                    @foreach ($jenis_cuti as $item)
                                        <option value="{{ $item->id }}" {{ $item->id == $cuti->jenis_cuti_id ? 'selected' : '' }}>
                                            {{ $item->nama_cuti }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="rentang_cuti" class="form-label">Rentang Cuti </label>
                                <input type="text" class="form-control" name="rentang_cuti" id="rentang_cuti"
                                       value="{{ $cuti->tanggal_mulai }} to {{ $cuti->tanggal_selesai }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="dok_pendukung" class="form-label">Dokumen Pendukung</label><br>
                            @if ($cuti->dok_pendukung)
                                <a href="{{ asset('storage/' . $cuti->dok_pendukung) }}" target="_blank" class="btn btn-sm btn-info mb-2">Lihat Dokumen</a><br>
                            @endif
                            <input type="file" class="form-control" name="dok_pendukung" id="dok_pendukung">
                            <small>* Selain cuti tahunan wajib menyertakan dokumen pendukung!</small>
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="keterangan" cols="30" rows="3">{{ old('keterangan', $cuti->keterangan) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{route('cuti.index')}}" class="btn btn-default">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sisaCuti = {{ $sisa_cuti }};
            let startDate = null;

            const existingRange = "{{ $cuti->tanggal_mulai }} to {{ $cuti->tanggal_selesai }}";

            const fp = flatpickr('#rentang_cuti', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                allowInput: false,
                minDate: "{{ now()->format('Y-m-d') }}",
                defaultDate: existingRange.split(" to "),
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 1) {
                        startDate = selectedDates[0];
                        const maxDate = calculateMaxDate(startDate, sisaCuti);
                        instance.set('maxDate', maxDate);
                    } else if (selectedDates.length === 2) {
                        const workingDays = countWorkingDays(selectedDates[0], selectedDates[1]);

                        if (workingDays > sisaCuti) {
                            alert(`Anda hanya memiliki ${sisaCuti} hari cuti tersedia. Rentang yang dipilih mencakup ${workingDays} hari kerja.`);
                            instance.clear();
                            startDate = null;
                        }
                    }
                },
                onClose: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 1) {
                        instance.open(); // biarkan terbuka jika baru pilih 1 tanggal
                    }
                }
            });

            function calculateMaxDate(startDate, days) {
                let count = 0;
                let currentDate = new Date(startDate);
                let resultDate = new Date(startDate);

                while (count < days) {
                    currentDate.setDate(currentDate.getDate() + 1);
                    if (currentDate.getDay() !== 0 && currentDate.getDay() !== 6) {
                        count++;
                    }
                    resultDate = new Date(currentDate);
                }

                return resultDate;
            }

            function countWorkingDays(start, end) {
                let count = 0;
                let current = new Date(start);

                while (current <= end) {
                    if (current.getDay() !== 0 && current.getDay() !== 6) {
                        count++;
                    }
                    current.setDate(current.getDate() + 1);
                }

                return count;
            }
        });
    </script>
@stop

