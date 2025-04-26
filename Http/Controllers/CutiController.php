<?php

namespace Modules\Cuti\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\JenisCuti;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\TimKerja;

class CutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $cuti = Cuti::all();
        return view('cuti::pengajuan_cuti.index', compact('cuti'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $jenis_cuti = JenisCuti::all();
        $pegawai = Pegawai::where('username', auth()->user()->username)->first();
        return view('cuti::pengajuan_cuti.create', compact('jenis_cuti', 'pegawai'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $pimpinan = TimKerja::where('id', '1')->first()->ketua_id;
        $pegawai = Pegawai::where('username', $request->pegawai)->first()->id;
        dd(
            [
                'jenis_cuti' => $request->jenis_cuti,
                'rentang_cuti' => $request->rentang_cuti,
                'keterangan' => $request->keterangan,
                'pimpinan' => $pimpinan,
                'pegawai' => $pegawai
            ]
        );
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
