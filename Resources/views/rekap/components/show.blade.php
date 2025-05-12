<!-- Modal -->
<div class="modal fade" id="detailCutiModal-{{ $pegawai->id }}" tabindex="-1" role="dialog"
    aria-labelledby="detailCutiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Riwayat Cuti - {{ $pegawai->gelar_dpn ?? '' }}{{ $pegawai->gelar_dpn ? ' ' : '' }}{{ $pegawai->nama }}{{ $pegawai->gelar_blk ? ', ' . $pegawai->gelar_blk : '' }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if ($pegawai->cuti->count())
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Jenis Cuti</th>
                                <th>Jumlah Hari</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pegawai->cuti as $cuti)
                                <tr>
                                    <td>{{ date('d M Y', strtotime($cuti->tanggal_mulai)) }}</td>
                                    <td>{{ date('d M Y', strtotime($cuti->tanggal_selesai)) }}</td>
                                    <td>{{ $cuti->jenis_cuti->nama_cuti ?? '-' }}</td>
                                    <td>{{ $cuti->jumlah_cuti }}</td>
                                    <td>
                                        <center>
                                            @php
                                                $status = $cuti->status;
                                                switch ($status) {
                                                    case 'Diajukan':
                                                        $badgeClass = 'secondary';
                                                        break;
                                                    case 'Diproses':
                                                        $badgeClass = 'info';
                                                        break;
                                                    case 'Disetujui':
                                                        $badgeClass = 'primary';
                                                        break;
                                                    case 'Dibatalkan':
                                                        $badgeClass = 'danger';
                                                        break;
                                                    case 'Selesai':
                                                        $badgeClass = 'success';
                                                        break;
                                                    default:
                                                        $badgeClass = 'light';
                                                }
                                            @endphp
                                            <span
                                                class="badge rounded-pill bg-{{ $badgeClass }}">{{ $status }}</span>
                                        </center>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">Tidak ada data cuti.</p>
                @endif
            </div>
        </div>
    </div>
</div>
