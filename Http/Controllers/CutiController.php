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
use Modules\Cuti\Services\WhatsappService;
use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\Pejabat;
use Modules\Pengaturan\Entities\TimKerja;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Cuti\Services\FonnteService;

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

        if ($role === 'admin') {
            $cuti = Cuti::latest()->get();
        } elseif ($role === 'direktur') {
            // Direktur: bisa sebagai atasan langsung dan pimpinan
            $cutiSebagaiPimpinan = Cuti::where('pimpinan_id', $pejabat_id)
                ->whereHas('logs', fn($q) => $q->where('status', 'Telah diteruskan ke pimpinan'));

            $cutiSebagaiAtasan = Cuti::where('pejabat_id', $pejabat_id)
                ->whereHas('logs', fn($q) => $q->where('status', 'Telah diteruskan ke atasan'));

            $cuti = $cutiSebagaiPimpinan
                ->union($cutiSebagaiAtasan)
                ->latest('created_at')
                ->get();
        } elseif (in_array($role, ['kajur', 'wadir1', 'wadir2', 'wadir3'])) {
            // Cuti yang diajukan ke saya sebagai atasan langsung
            $cutiSebagaiAtasan = Cuti::where('pejabat_id', $pejabat_id)
                ->whereHas('logs', fn($q) => $q->where('status', 'Telah diteruskan ke atasan'));

            // Cuti yang diajukan ke saya sebagai pimpinan
            $cutiSebagaiPimpinan = Cuti::where('pimpinan_id', $pejabat_id)
                ->whereHas('logs', fn($q) => $q->where('status', 'Telah diteruskan ke pimpinan'));

            $cuti_anggota = $cutiSebagaiAtasan
                ->union($cutiSebagaiPimpinan)
                ->latest('created_at')
                ->get();

            // Tetap tampilkan cuti milik pribadi
            $cuti_pribadi = Cuti::where('pegawai_id', $pegawai_id)->latest()->get();
        } elseif (in_array($role, ['pegawai', 'dosen'])) {
            // Pegawai biasa hanya melihat cutinya sendiri
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
        $pegawai = Pegawai::where('username', auth()->user()->username)->first();
        if ($pegawai->jenis_kelamin === 'L') {
            // Laki-laki tidak bisa mengambil cuti melahirkan (id = 3)
            $jenis_cuti = JenisCuti::where('id', '!=', 3)->get();
        } elseif ($pegawai->jenis_kelamin === 'P') {
            // Perempuan tidak bisa mengambil cuti pendampingan istri melahirkan (id = 5)
            $jenis_cuti = JenisCuti::where('id', '!=', 5)->get();
        }

        $anggota = Anggota::where('pegawai_id', $pegawai->id)->first();
        $tim = TimKerja::find($anggota->tim_kerja_id ?? null); // jika ada

        // Gunakan AtasanService
        $atasanService = new AtasanService();
        $ketua = $atasanService->getAtasanPegawai($pegawai->id);

        // Hitung sisa cuti
        $sisaCutiService = new SisaCutiService();
        $getCutiSisa = $sisaCutiService->hitungSisaCuti($pegawai->id);
        $sisa_cuti = $getCutiSisa['sisa'];

        // Filter: jika sisa cuti 0, hilangkan jenis cuti dengan id = 1
        if ($sisa_cuti == 0) {
            $jenis_cuti = $jenis_cuti->reject(function ($item) {
                return $item->id == 1;
            });
        }

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
        $username_login = auth()->user()->username;
        $username_pegawai = Pegawai::where('username', $username_login)->first()->id;

        // Validasi inputan
        $request->validate([
            'pegawai_id' => 'required|exists:pegawais,id',
            'pejabat_id' => 'required|exists:pejabats,id',
            'jenis_cuti' => 'required|exists:jenis_cuti,id',
            'rentang_cuti' => 'required',
            'dok_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Get data pimpinan
        if (in_array(auth()->user()->role_aktif, ['pegawai', 'dosen'])) {
            $atasan = Pejabat::firstWhere('id', $request->pejabat_id);
            $idPegawaiAtasan = Pegawai::firstWhere('id', $atasan->pegawai_id)->id;
            $atasanService = new AtasanService();
            $pimpinan = $atasanService->getAtasanPegawai($idPegawaiAtasan);
            $pimpinanId = $pimpinan->id;
        } elseif (in_array(auth()->user()->role_aktif, ['wadir1', 'wadir2', 'wadir3', 'kaunit'])) {
            $atasanService = new AtasanService();
            $pimpinan = $atasanService->getAtasanPegawai($request->pegawai_id);
            $pimpinanId = $pimpinan->id;
        }

        // Explode data rentang cuti
        $tanggal = $request->input('rentang_cuti');
        $tanggalRange = explode(' to ', $tanggal);

        if (count($tanggalRange) == 2) {
            // Jika range tanggal (multi hari)
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[1];
        } elseif (count($tanggalRange) == 1) {
            // Jika hanya 1 hari
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[0];
        } else {
            return redirect()->back()->withInput()->with('error', 'Format rentang cuti tidak valid.');
        }

        // Hitung jumlah hari kerja
        $hariKerjaService = new HariKerjaService();
        $jumlah_cuti = $hariKerjaService->countHariKerja($awal_cuti, $akhir_cuti);

        // Cek apakah ada hari kerja
        if ($jumlah_cuti <= 0) {
            return redirect()->back()->withInput()->with('error', 'Rentang cuti tidak mencakup hari kerja.');
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

            // Generate access token
            $uuid = Str::uuid()->toString();
            $access_token = $uuid;

            // Insert data ke tabel cuti
            $data = Cuti::create([
                'tanggal_mulai' => $awal_cuti,
                'tanggal_selesai' => $akhir_cuti,
                'jumlah_cuti' => $jumlah_cuti,
                'keterangan' => $request->keterangan,
                'access_token' => $access_token,
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
                'updated_by' => $username_pegawai,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            // Send Whatsapp via fonnte
            $fonnte = new FonnteService();
            $target = '6287785390241';
            $message = Pegawai::where('id', $username_pegawai)->first()->nama;
            $response = $fonnte->sendText($target, $message . ' mengajukan cuti', [
                'typing' => true,
                'delay' => 2,
                'countryCode' => '62',
            ]);

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
    public function show($access_token)
    {
        $user_login = auth()->user();

        // Ambil ID pejabat login (jika operator)
        $id_pejabat_login = null;
        if (in_array($user_login->role_aktif, ['direktur', 'kajur', 'wadir1', 'wadir2', 'wadir3'])) {
            $username_user_login = $user_login->username;
            $pegawai_login = Pegawai::firstWhere('username', $username_user_login);
            $pejabat_login = Pejabat::firstWhere('pegawai_id', $pegawai_login->id);
            $id_pejabat_login = optional($pejabat_login)->id;
        }
        $cuti = Cuti::where('access_token', $access_token)->first();

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->id);

        // Ambil data sisa cuti tahun ini
        $sisaCutiService = new SisaCutiService();
        $getCutiSisa = $sisaCutiService->hitungSisaCuti($cuti->pegawai_id);
        $sisa_cuti = $getCutiSisa['sisa'];

        // Tambahan data lain
        $jenis_cuti = JenisCuti::all();
        $anggota = Anggota::where('pegawai_id', $cuti->pegawai->id)->first();
        $tim = TimKerja::find(optional($anggota)->tim_kerja_id);

        $isPemohon = $cuti->pegawai->username === $user_login->username;
        $isPejabat = $id_pejabat_login === $cuti->pejabat_id;
        $isPimpinan = $id_pejabat_login === $cuti->pimpinan_id;

        return view('cuti::pengajuan_cuti.show', compact(
            'jenis_cuti',
            'cuti',
            'anggota',
            'tim',
            'pejabat',
            'id_pejabat_login',
            'sisa_cuti',
            'isPemohon',
            'isPejabat',
            'isPimpinan'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($access_token)
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
        $cuti = Cuti::where('access_token', $access_token)->first();

        // Ambil data atasan yang benar via service
        $atasanService = new AtasanService();
        $pejabat = $atasanService->getAtasanPegawai($cuti->pegawai->id);

        // Ambil data sisa cuti tahun ini
        $sisaCutiService = new SisaCutiService();
        $getCutiSisa = $sisaCutiService->hitungSisaCuti($cuti->pegawai_id);
        $sisa_cuti = $getCutiSisa['sisa'];

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
    public function update(Request $request, $access_token)
    {
        $username_login = auth()->user()->username;
        $username_pegawai = Pegawai::where('username', $username_login)->first()->id;

        // Validasi inputan
        $request->validate([
            'jenis_cuti' => 'required|exists:jenis_cuti,id',
            'rentang_cuti' => 'required',
            'dok_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'keterangan' => 'required',
        ]);

        $cuti = Cuti::where('access_token', $access_token)->first();

        // Explode rentang tanggal
        $tanggal = $request->input('rentang_cuti');
        $tanggalRange = explode(' to ', $tanggal);

        if (count($tanggalRange) == 2) {
            // Jika range tanggal (multi hari)
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[1];
        } elseif (count($tanggalRange) == 1) {
            // Jika hanya 1 hari
            $awal_cuti = $tanggalRange[0];
            $akhir_cuti = $tanggalRange[0];
        } else {
            return redirect()->back()->withInput()->with('danger', 'Format rentang cuti tidak valid.');
        }

        // Hitung jumlah hari kerja
        $hariKerjaService = new HariKerjaService();
        $jumlah_cuti = $hariKerjaService->countHariKerja($awal_cuti, $akhir_cuti);

        // Cek apakah ada hari kerja
        if ($jumlah_cuti <= 0) {
            return redirect()->back()->withInput()->with('danger', 'Rentang cuti tidak mencakup hari kerja.');
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
                'updated_by' => $username_pegawai,
            ]);

            DB::commit();
            return redirect()->route('cuti.index')->with('success', 'Pengajuan cuti berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Terjadi kesalahan saat memperbarui cuti.');
        }
    }


    // Update status cuti oleh admin
    public function approvedByKepegawaian(Request $request, $access_token)
    {

        // Pastikan hanya unit kepegawaian (admin) yang bisa menyetujui
        if (!auth()->user()->role_aktif === 'admin') {
            return redirect()->route('cuti.index')->with('danger', 'Anda tidak memiliki hak akses untuk menyetujui cuti.');
        }

        // Ambil data cuti berdasarkan ID
        $cuti = Cuti::where('access_token', $access_token)->first();

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Mulai transaksi DB
        DB::beginTransaction();

        try {

            $username_login = auth()->user()->username;
            $username_pegawai = Pegawai::where('username', $username_login)->first()->id;

            // Perbarui status menjadi "Disetujui Unit Kepegawaian"
            $cuti->status = 'Diproses';
            $cuti->catatan_kepegawaian = $request->catatan_kepegawaian;
            $cuti->save();

            // Tambahkan log status ke tabel cuti_logs
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah diteruskan ke atasan',
                'updated_by' => $username_pegawai,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            // $waService = new WhatsappService();
            // $username = $cuti->pegawai->username;
            // $message = "Cuti anda telah diteruskan ke atasan.";
            // $result = $waService->sendMessage($username, $message);

            // Send Whatsapp via Fonnte
            $fonnte = new FonnteService();
            $target = '6287785390241';
            $message = Pegawai::where('id', $username_pegawai)->first()->nama;
            $response = $fonnte->sendText($target, $message . ' (Kepegawaian) meneruskan pengajuan ke atasan', [
                'typing' => true,
                'delay' => 2,
                'countryCode' => '62',
            ]);

            // Redirect ke halaman pengajuan cuti
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil diteruskan ke atasan.');
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();

            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal disetujui karena: ' . $th->getMessage());
        }
    }

    public function approvedByAtasan(Request $request, $access_token)
    {
        // Pastikan hanya unit ketua jurusan yang bisa menyetujui
        if (!auth()->user()->role_aktif === 'kajur') {
            return redirect()->route('cuti.index')->with('danger', 'Anda tidak memiliki hak akses untuk menyetujui cuti.');
        }

        // Ambil data cuti berdasarkan ID
        $cuti = Cuti::where('access_token', $access_token)->first();


        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Mulai transaksi DB
        DB::beginTransaction();

        try {

            $username_login = auth()->user()->username;
            $username_pegawai = Pegawai::where('username', $username_login)->first()->id;

            // Perbarui status menjadi "Disetujui Unit Kepegawaian"
            $cuti->status = 'Diproses';
            $cuti->tanggal_disetujui_pejabat = now();
            $cuti->save();

            // Tambahkan log status ke tabel cuti_logs
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah diteruskan ke pimpinan',
                'updated_by' => $username_pegawai,
            ]);

            // Commit transaksi jika tidak ada error
            DB::commit();

            // $waService = new WhatsappService();
            // $username = $cuti->pegawai->username;
            // $message = "Cuti anda telah disetujui atasan.";
            // $result = $waService->sendMessage($username, $message);

            // Send Whatsapp via Fonnte
            $fonnte = new FonnteService();
            $target = '6287785390241';
            $message = Pegawai::where('id', $username_pegawai)->first()->nama;
            $response = $fonnte->sendText($target, $message . ' (atasan) telah menyetujui', [
                'typing' => true,
                'delay' => 2,
                'countryCode' => '62',
            ]);

            // Redirect ke halaman pengajuan cuti
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil diteruskan ke pimpinan.');
        } catch (\Throwable $th) {
            // Rollback jika terjadi error
            DB::rollBack();

            return redirect()->route('cuti.index')->with('danger', 'Cuti gagal disetujui karena: ' . $th->getMessage());
        }
    }

    public function approvedByPimpinan(Request $request, $access_token)
    {
        $cuti = Cuti::where('access_token', $access_token)->first();

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        DB::beginTransaction();

        try {
            $username_login = auth()->user()->username;
            $pegawai_penyetuju = Pegawai::where('username', $username_login)->first();
            $username_pegawai = optional($pegawai_penyetuju)->id;

            $cuti->status = 'Disetujui';
            $cuti->tanggal_disetujui_pimpinan = now();

            // ⏺ Tambahan: cek apakah pemohon adalah wakil direktur
            $role_pemohon = optional($cuti->pegawai->user)->role_aktif;
            $isWakilDirektur = in_array($role_pemohon, ['wadir1', 'wadir2', 'wadir3']);

            if ($isWakilDirektur) {
                // Tandai juga sudah disetujui oleh atasan
                $cuti->tanggal_disetujui_pejabat = now();

                // Tambahkan log seolah sudah melewati proses atasan
                CutiLogs::create([
                    'cuti_id' => $cuti->id,
                    'status' => 'Telah diteruskan ke pimpinan',
                    'updated_by' => $username_pegawai,
                ]);
            }

            $cuti->save();

            // Hitung jatah cuti jika perlu
            $jumlah_hari = Carbon::parse($cuti->tanggal_selesai)->diffInDays(Carbon::parse($cuti->tanggal_mulai)) + 1;
            $tahun = Carbon::parse($cuti->tanggal_mulai)->year;

            // Log persetujuan pimpinan
            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Telah disetujui pimpinan',
                'updated_by' => $username_pegawai,
            ]);

            // Kirim WA (jika aktif)
            $fonnte = new FonnteService();
            $target = '6287785390241';
            $message = $pegawai_penyetuju->nama;
            $fonnte->sendText($target, $message . ' (pimpinan) telah menyetujui', [
                'typing' => true,
                'delay' => 2,
                'countryCode' => '62',
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
    public function cancelCuti(Request $request, $access_token)
    {
        // Validasi input alasan
        $request->validate([
            'alasan_batal' => 'required|string|max:255',
        ]);

        $cuti = Cuti::where('access_token', $access_token)->first();

        if (!$cuti) {
            return redirect()->route('cuti.index')->with('danger', 'Cuti tidak ditemukan.');
        }

        // Tidak boleh batalkan cuti yang sudah disetujui
        if ($cuti->status === 'Disetujui') {
            return redirect()->route('cuti.index')->with('danger', 'Cuti yang sudah disetujui tidak bisa dibatalkan.');
        }

        DB::beginTransaction();
        try {

            $username_login = auth()->user()->username;
            $username_pegawai = Pegawai::where('username', $username_login)->first()->id;

            $cuti->status = 'Dibatalkan';
            $cuti->alasan_batal = $request->alasan_batal;
            $cuti->save();

            CutiLogs::create([
                'cuti_id' => $cuti->id,
                'status' => 'Dibatalkan',
                'catatan' => $request->alasan_batal,
                'updated_by' => $username_pegawai,
            ]);

            // $waService = new WhatsappService();
            // $username = $cuti->pegawai->username;
            // $message = "Cuti anda telah telah dibatalkan";
            // $result = $waService->sendMessage($username, $message);

            // Send Whatsapp via Fonnte
            $fonnte = new FonnteService();
            $target = '6287785390241';
            $message = Pegawai::where('id', $username_pegawai)->first()->nama;
            $response = $fonnte->sendText($target, $message . ' telah membatalkan pengajuan cuti anda dengan alasan ' . $request->alasan_batal, [
                'typing' => true,
                'delay' => 2,
                'countryCode' => '62',
            ]);

            DB::commit();
            return redirect()->route('cuti.index')->with('success', 'Cuti berhasil dibatalkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Gagal membatalkan cuti: ' . $th->getMessage());
        }
    }

    public function printCuti($access_token)
    {
        $cuti = Cuti::where('access_token', $access_token)->first();

        // Ambil data atasan
        $atasan = Pejabat::firstWhere('id', $cuti->pejabat_id);

        // Ambil data pimpinan
        $pimpinan = Pejabat::firstWhere('id', $cuti->pimpinan_id);

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

        DB::beginTransaction();
        try {
            if ($cuti->status === 'Disetujui') {
                $username_login = auth()->user()->username;
                $username_pegawai = Pegawai::where('username', $username_login)->first()->id;

                // Ubah status ke "Selesai"
                $cuti->status = 'Selesai';
                $cuti->save();

                // Simpan log
                CutiLogs::create([
                    'cuti_id' => $cuti->id,
                    'status' => 'Selesai',
                    'updated_by' => $username_pegawai,
                ]);

                DB::commit();

                // Kirim WA
                // $waService = new WhatsappService();
                // $username = $cuti->pegawai->username;
                // $message = "Cuti anda telah selesai di proses";
                // $waService->sendMessage($username, $message);

                // Send Whatsapp via Fonnte
                $fonnte = new FonnteService();
                $target = '6287785390241';
                $message = 'Pengajuan Cuti anda telah selesai';
                $response = $fonnte->sendText($target, $message, [
                    'typing' => true,
                    'delay' => 2,
                    'countryCode' => '62',
                ]);
            }

            // Jika status sudah "Selesai", atau baru saja diubah ke "Selesai", tetap buat QR dan tampilkan PDF
            if ($cuti->status === 'Selesai') {
                $qrCodeUrl = url("/tracking-cuti/" . $cuti->access_token);
                $qrCodeImage = QrCode::format('svg')->size(100)->generate($qrCodeUrl);

                return view('cuti::pdf.index', compact('cuti', 'atasan', 'pimpinan', 'cutiCounts', 'qrCodeImage'));
            }

            // Jika status selain "Disetujui" atau "Selesai", kembalikan
            DB::rollBack();
            return redirect()->back()->with('danger', 'Status cuti tidak valid untuk diproses.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('cuti.index')->with('danger', 'Gagal menyelesaikan cuti: ' . $th->getMessage());
        }
    }

    public function scanCuti($access_token)
    {
        $cuti = Cuti::where('access_token', $access_token)->first();
        $logs = CutiLogs::where('cuti_id', $cuti->id)->get();
        return view('cuti::pengajuan_cuti.scan', compact('cuti', 'logs'));
    }
}
