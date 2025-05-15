<?php

namespace Modules\Cuti\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\JenisCuti;

class SisaCutiService
{
    function ensureCutiSisaTerbuat($pegawaiId)
    {
        $tahun_sekarang = Carbon::now()->year;

        // Jika sudah ada, tidak perlu buat ulang
        $sudahAda = DB::table('cuti_sisa')
            ->where('pegawai_id', $pegawaiId)
            ->where('tahun', $tahun_sekarang)
            ->exists();

        if ($sudahAda) return;

        // Ambil data cuti 3 tahun terakhir sebelum tahun ini
        $tahun_batas = $tahun_sekarang - 3;

        $cuti_sebelumnya = DB::table('cuti_sisa')
            ->where('pegawai_id', $pegawaiId)
            ->where('tahun', '>', $tahun_batas)
            ->where('tahun', '<', $tahun_sekarang)
            ->orderBy('tahun')
            ->get();

        $total_sisa_cuti_awal = 0;

        foreach ($cuti_sebelumnya as $cuti) {
            // Hanya cuti_awal yang dihitung
            $sisa = max($cuti->cuti_awal, 0); // karena cuti_awal sudah berkurang saat digunakan
            $total_sisa_cuti_awal += $sisa;
        }

        // Hitung cuti dibawa: setengah dari total sisa cuti_awal
        $cuti_dibawa = floor($total_sisa_cuti_awal / 2);

        // Maksimal cuti total 24
        $cuti_awal = 12;
        if ($cuti_awal + $cuti_dibawa > 24) {
            $cuti_dibawa = 24 - $cuti_awal;
        }

        DB::table('cuti_sisa')->insert([
            'pegawai_id' => $pegawaiId,
            'tahun' => $tahun_sekarang,
            'cuti_awal' => $cuti_awal,
            'cuti_dibawa' => $cuti_dibawa,
            'cuti_digunakan' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
