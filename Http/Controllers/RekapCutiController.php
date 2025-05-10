<?php

namespace Modules\Cuti\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cuti\Entities\Cuti;
use Modules\Pengaturan\Entities\Pegawai;

class RekapCutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $pegawaiList = Pegawai::all()->map(function ($pegawai) {
            // Hitung jumlah cuti per jenis
            $cutiCounts = Cuti::where('pegawai_id', $pegawai->id)
                ->whereIn('jenis_cuti_id', [1, 2, 3, 4])
                ->where('status', 'Selesai')
                ->selectRaw('jenis_cuti_id, count(*) as total')
                ->groupBy('jenis_cuti_id')
                ->pluck('total', 'jenis_cuti_id');

            // Assign ke property tambahan di model
            $pegawai->jumlah_cuti_1 = $cutiCounts[1] ?? 0;
            $pegawai->jumlah_cuti_2 = $cutiCounts[2] ?? 0;
            $pegawai->jumlah_cuti_3 = $cutiCounts[3] ?? 0;
            $pegawai->jumlah_cuti_4 = $cutiCounts[4] ?? 0;

            return $pegawai;
        });

        return view('cuti::rekap.index', compact('pegawaiList'));
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
        return view('cuti::show');
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
