<?php

namespace Modules\Cuti\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Cuti\Entities\CutiSisa;
use Modules\Pengaturan\Entities\Pegawai;

class CutiSisaSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $tahun = now()->year;
        $pegawaiList = Pegawai::all();

        foreach ($pegawaiList as $pegawai) {
            CutiSisa::firstOrCreate([
                'pegawai_username' => $pegawai->username,
                'tahun' => $tahun,
            ], [
                'sisa_cuti' => 12,
            ]);
        }
    }
}
