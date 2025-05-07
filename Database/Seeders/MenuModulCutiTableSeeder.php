<?php

namespace Modules\Cuti\Database\Seeders;

use App\Models\Core\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class MenuModulCutiTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        Menu::where('modul', 'Cuti')->delete();
        $menu = Menu::create([
            'modul' => 'Cuti',
            'label' => 'Cuti',
            'url' => 'cuti',
            'can' => serialize(['admin']),
            'icon' => 'fas fa-file-alt',
            'urut' => 1,
            'parent_id' => 0,
            'active' => serialize(['cuti']),
        ]);
        if ($menu) {
            Menu::create([
                'modul' => 'Cuti',
                'label' => 'Pengajuan Cuti',
                'url' => 'cuti/pengajuan',
                'can' => serialize(['admin']),
                'icon' => 'far fa-circle',
                'urut' => 1,
                'parent_id' => $menu->id,
                'active' => serialize(['cuti/pengajuan', 'cuti/pengajuan*']),
            ]);
        }
        if ($menu) {
            Menu::create([
                'modul' => 'Cuti',
                'label' => 'Jenis Cuti',
                'url' => 'cuti/jenis',
                'can' => serialize(['admin']),
                'icon' => 'far fa-circle',
                'urut' => 2,
                'parent_id' => $menu->id,
                'active' => serialize(['cuti/jenis', 'cuti/jenis*']),
            ]);
        }
    }
}
