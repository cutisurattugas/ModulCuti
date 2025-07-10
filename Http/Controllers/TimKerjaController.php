<?php

namespace Modules\Cuti\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Cuti\Entities\Anggota;
use Modules\Cuti\Entities\Pegawai;
use Modules\Cuti\Entities\TimKerja;
use Modules\Cuti\Entities\Unit;
use Modules\Jabatan\Entities\Pejabat;

class TimKerjaController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $timKerja = TimKerja::with('ketua', 'unit')
            ->where('parent_id', 1) // anak dari politeknik
            ->get();
        $ketuaUtama = Pejabat::find(1);
        $pejabat = Pejabat::all();
        $parent_id = 1; // id Politeknik Negeri Banyuwangi atau root tim
        $units = Unit::all();
        $allTimKerja = TimKerja::with('unit')->get();
        $pegawai = Pegawai::with(['timKerjaAnggota.subUnits.unit', 'timKerjaAnggota.parentUnit.unit'])->get();

        return view('cuti::tim.index', compact('timKerja', 'pejabat', 'parent_id', 'ketuaUtama', 'units', 'allTimKerja', 'pegawai'));
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
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'ketua_id' => 'required',
            'parent_id' => 'required|exists:tim_kerja,id'
        ]);

        try {
            $data = explode('|', $request->input('ketua_id'));
            if (count($data) !== 2) {
                return back()->withErrors(['ketua' => 'Format ketua tidak valid.']);
            }
            $pejabat_id = $data[0];
            $pegawai_id = $data[1];

            DB::transaction(function () use ($request, $pegawai_id, $pejabat_id) {
                $timKerja = TimKerja::create([
                    'unit_id' => $request->unit_id,
                    'parent_id' => $request->parent_id,
                    'ketua_id' => $pejabat_id
                ]);

                Anggota::create([
                    'tim_kerja_id' => $timKerja->id,
                    'pegawai_id' => $pegawai_id,
                    'peran' => $pejabat_id ? 'Ketua' : null,
                ]);
            });

            return redirect()->back()->with('success', 'Tim berhasil ditambahkan.');
        } catch (\Throwable $th) {
            return response()->json($th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $timKerja = TimKerja::with('ketua')
            ->where('parent_id', $id)
            ->get();

        $unitInduk = TimKerja::with('ketua')->findOrFail($id); // Unit yang sedang dibuka
        $ketuaUtama = $unitInduk->ketua; // Bisa null
        $pejabat = Pejabat::all();
        $parent_id = $id;
        $units = Unit::all();

        return view('cuti::tim.index', compact('timKerja', 'pejabat', 'parent_id', 'ketuaUtama', 'unitInduk', 'units'));
    }

    public function getChildren($id)
    {
        $children = TimKerja::with(['unit', 'ketua.pegawai', 'ketua.jabatan'])->where('parent_id', $id)->get();
        $unitInduk = TimKerja::with(['ketua.pegawai', 'ketua.jabatan'])->find($id);

        $html = '';
        if ($unitInduk) {
            $html .= '<div class="border rounded p-2 mb-3">';

            // Buka baris horizontal: nama + ikon
            $html .= '<div class="d-flex align-items-start justify-content-between">';

            // Bagian kiri: nama + ikon
            $html .= '<div class="d-flex align-items-center">';

            // Nama ketua
            if ($unitInduk->ketua) {
                $pegawai = $unitInduk->ketua->pegawai;
                $namaLengkap = ($pegawai->gelar_dpn ?? '') .
                    ($pegawai->gelar_dpn ? ' ' : '') .
                    ($pegawai->nama ?? '') .
                    ($pegawai->gelar_blk ? ', ' . $pegawai->gelar_blk : '');
                $html .= '<div class="me-2 fw-bold">' . $namaLengkap . ' [Ketua]</div>';

                // Ikon edit + delete
                $html .= '<div class="d-flex align-items-center gap-2">';

                // Edit icon
                $html .= '<a href="javascript:void(0);" data-toggle="modal" data-target="#modalEditPegawai" title="Edit">';
                $html .= '<i class="fas fa-star text-info"></i>';
                $html .= '</a>';

                // Delete icon
                $html .= '<form action="#" method="POST" class="d-inline delete-form" onsubmit="return confirm(\'Yakin ingin menghapus?\')" style="display:inline">';
                $html .= csrf_field() . method_field('DELETE');
                $html .= '<button type="submit" class="btn p-0 border-0 bg-transparent" title="Hapus" style="line-height:1">';
                $html .= '<i class="fas fa-trash text-danger"></i>';
                $html .= '</button>';
                $html .= '</form>';

                $html .= '</div>'; // end icon group
            } else {
                $html .= '<em>Belum ada ketua</em>';
            }

            $html .= '</div>'; // end d-flex align-items-center (nama + ikon)

            $html .= '</div>'; // end d-flex utama

            // Baris kedua: detail nip dan jabatan
            if ($unitInduk->ketua) {
                $html .= '<div class="mt-1">';
                $html .= '<small>' . $unitInduk->ketua->pegawai->nip . ' | ' . ($unitInduk->ketua->jabatan->jabatan ?? '-') . ' | Sudah Buat SKP dengan Peran Ini</small>';
                $html .= '</div>';
            }

            $html .= '</div>'; // end container
        }


        // Menampilkan tim kerja anak-anak
        foreach ($children as $child) {
            $html .= '<div class="border rounded p-2 mb-1">';

            // Baris fleksibel: Nama unit + ikon
            $html .= '<div class="d-flex align-items-center justify-content-between">';

            // Nama Unit Anak + Ikon Aksi langsung nempel
            $html .= '<div class="d-flex align-items-center">';

            // Nama unit (toggle)
            $html .= '<a href="javascript:void(0)" class="toggle-child me-2 text-decoration-none" data-id="' . $child->id . '">';
            $html .= '<strong>' . $child->unit->nama . '</strong>';
            $html .= '</a>';

            // Ikon edit
            $html .= '<a href="javascript:void(0);" data-toggle="modal" data-target="#modalEditPegawai" title="Edit" class="text-warning ms-1">';
            $html .= '<i class="fas fa-edit"></i>';
            $html .= '</a>';

            // Ikon delete
            $html .= '<form action="#" method="POST" class="d-inline ms-2 delete-form" onsubmit="return confirm(\'Yakin ingin menghapus?\')" style="margin: 0">';
            $html .= csrf_field() . method_field('DELETE');
            $html .= '<button type="submit" class="p-0 border-0 bg-transparent text-danger" style="line-height:1">';
            $html .= '<i class="fas fa-trash"></i>';
            $html .= '</button>';
            $html .= '</form>';

            $html .= '</div>'; // end nama + ikon

            $html .= '</div>'; // end d-flex

            // Container anak dalam
            $html .= '<div class="children-container mt-2" id="child-container-' . $child->id . '"></div>';
            $html .= '</div>'; // end border
        }


        return response()->json([
            'status' => 'ok',
            'html' => $html,  // Mengirimkan HTML yang telah dirender
        ]);
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
