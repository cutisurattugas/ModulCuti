<?php

namespace Modules\Cuti\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cuti\Entities\Cuti;
use Modules\Pengaturan\Entities\Pegawai;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Cuti\Exports\RekapCutiExport;

class RekapCutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(Request $request)
    {
        $tahun = $request->input('tahun');
        $nama = $request->input('nama');
        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');

        // Ambil semua tahun unik dari tabel cuti
        $daftarTahun = Cuti::selectRaw('YEAR(tanggal_mulai) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        // Jika tidak dipilih tahun, default ke tahun terbaru
        if (!$tahun) {
            $tahun = $daftarTahun->first();
        }

        // Query pegawai (dengan filter nama jika ada)
        $pegawaiQuery = Pegawai::query();
        if ($nama) {
            $pegawaiQuery->where('nama', 'like', '%' . $nama . '%');
        }

        $pegawaiList = $pegawaiQuery->orderBy('nama')->paginate(30);

        // Query cuti (dengan filter tahun & tanggal range jika ada)
        $cutiQuery = Cuti::whereIn('pegawai_id', $pegawaiList->pluck('id'))
            ->whereIn('jenis_cuti_id', [1, 2, 3, 4])
            ->where('status', 'Selesai');

        if ($tanggalAwal && $tanggalAkhir) {
            $cutiQuery->whereBetween('tanggal_mulai', [$tanggalAwal, $tanggalAkhir]);
        } else {
            // Default: filter berdasarkan tahun jika range tidak diisi
            $cutiQuery->whereYear('tanggal_mulai', $tahun);
        }

        $cutiData = $cutiQuery
            ->selectRaw('pegawai_id, jenis_cuti_id, SUM(jumlah_cuti) as total')
            ->groupBy('pegawai_id', 'jenis_cuti_id')
            ->get()
            ->groupBy('pegawai_id');

        // Gabungkan data cuti ke pegawai
        $pegawaiList->getCollection()->transform(function ($pegawai) use ($cutiData) {
            $cutiCounts = $cutiData[$pegawai->id] ?? collect();

            $pegawai->jumlah_cuti_1 = $cutiCounts->firstWhere('jenis_cuti_id', 1)['total'] ?? 0;
            $pegawai->jumlah_cuti_2 = $cutiCounts->firstWhere('jenis_cuti_id', 2)['total'] ?? 0;
            $pegawai->jumlah_cuti_3 = $cutiCounts->firstWhere('jenis_cuti_id', 3)['total'] ?? 0;
            $pegawai->jumlah_cuti_4 = $cutiCounts->firstWhere('jenis_cuti_id', 4)['total'] ?? 0;

            return $pegawai;
        });

        return view('cuti::rekap.index', compact('pegawaiList', 'tahun', 'daftarTahun'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('cuti::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $riwayatCuti = Cuti::where('pegawai_id', $id)
            ->with('jenisCuti') // Jika ada relasi jenis cuti
            ->orderByDesc('tanggal_mulai')
            ->get();

        // Return dengan data riwayat cuti
        return view('cuti::rekap.index', compact('riwayatCuti'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('cuti::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function exportPdf(Request $request)
    {
        $tahun = $request->input('tahun');
        $nama = $request->input('nama');
        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');

        // Ambil semua tahun unik
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

        return Pdf::loadView('cuti::pdf.rekap', [
            'pegawaiList' => $pegawaiList,
            'tahun' => $tahun,
            'filterNama' => $request->input('filter_nama'), // bisa null
            'tanggalAwal' => $request->input('tanggal_awal'), // bisa null
            'tanggalAkhir' => $request->input('tanggal_akhir'), // bisa null
        ])->download('rekap-cuti-' . $tahun . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only(['tahun', 'nama', 'tanggal_awal', 'tanggal_akhir']);
        return Excel::download(new RekapCutiExport($filters), 'rekap-cuti.xlsx');
    }
}
