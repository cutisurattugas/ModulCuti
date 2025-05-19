<?php

namespace Modules\Cuti\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Cuti\Entities\Cuti;
use Modules\Pengaturan\Entities\Pegawai;

class RekapCutiExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $tahun = $this->filters['tahun'] ?? null;
        $nama = $this->filters['nama'] ?? null;
        $tanggalAwal = $this->filters['tanggal_awal'] ?? null;
        $tanggalAkhir = $this->filters['tanggal_akhir'] ?? null;

        // Filter pegawai (berdasarkan nama)
        $pegawaiQuery = Pegawai::query();
        if ($nama) {
            $pegawaiQuery->where('nama', 'like', '%' . $nama . '%');
        }
        $pegawaiList = $pegawaiQuery->orderBy('nama')->get();

        // Filter cuti
        $cutiQuery = Cuti::whereIn('pegawai_id', $pegawaiList->pluck('id'))
            ->whereIn('jenis_cuti_id', [1, 2, 3, 4])
            ->where('status', 'Selesai');

        if ($tanggalAwal && $tanggalAkhir) {
            $cutiQuery->whereBetween('tanggal_mulai', [$tanggalAwal, $tanggalAkhir]);
        } elseif ($tahun) {
            $cutiQuery->whereYear('tanggal_mulai', $tahun);
        }

        $cutiData = $cutiQuery->selectRaw('pegawai_id, jenis_cuti_id, SUM(jumlah_cuti) as total')
            ->groupBy('pegawai_id', 'jenis_cuti_id')
            ->get()
            ->groupBy('pegawai_id');

        // Kompilasi data akhir
        $data = [];

        foreach ($pegawaiList as $pegawai) {
            $cutiCounts = $cutiData[$pegawai->id] ?? collect();

            $data[] = [
                'Nama'             => $pegawai->nama,
                'Cuti Tahunan'     => $cutiCounts->firstWhere('jenis_cuti_id', 1)['total'] ?? 0,
                'Cuti Sakit'       => $cutiCounts->firstWhere('jenis_cuti_id', 2)['total'] ?? 0,
                'Cuti Besar'       => $cutiCounts->firstWhere('jenis_cuti_id', 3)['total'] ?? 0,
                'Cuti Melahirkan'  => $cutiCounts->firstWhere('jenis_cuti_id', 4)['total'] ?? 0,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['Nama', 'Cuti Tahunan', 'Cuti Sakit', 'Cuti Besar', 'Cuti Melahirkan'];
    }
}
