<table class="table table-bordered">
    <tr>
        <th width="1%">No</th>
        <th>
            <center>Nama</center>
        </th>
        <th>
            <center>Tanggal Awal</center>
        </th>
        <th>
            <center>Tanggal Selesai</center>
        </th>
        <th>
            <center>Jenis</center>
        </th>
        <th>
            <center>Status</center>
        </th>
        <th colspan="3">
            <center>Opsi</center>
        </th>
    </tr>
    @forelse ($cuti_data as $item)
        <tr>
            <td>
                <center>{{ $loop->iteration }}</center>
            </td>
            <td>
                <center>
                    {{ $item->pegawai->gelar_dpn ?? '' }}{{ $item->pegawai->gelar_dpn ? ' ' : '' }}{{ $item->pegawai->nama }}{{ $item->pegawai->gelar_blk ? ', ' . $item->pegawai->gelar_blk : '' }}
                </center>
            </td>
            <td>
                <center>{{ date('d M Y', strtotime($item->tanggal_mulai)) }}</center>
            </td>
            <td>
                <center>{{ date('d M Y', strtotime($item->tanggal_selesai)) }}</center>
            </td>
            <td>
                <center>{{ $item->jenis_cuti->nama_cuti }}</center>
            </td>
            <td>
                <center>
                    @php
                        $status = $item->status;
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
                            case 'Dibatalkan':
                                $badgeClass = 'danger';
                                break;
                            case 'Selesai':
                                $badgeClass = 'secondary';
                                break;
                            default:
                                $badgeClass = 'light';
                        }
                    @endphp
                    <span class="badge rounded-pill bg-{{ $badgeClass }}">{{ $status }}</span>
                </center>
            </td>

            <td>
                <center>
                    <a class="btn btn-info btn-sm" href="{{ route('cuti.show', $item->id) }}">
                        <i class="nav-icon fas fa-eye"></i>
                    </a>

                    @if (auth()->user()->role_aktif === 'admin' || auth()->user()->username === $item->pegawai->username)
                        <a class="btn btn-secondary btn-sm" href="{{ route('cuti.print', $item->id) }}">
                            <i class="nav-icon fas fa-print"></i>
                        </a>
                    @endif

                    @if (auth()->user()->role_aktif === 'admin' || auth()->user()->username === $item->pegawai->username)
                        <a class="btn btn-warning btn-sm" href="{{ route('cuti.edit', $item->id) }}">
                            <i class="nav-icon fas fa-edit"></i>
                        </a>
                    @endif
                </center>
            </td>

        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center">Belum ada data cuti</td>
        </tr>
    @endforelse
</table>
