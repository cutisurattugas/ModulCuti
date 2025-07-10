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
use Modules\Cuti\Services\SisaCutiService;
use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\TimKerja;

class RekapCutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(Request $request)
    {
        $user = auth()->user();
        $tahun = $request->input('tahun');
        $nama = $request->input('nama');
        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');

        $daftarTahun = Cuti::selectRaw('YEAR(tanggal_mulai) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        if (!$tahun) {
            $tahun = $daftarTahun->first();
        }

        // Mulai query pegawai
        $pegawaiQuery = Pegawai::query();

        // Jika login sebagai kajur
        if ($user->role_aktif === 'kajur') {
            // Ambil pegawai login
            $pegawaiLogin = Pegawai::where('username', $user->username)->first();

            if ($pegawaiLogin) {
                // Ambil tim yang diketuai oleh pegawai login
                $timYangDiketuai = TimKerja::whereHas('ketua', function ($q) use ($pegawaiLogin) {
                    $q->where('pegawai_id', $pegawaiLogin->id);
                })->pluck('id');

                // Ambil pegawai yang menjadi anggota dari tim-tim tersebut
                $pegawaiIds = Anggota::whereIn('tim_kerja_id', $timYangDiketuai)
                    ->pluck('pegawai_id')
                    ->unique();

                $pegawaiQuery->whereIn('id', $pegawaiIds);
            } else {
                // Kalau pegawai login tidak ditemukan, jangan tampilkan data
                $pegawaiQuery->whereRaw('0 = 1');
            }
        }

        // Filter nama jika ada
        if ($nama) {
            $pegawaiQuery->where('nama', 'like', '%' . $nama . '%');
        }

        $pegawaiList = $pegawaiQuery->orderBy('nama')->paginate(30);

        // Query cuti
        $cutiQuery = Cuti::whereIn('pegawai_id', $pegawaiList->pluck('id'))
            ->whereIn('jenis_cuti_id', [1, 2, 3, 4])
            ->where('status', 'Selesai');

        if ($tanggalAwal && $tanggalAkhir) {
            $cutiQuery->whereBetween('tanggal_mulai', [$tanggalAwal, $tanggalAkhir]);
        } else {
            $cutiQuery->whereYear('tanggal_mulai', $tahun);
        }

        $cutiData = $cutiQuery
            ->selectRaw('pegawai_id, jenis_cuti_id, SUM(jumlah_cuti) as total')
            ->groupBy('pegawai_id', 'jenis_cuti_id')
            ->get()
            ->groupBy('pegawai_id');

        // Gabungkan cuti ke data pegawai
        $pegawaiList->getCollection()->transform(function ($pegawai) use ($cutiData) {
            $cutiCounts = $cutiData[$pegawai->id] ?? collect();

            $pegawai->jumlah_cuti_1 = $cutiCounts->firstWhere('jenis_cuti_id', 1)['total'] ?? 0;
            $pegawai->jumlah_cuti_2 = $cutiCounts->firstWhere('jenis_cuti_id', 2)['total'] ?? 0;
            $pegawai->jumlah_cuti_3 = $cutiCounts->firstWhere('jenis_cuti_id', 3)['total'] ?? 0;
            $pegawai->jumlah_cuti_4 = $cutiCounts->firstWhere('jenis_cuti_id', 4)['total'] ?? 0;

            // Hitung sisa cuti
            $sisaCutiService = new SisaCutiService();
            $sisa = $sisaCutiService->hitungSisaCuti($pegawai->id);
            $pegawai->sisa_cuti = $sisa['sisa']; // tambahkan ini ke objek pegawai

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
