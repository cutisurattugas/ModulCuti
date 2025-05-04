<?php

namespace Modules\Cuti\Services;

use Carbon\Carbon;
use Modules\Cuti\Entities\Cuti;
use Modules\Cuti\Entities\JenisCuti;

class SisaCutiService
{
    public function hitung($user_id, $jenis_cuti_id = 1)
    {
        $jatah_cuti = JenisCuti::find($jenis_cuti_id)?->jumlah_cuti ?? 0;

        $cuti_diambil = Cuti::where('user_id', $user_id)
            ->where('jenis_cuti_id', $jenis_cuti_id)
            ->where('status', 'Disetujui')
            ->get();

        $total_cuti_diambil = $cuti_diambil->reduce(function ($carry, $cuti) {
            $mulai = Carbon::parse($cuti->tanggal_mulai);
            $selesai = Carbon::parse($cuti->tanggal_selesai);
            return $carry + $mulai->diffInDays($selesai) + 1;
        }, 0);

        return $jatah_cuti - $total_cuti_diambil;
    }
}
