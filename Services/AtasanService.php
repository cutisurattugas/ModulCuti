<?php

namespace Modules\Cuti\Services;

use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\Pejabat;
use Modules\Pengaturan\Entities\TimKerja;

class AtasanService
{
    public function getAtasanPegawai($pegawaiId)
    {
        $pegawai = Pegawai::find($pegawaiId);
        $keanggotaan = Anggota::where('pegawai_id', $pegawaiId)->get();

        if ($keanggotaan->isEmpty()) return null;

        // 1️⃣ Cek apakah pegawai adalah ketua di salah satu unit
        foreach ($keanggotaan as $anggota) {
            $tim = TimKerja::find($anggota->tim_kerja_id);

            if (
                strtolower($anggota->peran) === 'ketua' ||
                ($tim?->ketua && $tim->ketua->pegawai_id == $pegawaiId)
            ) {
                // Jika dia ketua, cari ketua dari unit di atasnya (satu level saja, tidak naik terus)
                $parentUnit = $tim?->parentUnit;

                if ($parentUnit) {
                    $ketua = Pejabat::find($parentUnit->ketua_id);
                    if ($ketua && $ketua->pegawai_id != $pegawaiId) {
                        return $ketua;
                    }
                }

                // Jika tidak ada parent atau tidak ketemu ketua, tidak usah naik lebih jauh
                return null;
            }
        }

        // 2️⃣ Jika bukan ketua, ambil ketua langsung dari unit kerja pertamanya saja
        $firstUnit = TimKerja::find($keanggotaan->first()->tim_kerja_id);

        if ($firstUnit && $firstUnit->ketua_id) {
            $ketua = Pejabat::find($firstUnit->ketua_id);
            if ($ketua && $ketua->pegawai_id != $pegawaiId) {
                return $ketua;
            }
        }

        return null;
    }
}
