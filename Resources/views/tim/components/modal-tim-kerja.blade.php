<!-- Modal Tambah Tim Kerja -->
<div class="modal fade" id="modalTambahTim" tabindex="-1" role="dialog" aria-labelledby="modalTambahTimLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('tim.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Tim Kerja</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Parent Unit -->
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent</label>
                        <select class="form-control" name="parent_id" id="parent_id" class="form-select">
                            <option value="">-- Pilih Parent --</option>
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
                        <label>Nama Tim</label>
                        <select class="form-control" name="unit_id">
                            <option value="">- Pilih Unit -</option>
                            @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Ketua Tim -->
                    <div class="form-group">
                        <label>Ketua Tim</label>
                        <select name="ketua_id" class="form-control" required>
                            <option value="">- Pilih Ketua -</option>
                            @foreach ($pejabat as $p)
                                <option value="{{ $p->id }}|{{ $p->pegawai_id }}">
                                    {{ $p->pegawai->nama }}
                                </option>
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
