<?php

namespace Modules\Cuti\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\JenisCuti;

class SisaCutiService
{
    function ensureCutiSisaTerbuat($pegawai_username)
    {
        $tahun_sekarang = Carbon::now()->year;

        // Jika sudah ada, tidak perlu buat ulang
        $sudahAda = DB::table('cuti_sisa')
            ->where('pegawai_username', $pegawai_username)
            ->where('tahun', $tahun_sekarang)
            ->exists();

        if ($sudahAda) return;

        // Ambil data cuti dari 2 tahun ke belakang (karena tahun sebelumnya bisa membawa akumulasi dari tahun sebelumnya juga)
        $tahun_batas = $tahun_sekarang - 3;
        $cuti_sebelumnya = DB::table('cuti_sisa')
            ->where('pegawai_username', $pegawai_username)
            ->where('tahun', '>', $tahun_batas)
            ->where('tahun', '<', $tahun_sekarang)
            ->orderBy('tahun')
            ->get();

        $cuti_dibawa = 0;

        foreach ($cuti_sebelumnya as $cuti) {
            // Hitung sisa cuti tahun tersebut
            $sisa = max($cuti->cuti_awal - $cuti->cuti_digunakan, 0);
            $cuti_dibawa += floor($sisa / 2); // hanya cuti_awal yang dibagi
        }

        // Batasi total cuti tidak lebih dari 24
        $cuti_awal = 12;
        if ($cuti_dibawa + $cuti_awal > 24) {
            $cuti_dibawa = 24 - $cuti_awal;
        }
        
        DB::table('cuti_sisa')->insert([
            'pegawai_username' => $pegawai_username,
            'tahun' => $tahun_sekarang,
            'cuti_awal' => 12,
            'cuti_dibawa' => $cuti_dibawa,
            'cuti_digunakan' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
