<?php

namespace Modules\Cuti\Http\Controllers;

use App\Models\Core\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        } elseif ($role == 'operator') {
            if ($pejabat_id == 1) {
                // Pimpinan: lihat semua cuti yang sudah diteruskan ke pimpinan
                $cuti = Cuti::whereHas('logs', function ($query) {
                    $query->where('status', 'Telah diteruskan ke pimpinan');
                })->latest()->get();
            } else {
                // Atasan: lihat semua cuti yang diteruskan ke atasan dan pejabat_id sesuai
                $cuti_anggota = Cuti::where('pejabat_id', $pejabat_id)
                    ->whereHas('logs', function ($query) {
                        $query->where('status', 'Telah diteruskan ke atasan');
                    })->latest()->get();

                // Cuti pribadi tetap bisa dilihat
                $cuti_pribadi = Cuti::where('pegawai_username', $pegawai_username)->latest()->get();
            }
        } elseif ($role == 'terdaftar') {
            // Pegawai biasa hanya bisa lihat cuti dirinya sendiri
            $cuti_pribadi = Cuti::where('pegawai_username', $pegawai_username)->latest()->get();
        }
        return view('cuti::pengajuan_cuti.index', compact('cuti', 'cuti_pribadi', 'cuti_anggota', 'pejabat_id'));
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
        $sisaCutiService->ensureCutiSisaTerbuat($pegawai->username);

        // Ambil data sisa cuti tahun ini
        $ambilCuti = DB::table('cuti_sisa')->where('pegawai_username', $pegawai->username)->where('tahun', Carbon::now()->year)->first();
        $sisa_cuti = $ambilCuti->cuti_awal + $ambilCuti->cuti_dibawa;
        // dd($sisa_cuti);
        return view('cuti::pengajuan_cuti.create', compact(
            'jenis_cuti',
            'pegawai',
            'tim',
            'anggota',
            'ketua',
            'sisa_cuti',
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
            'dok_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
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
            ]);

            // Insert data ke table cuti_logs
            CutiLogs::create([
                'cuti_id' => $data->id,
                'status' => 'Diajukan',
                'updated_by' => auth()->user()->username,
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
            $username_user_login = $user_login->username;
            $pegawai_login = Pegawai::where('username', $username_user_login)->first();
            $pejabat_login = Pejabat::where('pegawai_username', $pegawai_login->username)->first();
            $id_pejabat_login = optional($pejabat_login)->id;
        }
        // dd($pejabat_login);
        $cuti = Cuti::findOrFail($id);

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->username);

        // Ambil data sisa cuti tahun ini
        $ambilCuti = DB::table('cuti_sisa')->where('pegawai_username', $cuti->pegawai_username)->where('tahun', Carbon::now()->year)->first();

        $sisa_cuti = $ambilCuti->cuti_awal + $ambilCuti->cuti_dibawa;

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
        $user_login = auth()->user();

        // Ambil ID pejabat login (jika operator)
        $id_pejabat_login = null;
        if ($user_login->role_aktif === 'operator') {
            $username_user_login = $user_login->username;
            $pegawai_login = Pegawai::where('username', $username_user_login)->first();
            $pejabat_login = Pejabat::where('pegawai_username', $pegawai_login->username)->first();
            $id_pejabat_login = optional($pejabat_login)->id;
        }
        // dd($pejabat_login);
        $cuti = Cuti::findOrFail($id);

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->username);

        // Ambil data sisa cuti tahun ini
        $ambilCuti = DB::table('cuti_sisa')->where('pegawai_username', $cuti->pegawai_username)->where('tahun', Carbon::now()->year)->first();

        $sisa_cuti = $ambilCuti->cuti_awal + $ambilCuti->cuti_dibawa;

        // Tambahan data lain
        $jenis_cuti = JenisCuti::all();
        $anggota = Anggota::where('pegawai_username', $cuti->pegawai->username)->first();
        $tim = TimKerja::find(optional($anggota)->tim_kerja_id);

        return view('cuti::pengajuan_cuti.edit', compact(
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
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        // Validasi inputan
        $request->validate([
            'jenis_cuti' => 'required|exists:jenis_cuti,id',
            'rentang_cuti' => 'required',
            'dok_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'keterangan' => 'required',
        ]);

        $cuti = Cuti::findOrFail($id);

        // Explode rentang tanggal
        $tanggal = $request->input('rentang_cuti');
        $tanggalRange = explode(' - ', $tanggal);
        if (count($tanggalRange) !== 2) {
            return redirect()->back()->withInput()->with('danger', 'Format rentang cuti tidak valid.');
        }
        $awal_cuti = $tanggalRange[0];
        $akhir_cuti = $tanggalRange[1];

        DB::beginTransaction();
        try {
            // Proses file baru jika ada
            if ($request->hasFile('dok_pendukung')) {
                $file = $request->file('dok_pendukung');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/uploads/dok_pendukung', $fileName);
                $dokPendukungPath = 'uploads/dok_pendukung/' . $fileName;

                // Hapus file lama jika ada
                if ($cuti->dok_pendukung && Storage::exists('public/' . $cuti->dok_pendukung)) {
                    Storage::delete('public/' . $cuti->dok_pendukung);
                }

                $cuti->dok_pendukung = $dokPendukungPath;
            }

            // Update data cuti
            $cuti->tanggal_mulai = $awal_cuti;
            $cuti->tanggal_selesai = $akhir_cuti;
            $cuti->keterangan = $request->keterangan;
            $cuti->jenis_cuti_id = $request->jenis_cuti;
            $cuti->save();

            // Tambahkan ke log
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Diedit',
                'updated_by' => auth()->user()->username,
            ]);

            DB::commit();
            return redirect()->route('cuti.index')->with('success', 'Pengajuan cuti berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Terjadi kesalahan saat memperbarui cuti.');
        }
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
                'updated_by' => auth()->user()->username,
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
                'updated_by' => auth()->user()->username,
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
        if (!auth()->user()->role_aktif === 'operator') {
            return redirect()->route('cuti.index')->with('danger', 'Anda tidak memiliki hak akses untuk menyetujui cuti.');
        }

        $cuti = Cuti::find($id);

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        DB::beginTransaction();
        try {
            $cuti->status = 'Disetujui';
            $cuti->save();

            // Hitung durasi cuti
            $jumlah_hari = Carbon::parse($cuti->tanggal_selesai)
                ->diffInDays(Carbon::parse($cuti->tanggal_mulai)) + 1;
            $tahun = Carbon::parse($cuti->tanggal_mulai)->year;

            if ($cuti->jenis_cuti_id == 4) {
                // Jika cuti besar, hanguskan jatah cuti tahunan tahun ini
                DB::table('cuti_sisa')
                    ->where('pegawai_username', $cuti->pegawai_username)
                    ->where('tahun', $tahun)
                    ->update([
                        'cuti_awal' => 0,
                        'cuti_dibawa' => 0,
                        'updated_at' => now(),
                    ]);
            } elseif ($cuti->jenis_cuti_id == 1) {
                // Hanya potong jatah cuti jika jenis cuti adalah tahunan
                $cutiSisa = DB::table('cuti_sisa')
                    ->where('pegawai_username', $cuti->pegawai_username)
                    ->where('tahun', $tahun)
                    ->first();

                if (!$cutiSisa) {
                    throw new \Exception('Data cuti_sisa tidak ditemukan untuk tahun ' . $tahun);
                }

                $sisa_dibawa = $cutiSisa->cuti_dibawa;
                $sisa_awal = $cutiSisa->cuti_awal;
                $terpakai = 0;
                $cuti_dibawa_baru = $sisa_dibawa;
                $cuti_awal_baru = $sisa_awal;

                if ($jumlah_hari <= $sisa_dibawa) {
                    $cuti_dibawa_baru -= $jumlah_hari;
                    $terpakai = $jumlah_hari;
                } else {
                    $terpakai = $jumlah_hari;
                    $cuti_dibawa_baru = 0;
                    $sisa_dari_awal = $jumlah_hari - $sisa_dibawa;
                    $cuti_awal_baru -= $sisa_dari_awal;
                }

                DB::table('cuti_sisa')
                    ->where('pegawai_username', $cuti->pegawai_username)
                    ->where('tahun', $tahun)
                    ->update([
                        'cuti_awal' => $cuti_awal_baru,
                        'cuti_dibawa' => $cuti_dibawa_baru,
                        'cuti_digunakan' => $cutiSisa->cuti_digunakan + $terpakai,
                        'updated_at' => now(),
                    ]);
            }

            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah disetujui pimpinan',
                'updated_by' => auth()->user()->username,
            ]);

            DB::commit();
            return redirect()->route('cuti.index')->with('success', 'Cuti telah disetujui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal disetujui karena: ' . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function cancelCuti(Request $request, $id)
    {
        // Validasi input alasan
        $request->validate([
            'alasan_batal' => 'required|string|max:255',
        ]);

        $cuti = Cuti::find($id);

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Tidak boleh batalkan cuti yang sudah disetujui
        if ($cuti->status === 'Disetujui') {
            return redirect()->route('cuti.index')->with('danger', 'Cuti yang sudah disetujui tidak bisa dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $cuti->status = 'Dibatalkan';
            $cuti->save();

            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Dibatalkan',
                'catatan' => $request->alasan_batal,
                'updated_by' => auth()->user()->username,
            ]);

            DB::commit();
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil dibatalkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Gagal membatalkan cuti: ' . $th->getMessage());
        }
    }
}
