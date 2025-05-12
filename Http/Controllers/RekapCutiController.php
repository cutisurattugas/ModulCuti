<?php

namespace Modules\Cuti\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cuti\Entities\Cuti;
use Modules\Pengaturan\Entities\Pegawai;
use Illuminate\Support\Facades\DB;

class RekapCutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(Request $request)
    {
        $tahun = $request->input('tahun');

        // Ambil semua tahun unik dari tabel cuti (kolom tanggal_mulai)
        $daftarTahun = Cuti::selectRaw('YEAR(tanggal_mulai) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        // Jika tidak dipilih tahun, default ke tahun terbaru
        if (!$tahun) {
            $tahun = $daftarTahun->first();
        }

        $pegawaiList = Pegawai::orderBy('nama')->paginate(30);

        $cutiData = Cuti::whereIn('pegawai_id', $pegawaiList->pluck('id'))
            ->whereYear('tanggal_mulai', $tahun)
            ->whereIn('jenis_cuti_id', [1, 2, 3, 4])
            ->where('status', 'Selesai')
            ->selectRaw('pegawai_id, jenis_cuti_id, SUM(jumlah_cuti) as total')
            ->groupBy('pegawai_id', 'jenis_cuti_id')
            ->get()
            ->groupBy('pegawai_id');

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
}
