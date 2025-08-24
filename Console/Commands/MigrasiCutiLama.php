<?php

namespace Modules\Cuti\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\CutiLogs;
use Modules\Cuti\Services\AtasanService;
use Modules\Jabatan\Entities\Pejabat as EntitiesPejabat;
use Modules\Kepegawaian\Entities\Pegawai;
use Carbon\Carbon;

class MigrasiCutiLama extends Command
{
    protected $signature = 'cuti:migrasi';
    protected $description = 'Migrasi data cuti lama dari file CSV';

    public function handle()
    {
        $file = storage_path('app/tbl_cuti.csv');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: $file");
            return;
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 1000, ';'); // baca header

        $baris = 1;
        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            $baris++;

            if (count($data) < 16) {
                $this->warn("[$baris] Kolom kurang dari 16. Lewat. Data: " . implode(';', $data));
                continue;
            }

            [
                $id_cuti, $nama_cuti, $nip_nik_cuti, $pangkat, $prodi_cuti,
                $nama_atasan, $nip_nik_atasan, $pangkat1, $jabatan_atasan,
                $awal_cuti, $akhir_cuti, $jenis_cuti, $ket_cuti,
                $user_id, $user_lvl, $status
            ] = $data;

            // Validasi pegawai
            $pegawai = Pegawai::where('noid', trim($user_id))->first();
            if (!$pegawai) {
                $this->warn("[$baris] Pegawai tidak ditemukan dengan noid: $user_id");
                continue;
            }

            // Default null
            $pejabatId = null;
            $pimpinanId = null;

            // Coba cari pejabat
            $pegawaiAtasan = Pegawai::where('nip', trim($nip_nik_atasan))->first();
            if ($pegawaiAtasan) {
                $pejabat = EntitiesPejabat::where('pegawai_id', $pegawaiAtasan->id)->first();
                if ($pejabat) {
                    $pejabatId = $pejabat->id;

                    $atasanService = new AtasanService();
                    $pimpinan = $atasanService->getAtasanPegawai($pegawaiAtasan->id);
                    if ($pimpinan) {
                        $pimpinanId = $pimpinan->id;
                    } else {
                        $this->warn("[$baris] Pimpinan tidak ditemukan, dibiarkan null.");
                    }
                } else {
                    $this->warn("[$baris] Pejabat tidak ditemukan, dibiarkan null.");
                }
            } else {
                $this->warn("[$baris] Pegawai atasan tidak ditemukan, dibiarkan null.");
            }

            // Konversi tanggal
            $tanggalMulai = $this->convertTanggal(trim($awal_cuti));
            $tanggalSelesai = $this->convertTanggal(trim($akhir_cuti));

            if (!$tanggalMulai || !$tanggalSelesai) {
                $this->warn("[$baris] Format tanggal tidak valid. Lewat.");
                continue;
            }

            // Validasi jenis_cuti_id
            if (!DB::table('jenis_cuti')->where('id', (int)$jenis_cuti)->exists()) {
                $this->warn("[$baris] Jenis cuti ID $jenis_cuti tidak ditemukan. Lewat.");
                continue;
            }

            $jumlahCuti = Carbon::parse($tanggalSelesai)->diffInDays(Carbon::parse($tanggalMulai)) + 1;
            DB::beginTransaction();
            try {
                $cuti = Cuti::create([
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'jumlah_cuti' => $jumlahCuti,
                    'keterangan' => trim($ket_cuti),
                    'status' => $this->mapStatus($status),
                    'pegawai_id' => $pegawai->id,
                    'pejabat_id' => $pejabatId,
                    'pimpinan_id' => $pimpinanId,
                    'jenis_cuti_id' => (int) $jenis_cuti,
                    'access_token' => Str::uuid()->toString(),
                ]);

                CutiLogs::create([
                    'cuti_id' => $cuti->id,
                    'status' => $this->mapStatus($status),
                    'updated_by' => $pegawai->id,
                ]);

                DB::commit();
                $this->info("[$baris] Migrasi cuti untuk {$pegawai->nama} berhasil.");
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("[$baris] Gagal migrasi: " . $e->getMessage());
            }
        }

        fclose($handle);
        $this->info("Migrasi selesai.");
    }

    private function convertTanggal($tanggal)
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $tanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapStatus($statusLama)
    {
        return match ((int) $statusLama) {
            0 => 'Diajukan',
            1 => 'Diproses',
            2 => 'Disetujui',
            3 => 'Dibatalkan',
            default => 'Diajukan',
        };
    }
}
