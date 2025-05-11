<?php

namespace Modules\Cuti\Http\Controllers;

use App\Models\Core\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\CutiLogs;
use Modules\Cuti\Entities\CutiSisa;
use Modules\Cuti\Entities\JenisCuti;
use Modules\Cuti\Services\AtasanService;
use Modules\Cuti\Services\SisaCutiService;
use Modules\Cuti\Services\HariKerjaService;
use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\Pejabat;
use Modules\Pengaturan\Entities\TimKerja;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;

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

        $cuti = null;
        $cuti_pribadi = null;
        $cuti_anggota = null;

        if ($role == 'admin') {
            // Admin bisa lihat semua cuti
            $cuti = Cuti::latest()->get();
        } elseif ($role == 'direktur') {
            // Pimpinan: lihat semua cuti yang sudah diteruskan ke pimpinan
            $cuti = Cuti::whereHas('logs', function ($query) {
                $query->where('status', 'Telah diteruskan ke pimpinan');
            })->latest()->get();
        } elseif ($role == 'kajur') {
            // Atasan: lihat semua cuti yang diteruskan ke atasan dan pejabat_id sesuai
            $cuti_anggota = Cuti::where('pejabat_id', $pejabat_id)
                ->whereHas('logs', function ($query) {
                    $query->where('status', 'Telah diteruskan ke atasan');
                })->latest()->get();

            // Cuti pribadi tetap bisa dilihat
            $cuti_pribadi = Cuti::where('pegawai_id', $pegawai_id)->latest()->get();
        } elseif ($role == 'dosen') {
            // Pegawai biasa hanya bisa lihat cuti dirinya sendiri
            $cuti_pribadi = Cuti::where('pegawai_id', $pegawai_id)->latest()->get();
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

        $anggota = Anggota::where('pegawai_id', $pegawai->id)->first();
        $tim = TimKerja::find($anggota->tim_kerja_id ?? null); // jika ada

        // Gunakan AtasanService
        $atasanService = new AtasanService();
        $ketua = $atasanService->getAtasanPegawai($pegawai->id);

        // Hitung sisa cuti
        $sisaCutiService = new SisaCutiService();
        $sisaCutiService->ensureCutiSisaTerbuat($pegawai->id);

        // Ambil data sisa cuti tahun ini
        $ambilCuti = DB::table('cuti_sisa')->where('pegawai_id', $pegawai->id)->where('tahun', Carbon::now()->year)->first();
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
            'pegawai_id' => 'required|exists:pegawais,id',
            'pejabat_id' => 'required|exists:pejabats,id',
            'jenis_cuti' => 'required|exists:jenis_cuti,id',
            'rentang_cuti' => 'required',
            'dok_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Get data pimpinan
        $pimpinanId = Pejabat::where('jabatan_id', 1)->first()->value('id');

        // Explode data rentang cuti
        $tanggal = $request->input('rentang_cuti');
        $tanggalRange = explode(' to ', $tanggal);
        if (count($tanggalRange) == 2) {
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[1];

            // Hitung jumlah hari kerja
            $hariKerjaService = new HariKerjaService();
            $jumlah_cuti = $hariKerjaService->countHariKerja($awal_cuti, $akhir_cuti);

            // Cek kuota jika jenis cuti adalah "Cuti Tahunan" (id = 1)
            if ($request->jenis_cuti == 1) {
                // Ambil tahun dari tanggal mulai cuti
                $tahunCuti = date('Y', strtotime($awal_cuti));
                $pegawaiId = $request->pegawai_id;

                // Cari record cuti_sisa untuk pegawai dan tahun ini
                $cutiSisa = CutiSisa::where('pegawai_id', $pegawaiId)
                    ->where('tahun', $tahunCuti)
                    ->first();

                if (!$cutiSisa) {
                    return redirect()->back()->withInput()->with('error', 'Data kuota cuti tahunan belum tersedia.');
                }

                // Hitung sisa kuota
                $kuota = $cutiSisa->cuti_dibawa > 0 ? $cutiSisa->cuti_dibawa : $cutiSisa->cuti_awal;
                $sisaKuota = $kuota - $cutiSisa->cuti_digunakan;

                if ($jumlah_cuti > $sisaKuota) {
                    return redirect()->back()->withInput()->with('error', "Pengajuan cuti melebihi kuota yang tersisa.");
                }
            }

            // Cek apakah ada hari kerja
            if ($jumlah_cuti <= 0) {
                return redirect()->back()->withInput()->with('error', 'Rentang cuti tidak mencakup hari kerja.');
            }
        } elseif (count($tanggalRange) !== 2) {
            return redirect()->back()->withInput()->with('error', 'Format rentang cuti tidak valid.');
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
                'jumlah_cuti' => $jumlah_cuti,
                'keterangan' => $request->keterangan,
                'dok_pendukung' => $dokPendukungPath,
                'status' => 'Diajukan',
                'pegawai_id' => $request->pegawai_id,
                'pejabat_id' => $request->pejabat_id,
                'pimpinan_id' => $pimpinanId,
                'tim_kerja_id' => $request->tim_kerja_id,
                'jenis_cuti_id' => $request->jenis_cuti,
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
            $pegawai_login = Pegawai::where('id', $id_user_login)->first();
            $pejabat_login = Pejabat::where('pegawai_id', $pegawai_login->id)->first();
            $id_pejabat_login = optional($pejabat_login)->id;
        }
        // dd($pejabat_login);
        $cuti = Cuti::findOrFail($id);

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->id);

        // Ambil data sisa cuti tahun ini
        $ambilCuti = DB::table('cuti_sisa')->where('pegawai_id', $cuti->pegawai_id)->where('tahun', Carbon::now()->year)->first();

        $sisa_cuti = $ambilCuti->cuti_awal + $ambilCuti->cuti_dibawa;

        // Tambahan data lain
        $jenis_cuti = JenisCuti::all();
        $anggota = Anggota::where('pegawai_id', $cuti->pegawai->id)->first();
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
            $id_user_login = $user_login->id;
            $pegawai_login = Pegawai::where('id', $id_user_login)->first();
            $pejabat_login = Pejabat::where('pegawai_id', $pegawai_login->id)->first();
            $id_pejabat_login = optional($pejabat_login)->id;
        }
        // dd($pejabat_login);
        $cuti = Cuti::findOrFail($id);

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->id);

        // Ambil data sisa cuti tahun ini
        $ambilCuti = DB::table('cuti_sisa')->where('pegawai_id', $cuti->pegawai_id)->where('tahun', Carbon::now()->year)->first();

        $sisa_cuti = $ambilCuti->cuti_awal + $ambilCuti->cuti_dibawa;

        // Tambahan data lain
        $jenis_cuti = JenisCuti::all();
        $anggota = Anggota::where('pegawai_id', $cuti->pegawai->id)->first();
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
        $tanggalRange = explode(' to ', $tanggal);
        if (count($tanggalRange) == 2) {
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[1];

            // Hitung jumlah hari kerja
            $hariKerjaService = new HariKerjaService();
            $jumlah_cuti = $hariKerjaService->countHariKerja($awal_cuti, $akhir_cuti);

            // Cek kuota jika jenis cuti adalah "Cuti Tahunan" (id = 1)
            if ($request->jenis_cuti == 1) {
                // Ambil tahun dari tanggal mulai cuti
                $tahunCuti = date('Y', strtotime($awal_cuti));
                $pegawaiId = $request->pegawai_id;

                // Cari record cuti_sisa untuk pegawai dan tahun ini
                $cutiSisa = CutiSisa::where('pegawai_id', $pegawaiId)
                    ->where('tahun', $tahunCuti)
                    ->first();

                if (!$cutiSisa) {
                    return redirect()->back()->withInput()->with('danger', 'Data kuota cuti tahunan belum tersedia.');
                }

                // Hitung sisa kuota
                $kuota = $cutiSisa->cuti_dibawa > 0 ? $cutiSisa->cuti_dibawa : $cutiSisa->cuti_awal;
                $sisaKuota = $kuota - $cutiSisa->cuti_digunakan;

                if ($jumlah_cuti > $sisaKuota) {
                    return redirect()->back()->withInput()->with('danger', "Pengajuan cuti melebihi kuota yang tersisa.");
                }
            }

            // Cek apakah ada hari kerja
            if ($jumlah_cuti <= 0) {
                return redirect()->back()->withInput()->with('danger', 'Rentang cuti tidak mencakup hari kerja.');
            }
        } elseif (count($tanggalRange) !== 2) {
            return redirect()->back()->withInput()->with('danger', 'Format rentang cuti tidak valid.');
        }

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
            $cuti->jumlah_cuti = $jumlah_cuti;
            $cuti->keterangan = $request->keterangan;
            $cuti->jenis_cuti_id = $request->jenis_cuti;
            $cuti->save();

            // Tambahkan ke log
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Diedit',
                'updated_by' => auth()->user()->id,
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
            $cuti->catatan_kepegawaian = $request->catatan_kepegawaian;
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
            $cuti->tanggal_disetujui_pejabat = now();
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
            $cuti->tanggal_disetujui_pimpinan = now();
            $cuti->save();

            // Hitung durasi cuti
            $jumlah_hari = Carbon::parse($cuti->tanggal_selesai)
                ->diffInDays(Carbon::parse($cuti->tanggal_mulai)) + 1;
            $tahun = Carbon::parse($cuti->tanggal_mulai)->year;

            if ($cuti->jenis_cuti_id == 4) {
                // Jika cuti besar, hanguskan jatah cuti tahunan tahun ini
                DB::table('cuti_sisa')
                    ->where('pegawai_id', $cuti->pegawai_id)
                    ->where('tahun', $tahun)
                    ->update([
                        'cuti_awal' => 0,
                        'cuti_dibawa' => 0,
                        'updated_at' => now(),
                    ]);
            } elseif ($cuti->jenis_cuti_id == 1) {
                // Hanya potong jatah cuti jika jenis cuti adalah tahunan
                $cutiSisa = DB::table('cuti_sisa')
                    ->where('pegawai_id', $cuti->pegawai_id)
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
                    ->where('pegawai_id', $cuti->pegawai_id)
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
                'updated_by' => auth()->user()->id,
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
            $cuti->alasan_batal = $request->alasan_batal;
            $cuti->save();

            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Dibatalkan',
                'catatan' => $request->alasan_batal,
                'updated_by' => auth()->user()->id,
            ]);

            DB::commit();
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil dibatalkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Gagal membatalkan cuti: ' . $th->getMessage());
        }
    }

    public function printCuti($id)
    {
        $cuti = Cuti::findOrFail($id);

        // Ambil data atasan
        $atasanService = new AtasanService();
        $atasan = $atasanService->getAtasanPegawai($cuti->pegawai_id);

        // Ambil data pimpinan
        $pimpinan = Pejabat::where('id', 1)->first();

        // Hitung jumlah cuti sebelum tanggal_mulai dari cuti saat ini
        $pegawaiId = $cuti->pegawai_id;
        $currentTanggalMulai = $cuti->tanggal_mulai;

        // Hitung jumlah hari cuti berdasarkan jenis cuti
        $cutiCounts = [
            1 => Cuti::where('pegawai_id', $pegawaiId)
                ->where('jenis_cuti_id', 1)
                ->where('status', 'selesai')
                ->where('tanggal_mulai', '<', $currentTanggalMulai)
                ->sum('jumlah_cuti'),

            2 => Cuti::where('pegawai_id', $pegawaiId)
                ->where('jenis_cuti_id', 2)
                ->where('status', 'selesai')
                ->where('tanggal_mulai', '<', $currentTanggalMulai)
                ->sum('jumlah_cuti'),

            3 => Cuti::where('pegawai_id', $pegawaiId)
                ->where('jenis_cuti_id', 3)
                ->where('status', 'selesai')
                ->where('tanggal_mulai', '<', $currentTanggalMulai)
                ->sum('jumlah_cuti'),

            4 => Cuti::where('pegawai_id', $pegawaiId)
                ->where('jenis_cuti_id', 4)
                ->where('status', 'selesai')
                ->where('tanggal_mulai', '<', $currentTanggalMulai)
                ->sum('jumlah_cuti'),
        ];

        // Generate QR Code
        $qrCodeUrl = url("/cuti/pengajuan/show/" . $cuti->id);
        $qrCodeImage = QrCode::format('svg')->size(100)->generate($qrCodeUrl);

        return view('cuti::pdf.index', compact('cuti', 'atasan', 'pimpinan', 'cutiCounts', 'qrCodeImage'));
    }
}
