<?php

namespace Modules\Cuti\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Cuti\Entities\JenisCuti;

class JenisCutiTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $jeniCuti = [
            ['nama_cuti' => 'Cuti Tahunan', 'jumlah_cuti' => '12', 'deskripsi' => 'PP 11 Tahun 2017 (maksimal 12 hari kerja dalam satu
tahun)'],
            ['nama_cuti' => 'Cuti Alasan Penting', 'jumlah_cuti' => '1', 'deskripsi' => ''],
            ['nama_cuti' => 'Cuti Melahirkan', 'jumlah_cuti' => '30', 'deskripsi' => ''],
            ['nama_cuti' => 'Cuti Besar', 'jumlah_cuti' => '12', 'deskripsi'=>''],
        ];

        foreach ($jeniCuti as $jenis) {
            JenisCuti::create($jenis);
        }
    }
}
