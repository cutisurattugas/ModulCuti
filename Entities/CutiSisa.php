<?php

namespace Modules\Cuti\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Pengaturan\Entities\Pegawai;

class CutiSisa extends Model
{
    use HasFactory;

    protected $table = 'cuti_sisa';
    protected $primaryKey = 'id';
    protected $fillable = ['pegawai_username', 'tahun', 'sisa_cuti'];

    public function pegawai(){
        return $this->belongsTo(Pegawai::class, 'pegawai_username', 'username');
    }
}
