<?php

namespace Modules\Cuti\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\JenisCuti;

class SisaCutiService
{
    public function hitungSisaCuti($pegawaiId, $tahun = null)
    {
        $tahunSekarang = $tahun ?? Carbon::now()->year;

        // Ambil default jatah cuti tahunan dari tabel jenis_cuti
        $jatahCutiTahunan = JenisCuti::find(1)?->jumlah_cuti ?? 12;

        // Hitung total cuti yang digunakan di tahun sekarang (hanya yang 'Selesai')
        $cutiTahunIni = Cuti::where('pegawai_id', $pegawaiId)
            ->where('jenis_cuti_id', 1)
            ->where('status', 'Selesai')
            ->whereYear('tanggal_mulai', $tahunSekarang)
            ->sum('jumlah_cuti');

        // Ambil cuti dari 3 tahun sebelumnya untuk carry over
        $totalSisaSebelumnya = 0;
        for ($i = 1; $i <= 3; $i++) {
            $tahunSebelumnya = $tahunSekarang - $i;

            $cutiTahunSebelumnya = Cuti::where('pegawai_id', $pegawaiId)
                ->where('jenis_cuti_id', 1)
                ->where('status', 'Selesai')
                ->whereYear('tanggal_mulai', $tahunSebelumnya);

            if ($cutiTahunSebelumnya->exists()) {
                $cutiTerpakai = $cutiTahunSebelumnya->sum('jumlah_cuti');
                $sisa = max($jatahCutiTahunan - $cutiTerpakai, 0);
                $totalSisaSebelumnya += $sisa;
            }
        }

        // Hitung cuti yang bisa dibawa ke tahun ini (dibagi dua)
        $cutiDibawa = floor($totalSisaSebelumnya / 2);

        // Total cuti yang dimiliki tahun ini
        $totalJatah = $jatahCutiTahunan + $cutiDibawa;

        // Batasi maksimal total cuti (misalnya 24 hari)
        if ($totalJatah > 24) {
            $totalJatah = 24;
        }

        // Hitung sisa cuti aktual tahun ini
        $sisaCuti = max($totalJatah - $cutiTahunIni, 0);

        return [
            'tahun' => $tahunSekarang,
            'jatah_tahun_ini' => $jatahCutiTahunan,
            'carry_over' => $cutiDibawa,
            'total_jatah' => $totalJatah,
            'terpakai' => $cutiTahunIni,
            'sisa' => $sisaCuti,
        ];
    }
}
