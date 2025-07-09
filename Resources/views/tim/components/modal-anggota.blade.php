<div class="modal fade" id="modalTambahAnggota" tabindex="-1" role="dialog" aria-labelledby="modalTambahAnggotaLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('anggota.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Anggota</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Parent Unit -->
                    <div class="mb-3">
                        <label for="tim_kerja_id" class="form-label">Tim Kerja</label>
                        <select class="form-control" name="tim_kerja_id" id="tim_kerja_id" class="form-select">
                            <option value="">-- Pilih Tim Kerja --</option>
                            <!-- Opsional kalau root tetap ditampilkan -->
                            @foreach ($allTimKerja as $tim)
                            <option value="{{ $tim->id }}">
                                {{ $tim->unit->nama ?? 'Politeknik Negeri Banyuwangi' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Tim -->
                    <div class="form-group">
                        <label>Pegawai</label>
                        <select class="form-control" name="pegawai_id">
                            <option value="">- Pilih Pegawai -</option>
                            @foreach ($pegawai as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
