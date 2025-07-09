<?php

namespace Modules\Cuti\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Cuti\Entities\TimKerja;

class TimKerjaTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        TimKerja::firstOrCreate(
            ['id' => 1],
            [
                'unit_id' => null,
                'ketua_id' => 1, // pastikan ini valid
                'parent_id' => null
            ]
        );
    }
}
