<?php

namespace Modules\Cuti\Http\Controllers;

use App\Models\Core\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\CutiLogs;
use Modules\Cuti\Entities\JenisCuti;
use Modules\Cuti\Services\AtasanService;
use Modules\Cuti\Services\SisaCutiService;
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
        $pegawai_username = optional($pegawai)->username;
        $pejabat = Pejabat::where('pegawai_username', $pegawai_username)->first();
        $pejabat_id = optional($pejabat)->id;

        $cuti = null;
        $cuti_pribadi = null;
        $cuti_anggota = null;

        if ($role == 'admin') {
            // Admin bisa lihat semua cuti
            $cuti = Cuti::latest()->get();
        } elseif ($role == 'operator' && $pejabat_id == 1) {
            // Operator pusat bisa lihat semua yang sedang diproses
            $cuti = Cuti::where('status', "diproses")->latest()->get();
        } elseif ($role == 'operator' && $pejabat_id != 1) {
            // Atasan (operator bukan pusat)
            $cuti_anggota = Cuti::where('pejabat_id', $pejabat_id)->latest()->get();
            $cuti_pribadi = Cuti::where('pegawai_username', $pegawai_username)->latest()->get();
        } elseif ($role == 'terdaftar') {
            // Pegawai biasa hanya bisa lihat cuti dirinya sendiri
            $cuti_pribadi = Cuti::where('pegawai_username', $pegawai_username)->latest()->get();
        }
        return view('cuti::pengajuan_cuti.index', compact('cuti', 'cuti_pribadi', 'cuti_anggota'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $jenis_cuti = JenisCuti::all();
        $pegawai = Pegawai::where('username', auth()->user()->username)->first();

        $anggota = Anggota::where('pegawai_username', $pegawai->username)->first();
        $tim = TimKerja::find($anggota->tim_kerja_id ?? null); // jika ada

        // Gunakan AtasanService
        $atasanService = new AtasanService();
        $ketua = $atasanService->getAtasanPegawai($pegawai->username);

        // Hitung sisa cuti
        $sisaCutiService = new SisaCutiService();
        $sisa_cuti = $sisaCutiService->hitung($pegawai->user_id);

        // dd($sisa_cuti);
        return view('cuti::pengajuan_cuti.create', compact(
            'jenis_cuti',
            'pegawai',
            'tim',
            'anggota',
            'ketua',
            'sisa_cuti'
        ));
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
            'pegawai_username' => 'required|exists:pegawai,username',
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

        // Mulai transaksi DB
        DB::beginTransaction();
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
                'pegawai_username' => $request->pegawai_username,
                'pejabat_id' => $request->atasan_id,
                'tim_kerja_id' => $request->tim_kerja_id,
                'jenis_cuti_id' => $request->jenis_cuti,
                'user_id' => auth()->user()->id,
            ]);

            // Insert data ke table cuti_logs
            CutiLogs::create([
                'cuti_id' => $data->id,
                'status' => 'Diajukan',
                'updated_by' => auth()->user()->id,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil diajukan.');
        } catch (\Throwable $th) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();
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
        $user_login = auth()->user();

        // Ambil ID pejabat login (jika operator)
        $id_pejabat_login = null;
        if ($user_login->role_aktif === 'operator') {
            $id_user_login = $user_login->id;
            $pegawai_login = Pegawai::where('user_id', $id_user_login)->first();
            $pejabat_login = Pejabat::where('pegawai_username', $pegawai_login->username)->first();
            $id_pejabat_login = optional($pejabat_login)->id;
        }

        $cuti = Cuti::findOrFail($id);

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->username); // ⬅️ Ini yang diganti

        // Sisa cuti
        $sisaCutiService = new SisaCutiService();
        $sisa_cuti = $sisaCutiService->hitung($cuti->user_id);

        // Tambahan data lain
        $jenis_cuti = JenisCuti::all();
        $anggota = Anggota::where('pegawai_username', $cuti->pegawai->username)->first();
        $tim = TimKerja::find(optional($anggota)->tim_kerja_id);

        return view('cuti::pengajuan_cuti.show', compact(
            'jenis_cuti',
            'cuti',
            'anggota',
            'tim',
            'pejabat',
            'id_pejabat_login',
            'sisa_cuti'
        ));
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

    // Update status cuti oleh admin
    public function approvedByKepegawaian(Request $request, $id)
    {
        // Pastikan hanya unit kepegawaian (admin) yang bisa menyetujui
        if (!auth()->user()->role_aktif === 'admin') {
            return redirect()->route('cuti.index')->with('danger', 'Anda tidak memiliki hak akses untuk menyetujui cuti.');
        }

        // Ambil data cuti berdasarkan ID
        $cuti = Cuti::find($id);

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Mulai transaksi DB
        DB::beginTransaction();

        try {
            // Perbarui status menjadi "Disetujui Unit Kepegawaian"
            $cuti->status = 'Diproses';
            $cuti->save();

            // Tambahkan log status ke tabel cuti_logs
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah diteruskan ke atasan',
                'updated_by' => auth()->user()->id,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            // Redirect ke halaman pengajuan cuti
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil diteruskan ke atasan.');
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();

            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal disetujui karena: ' . $th->getMessage());
        }
    }

    public function approvedByAtasan(Request $request, $id)
    {
        // Pastikan hanya unit kepegawaian (admin) yang bisa menyetujui
        if (!auth()->user()->role_aktif === 'operator') {
            return redirect()->route('cuti.index')->with('danger', 'Anda tidak memiliki hak akses untuk menyetujui cuti.');
        }

        // Ambil data cuti berdasarkan ID
        $cuti = Cuti::find($id);

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Mulai transaksi DB
        DB::beginTransaction();

        try {
            // Perbarui status menjadi "Disetujui Unit Kepegawaian"
            $cuti->status = 'Diproses';
            $cuti->save();

            // Tambahkan log status ke tabel cuti_logs
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah diteruskan ke pimpinan',
                'updated_by' => auth()->user()->id,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            // Redirect ke halaman pengajuan cuti
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil diteruskan ke pimpinan.');
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();

            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal disetujui karena: ' . $th->getMessage());
        }
    }

    public function approvedByPimpinan(Request $request, $id)
    {
        // Pastikan hanya unit kepegawaian (admin) yang bisa menyetujui
        if (!auth()->user()->role_aktif === 'operator') {
            return redirect()->route('cuti.index')->with('danger', 'Anda tidak memiliki hak akses untuk menyetujui cuti.');
        }

        // Ambil data cuti berdasarkan ID
        $cuti = Cuti::find($id);

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Mulai transaksi DB
        DB::beginTransaction();

        try {
            // Perbarui status menjadi "Disetujui Unit Kepegawaian"
            $cuti->status = 'Disetujui';
            $cuti->save();

            // Tambahkan log status ke tabel cuti_logs
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah disetujui pimpinan',
                'updated_by' => auth()->user()->id,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            // Redirect ke halaman pengajuan cuti
            return redirect()->route('cuti.index')->with('success', 'Cuti telah di setujui.');
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();

            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal disetujui karena: ' . $th->getMessage());
        }
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
