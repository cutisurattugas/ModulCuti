<table class="table table-bordered">
    <tr>
        <th width="1%">No</th>
        @if(!isset($hide_nama) || !$hide_nama)  <!-- Jika hide_nama = false, tampilkan kolom Nama -->
            <th>
                <center>Nama</center>
            </th>
        @endif
        <th>
            <center>Jumlah Cuti</center>
        </th>
        <th>
            <center>Rentang Cuti</center>
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
            @if(!isset($hide_nama) || !$hide_nama)  <!-- Jika hide_nama = false, tampilkan data Nama -->
                <td>
                    <center>
                        {{ $item->pegawai->gelar_dpn ?? '' }}{{ $item->pegawai->gelar_dpn ? ' ' : '' }}{{ $item->pegawai->nama }}{{ $item->pegawai->gelar_blk ? ', ' . $item->pegawai->gelar_blk : '' }}
                    </center>
                </td>
            @endif
            <td>
                <center>{{ $item->jumlah_cuti }} Hari</center>
            </td>
            <td>
                <center>{{ date('d M Y', strtotime($item->tanggal_mulai)) }} -
                    {{ date('d M Y', strtotime($item->tanggal_selesai)) }}</center>
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
                    <span class="badge rounded-pill bg-{{ $badgeClass }}"><a href="{{route('cuti.scan', $item->access_token)}}">{{ $status }}</a></span>
                </center>
            </td>
            <td>
                <center>
                    <a class="btn btn-info btn-sm" href="{{ route('cuti.show', $item->access_token) }}">
                        <i class="nav-icon fas fa-eye"></i>
                    </a>

                    @if (
                        (auth()->user()->role_aktif === 'admin' && in_array($item->status, ['Disetujui', 'Selesai'])) ||
                            (auth()->user()->username === $item->pegawai->username && $item->status === 'Selesai'))
                        <a class="btn btn-success btn-sm" href="{{ route('cuti.print', $item->access_token) }}">
                            <i class="nav-icon fas fa-print"></i>
                        </a>
                    @endif

                    @if (auth()->user()->role_aktif === 'admin' || auth()->user()->username === $item->pegawai->username)
                        <a class="btn btn-warning btn-sm" href="{{ route('cuti.edit', $item->access_token) }}">
                            <i class="nav-icon fas fa-edit"></i>
                        </a>
                    @endif
                </center>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ (isset($hide_nama) && $hide_nama) ? '6' : '7' }}" class="text-center">Belum ada data cuti</td>
        </tr>
    @endforelse
</table>