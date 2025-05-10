<?php

namespace Modules\Cuti\Services;

use Carbon\Carbon;
use Modules\Setting\Entities\Libur;

class HariKerjaService
{
    public function countHariKerja($startDate, $endDate)
    {
        // Ambil semua libur dalam rentang tanggal yang dipilih
        $liburDates = Libur::whereBetween('tanggal', [$startDate, $endDate])
            ->pluck('tanggal')
            ->toArray();

        $count = 0;
        $current = strtotime($startDate);
        $end = strtotime($endDate);

        while ($current <= $end) {
            $dayOfWeek = date('w', $current); // 0 = Minggu, 6 = Sabtu
            $currentDate = date('Y-m-d', $current);

            // Cek apakah tanggal adalah hari kerja (bukan sabtu/minggu dan bukan libur)
            if ($dayOfWeek != 0 && $dayOfWeek != 6 && !in_array($currentDate, $liburDates)) {
                $count++;
            }

            $current = strtotime('+1 day', $current);
        }

        return $count;
    }
}