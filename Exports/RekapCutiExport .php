<?php
namespace Modules\Cuti\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Cuti\Entities\Cuti;
use Modules\Pengaturan\Entities\Pegawai;

class RekapCutiExport implements FromView
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $tahun = $this->request->input('tahun');
        $nama = $this->request->input('nama');
        $tanggalAwal = $this->request->input('tanggal_awal');
        $tanggalAkhir = $this->request->input('tanggal_akhir');

        $daftarTahun = Cuti::selectRaw('YEAR(tanggal_mulai) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        if (!$tahun) {
            $tahun = $daftarTahun->first();
        }

        $pegawaiQuery = Pegawai::query();

        if ($nama) {
            $pegawaiQuery->where('nama', 'LIKE', '%' . $nama . '%');
        }

        $pegawaiList = $pegawaiQuery->orderBy('nama')->get();

        $cutiQuery = Cuti::whereIn('pegawai_id', $pegawaiList->pluck('id'))
            ->whereIn('jenis_cuti_id', [1, 2, 3, 4])
            ->where('status', 'Selesai');

        if ($tanggalAwal && $tanggalAkhir) {
            $cutiQuery->whereBetween('tanggal_mulai', [$tanggalAwal, $tanggalAkhir]);
        } else {
            $cutiQuery->whereYear('tanggal_mulai', $tahun);
        }

        $cutiData = $cutiQuery->selectRaw('pegawai_id, jenis_cuti_id, SUM(jumlah_cuti) as total')
            ->groupBy('pegawai_id', 'jenis_cuti_id')
            ->get()
            ->groupBy('pegawai_id');

        $pegawaiList->transform(function ($pegawai) use ($cutiData) {
            $cutiCounts = $cutiData[$pegawai->id] ?? collect();

            $pegawai->jumlah_cuti_1 = $cutiCounts->firstWhere('jenis_cuti_id', 1)['total'] ?? 0;
            $pegawai->jumlah_cuti_2 = $cutiCounts->firstWhere('jenis_cuti_id', 2)['total'] ?? 0;
            $pegawai->jumlah_cuti_3 = $cutiCounts->firstWhere('jenis_cuti_id', 3)['total'] ?? 0;
            $pegawai->jumlah_cuti_4 = $cutiCounts->firstWhere('jenis_cuti_id', 4)['total'] ?? 0;

            return $pegawai;
        });

        return view('cuti::rekap.export_excel', [
            'pegawaiList' => $pegawaiList,
            'tahun' => $tahun,
        ]);
    }
}
