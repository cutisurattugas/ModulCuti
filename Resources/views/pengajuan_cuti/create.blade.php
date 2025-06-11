@extends('adminlte::page')
@section('title', 'Tambah Pengajuan Cuti')
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css ">
@endsection
@section('content_header')
    <h1 class="m-0 text-dark"></h1>
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h1>Informasi Pegawai</h1>
                    <div class="row">
                        <!-- Identitas Pegawai -->
                        <div class="col-md-6">
                            <h6 style="color: grey">
                                <center>Identitas Pegawai</center>
                            </h6>
                            <table class="table">
                                <tr>
                                    <td>Nama</td>
                                    <td>:
                                        {{ $pegawai->gelar_dpn ?? '' }}{{ $pegawai->gelar_dpn ? ' ' : '' }}{{ $pegawai->nama }}{{ $pegawai->gelar_blk ? ', ' . $pegawai->gelar_blk : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{ $pegawai->nip }}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{ $tim->unit->nama }}</td>
                                </tr>
                                <tr>
                                    <td>Sisa Cuti Tahunan</td>
                                    <td>:
                                        <span class="badge rounded-pill bg-warning">
                                            {{ $sisa_cuti }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!-- Identitas Atasan -->
                        <div class="col-md-6">
                            <h6 style="color: grey">
                                <center>Identitas Atasan</center>
                            </h6>
                            <table class="table">
                                <tr>
                                    <td>Nama</td>
                                    <td>:
                                        {{ $ketua->pegawai->gelar_dpn ?? '' }}{{ $ketua->pegawai->gelar_dpn ? ' ' : '' }}{{ $ketua->pegawai->nama }}{{ $ketua->pegawai->gelar_blk ? ', ' . $ketua->pegawai->gelar_blk : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>NIP/NIPPK</td>
                                    <td>: {{ $ketua->pegawai->nip }}</td>
                                </tr>
                                <tr>
                                    <td>Unit Kerja</td>
                                    <td>: {{ $ketua->unit->nama ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h1>Form Pengajuan Cuti</h1>
                    <div class="mt-2">
                        @include('layouts.partials.messages')
                        @if (session('error'))
                            <div class="alert alert-warning" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('cuti.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-3">
                            <input type="hidden" name="pegawai_id" value="{{ $pegawai->id }}">
                            <input type="hidden" name="pejabat_id" value="{{ $ketua->id }}">
                            <input type="hidden" name="tim_kerja_id" value="{{ $tim->id }}">
                            <div class="col-md-6">
                                <label for="jenis_cuti" class="form-label">Jenis Cuti</label>
                                <select class="form-control" name="jenis_cuti" id="jenis_cuti">
                                    <option value="">-- Pilih Jenis Cuti --</option>
                                    @foreach ($jenis_cuti as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_cuti }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="rentang_cuti" class="form-label">Rentang Cuti</label>
                                <input type="text" class="form-control" name="rentang_cuti" id="rentang_cuti" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="dok_pendukung" class="form-label">Dokumen Pendukung</label>
                            <input type="file" class="form-control" name="dok_pendukung" id="dok_pendukung"
                                accept=".pdf, image/*">
                            <small>*Selain cuti tahunan wajib menyertakan dokumen pendukung!</small>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="keterangan" cols="10" rows="" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('cuti.index') }}" class="btn btn-default">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sisaCuti = {{ $sisa_cuti }};
            let startDate = null;
            
            const fp = flatpickr('#rentang_cuti', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                minDate: "{{ now()->format('Y-m-d') }}",
                allowInput: false,
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 1) {
                        // Tanggal awal dipilih, set max date berdasarkan sisa cuti
                        startDate = selectedDates[0];
                        const maxDate = calculateMaxDate(startDate, sisaCuti);
                        instance.set('maxDate', maxDate);
                    } else if (selectedDates.length === 2) {
                        // Validasi total hari kerja
                        const workingDays = countWorkingDays(selectedDates[0], selectedDates[1]);
                        
                        if (workingDays > sisaCuti) {
                            alert(`Anda hanya memiliki ${sisaCuti} hari cuti tersedia. Rentang yang dipilih mencakup ${workingDays} hari kerja.`);
                            instance.clear();
                            startDate = null;
                        }
                    }
                },
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 1) {
                        // Jika hanya memilih 1 tanggal, biarkan calendar tetap terbuka
                        instance.open();
                    }
                }
            });
            
            // Fungsi menghitung max date berdasarkan tanggal awal dan sisa cuti
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
            
            // Fungsi menghitung hari kerja dalam rentang
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
