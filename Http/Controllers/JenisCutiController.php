<?php

namespace Modules\Cuti\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cuti\Entities\JenisCuti;

class JenisCutiController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $jenis = JenisCuti::all();
        return view('cuti::jenis_cuti.index', compact('jenis'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('cuti::jenis_cuti.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama_cuti' => 'required|string|max:255',
            'jumlah_cuti' => 'required|integer|min:1',
            'deskripsi' => 'nullable|string',
        ]);

        // Simpan data ke database
        JenisCuti::create([
            'nama_cuti' => $request->nama_cuti,
            'jumlah_cuti' => $request->jumlah_cuti,
            'deskripsi' => $request->deskripsi,
        ]);

        // Redirect dengan pesan sukses
        return redirect()->route('jenis_cuti.index')->with('success', 'Jenis cuti berhasil ditambahkan!');
    }
    
    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        // Ambil data jenis cuti berdasarkan ID
        $jenisCuti = JenisCuti::findOrFail($id);

        // Kirim data ke view edit
        return view('cuti::jenis_cuti.edit', compact('jenisCuti'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_cuti' => 'string|max:255',
            'jumlah_cuti' => 'integer',
            'deskripsi' => 'nullable|string',
        ]);

        $jenisCuti = JenisCuti::findOrFail($id);
        $jenisCuti->update([
            'nama_cuti' => $request->nama_cuti,
            'jumlah_cuti' => $request->jumlah_cuti,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()->route('jenis_cuti.index')->with('success', 'Jenis cuti berhasil diperbarui!');
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        // Ambil data jenis cuti berdasarkan ID
        $jenisCuti = JenisCuti::findOrFail($id);
        $jenisCuti->delete();

        // Redirect dengan pesan sukses
        return redirect()->route('jenis_cuti.index')->with('success', 'Jenis cuti berhasil ditambahkan!');
    }
}
