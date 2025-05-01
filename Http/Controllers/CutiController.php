<?php

namespace Modules\Cuti\Http\Controllers;

use App\Models\Core\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\JenisCuti;
use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\Pejabat;
use Modules\Pengaturan\Entities\TimKerja;

class CutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $role = auth()->user()->role_aktif;
        $pegawai = Pegawai::where('username', auth()->user()->username)->first();
        $pegawai_id = optional($pegawai)->id;
        $pejabat = Pejabat::where('pegawai_id', $pegawai_id)->first();
        $pejabat_id = optional($pejabat)->id;
        
        if ($role == 'admin') {
            $cuti = Cuti::latest()->get();
        } elseif ($role == 'operator' && $pejabat_id == 1) {
            $cuti = Cuti::where('status', "Acc")->latest()->get();
        } elseif ($role == 'operator' && $pejabat_id != 1) {
            $pejabat_id = Pejabat::where('pegawai_id', $pegawai_id)->first()->id;
            $cuti = Cuti::where('pejabat_id', $pejabat_id)->latest()->get();
            $statuses = $cuti->pluck('status');
            if ($statuses->contains('Diproses')) {
                $cuti;
            } else {
                $cuti = null;
            }
        } elseif ($role == 'terdaftar') {
            $cuti = Cuti::where('pegawai_id', $pegawai_id)->latest()->get();
        }
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
        $anggota = Anggota::where('pegawai_id', $pegawai->id)->first();
        $tim = TimKerja::where('id', $anggota->tim_kerja_id)->first();
        $ketua = Pejabat::where('id', $tim->ketua_id)->first();

        return view('cuti::pengajuan_cuti.create', compact('jenis_cuti', 'pegawai', 'tim', 'anggota', 'ketua'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        // Validasi inputan
        $request->validate([
            'pegawai_id' => 'required|exists:pegawai,id',
            'atasan_id' => 'required|exists:pejabat,id',
            'jenis_cuti' => 'required|exists:jenis_cuti,id',
            'rentang_cuti' => 'required',
            'dok_pendukung' => 'nullable|file|mimes:pdf|max:2048',
            'keterangan' => 'required',
        ]);

        // Explode data rentang cuti
        $tanggal = $request->input('rentang_cuti');
        $tanggalRange = explode(' - ', $tanggal);
        if (count($tanggalRange) == 2) {
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[1];
        } elseif (count($tanggalRange) !== 2) {
            return redirect()->back()->withInput()->with('danger', 'Format rentang cuti tidak valid.');
        }

        try {
            // Simpan file kedalam storage
            if ($request->hasFile('dok_pendukung')) {
                $file = $request->file('dok_pendukung');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/uploads/dok_pendukung', $fileName);
                $dokPendukungPath = 'uploads/dok_pendukung/' . $fileName;
            } else {
                $dokPendukungPath = null;
            }

            // Insert data ke tabel cuti
            $data = Cuti::create([
                'tanggal_mulai' => $awal_cuti,
                'tanggal_selesai' => $akhir_cuti,
                'keterangan' => $request->keterangan,
                'dok_pendukung' => $dokPendukungPath,
                'status' => 'Diajukan',
                'pegawai_id' => $request->pegawai_id,
                'pejabat_id' => $request->atasan_id,
                'tim_kerja_id' => $request->tim_kerja_id,
                'jenis_cuti_id' => $request->jenis_cuti,
                'user_id' => auth()->user()->id,
            ]);

            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil diajukan.');
        } catch (\Throwable $th) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal diajukan karena.');
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $cuti = Cuti::findOrFail($id);
        $jenis_cuti = JenisCuti::all();
        $anggota = Anggota::where('pegawai_id', $cuti->pegawai->id)->first();
        // dd($pegawai);

        return view('cuti::pengajuan_cuti.show', compact('jenis_cuti', 'cuti', 'anggota'));
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
