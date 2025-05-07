<?php

namespace Modules\Cuti\Services;

use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\Pejabat;
use Modules\Pengaturan\Entities\TimKerja;

class AtasanService
{
    public function getAtasanPegawai($pegawaiUsername)
    {
        $pegawai = Pegawai::find($pegawaiUsername);
        $keanggotaan = Anggota::where('pegawai_username', $pegawaiUsername)->get();

        if ($keanggotaan->isEmpty()) return null;

        // 1️⃣ Cek apakah pegawai adalah ketua di salah satu unit
        foreach ($keanggotaan as $anggota) {
            $tim = TimKerja::find($anggota->tim_kerja_id);
            if (
                strtolower($anggota->peran) === 'ketua' ||
                ($tim?->ketua && $tim->ketua->pegawai_username == $pegawaiUsername)
            ) {
                // Jika dia ketua, naik ke atas langsung (Wadir atau Direktur)
                $parentUnit = $tim?->parentUnit;

                while ($parentUnit) {
                    $ketua = Pejabat::find($parentUnit->ketua_id);
                    if ($ketua && $ketua->pegawai_username != $pegawaiUsername) {
                        return $ketua;
                    }
                    $parentUnit = $parentUnit->parentUnit;
                }
            }
        }

        // 2️⃣ Jika bukan ketua di mana pun, ambil salah satu unit anggota dan naik satu level (dari prodi ke jurusan)
        $firstUnit = TimKerja::find($keanggotaan->first()->tim_kerja_id);
        $tim = $firstUnit?->parentUnit;

        while ($tim) {
            $ketua = Pejabat::find($tim->ketua_id);
            if ($ketua && $ketua->pegawai_username != $pegawaiUsername) {
                return $ketua;
            }
            $tim = $tim->parentUnit; // naik terus kalau belum ketemu
        }

        return null;
    }
}
