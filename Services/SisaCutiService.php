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

        $pegawai = \Modules\Kepegawaian\Entities\Pegawai::findOrFail($pegawaiId);
        $nip = $pegawai->nip;

        // Ambil tahun pengangkatan berdasarkan jenis NIP
        if (strpos($nip, '.') !== false) {
            // Jenis 2: NIP seperti 2011.36.072
            $tahunPengangkatan = (int)substr($nip, 0, 4);
        } else {
            // Jenis 1: NIP seperti 198403052021212004
            $tahunPengangkatan = (int)substr($nip, 8, 4);
        }

        // Ambil default jatah cuti tahunan dari tabel jenis_cuti
        $jatahCutiTahunan = JenisCuti::find(1)?->jumlah_cuti ?? 12;

        // Hitung total cuti yang digunakan di tahun sekarang (hanya yang 'Selesai')
        $cutiTahunIni = Cuti::where('pegawai_id', $pegawaiId)
            ->where('jenis_cuti_id', 1)
            ->where('status', 'Selesai')
            ->whereYear('tanggal_mulai', $tahunSekarang)
            ->sum('jumlah_cuti');

        $carryOver = 0;

        if ($tahunPengangkatan < $tahunSekarang) {
            // Ambil cuti dari 3 tahun sebelumnya
            $totalSisaSebelumnya = 0;
            for ($i = 1; $i <= 3; $i++) {
                $tahunSebelumnya = $tahunSekarang - $i;

                $cutiTahunSebelumnya = Cuti::where('pegawai_id', $pegawaiId)
                    ->where('jenis_cuti_id', 1)
                    ->where('status', 'Selesai')
                    ->whereYear('tanggal_mulai', $tahunSebelumnya);

                if ($cutiTahunSebelumnya->exists()) {
                    $terpakai = $cutiTahunSebelumnya->sum('jumlah_cuti');
                    $sisa = max($jatahCutiTahunan - $terpakai, 0);
                    $totalSisaSebelumnya += $sisa;
                } else {
                    $totalSisaSebelumnya += $jatahCutiTahunan; // jika belum pernah cuti, dianggap penuh
                }
            }

            // Hitung carry over (maks setengah dari total sisa sebelumnya)
            $carryOver = floor($totalSisaSebelumnya / 2);
        }

        // Total jatah maksimal: jatah + carry over
        $totalJatah = min($jatahCutiTahunan + $carryOver, 24);

        // Sisa cuti = total jatah - terpakai tahun ini
        $sisaCuti = max($totalJatah - $cutiTahunIni, 0);

        return [
            'tahun' => $tahunSekarang,
            'tahun_pengangkatan' => $tahunPengangkatan,
            'jatah_tahun_ini' => $jatahCutiTahunan,
            'carry_over' => $carryOver,
            'total_jatah' => $totalJatah,
            'terpakai' => $cutiTahunIni,
            'sisa' => $sisaCuti,
        ];
    }
}
