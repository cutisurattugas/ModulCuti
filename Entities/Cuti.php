<?php

namespace Modules\Cuti\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Pengaturan\Entities\Anggota;
use Modules\Pengaturan\Entities\Pegawai;
use Modules\Pengaturan\Entities\Pejabat;
use Modules\Pengaturan\Entities\TimKerja;
use Modules\Pengaturan\Entities\Unit;

class Cuti extends Model
{
    use HasFactory;

    protected $table = 'cuti';
    protected $primaryKey = 'id';
    protected $fillable = ['tanggal_mulai', 'tanggal_selesai', 'keterangan', 'dok_pendukung', 'status', 'dok_cuti', 'jenis_cuti_id', 'user_id'];
    
    public function jenis_cuti(){
        return $this->belongsTo(JenisCuti::class, 'jenis_cuti_id', 'id');
    }

    public function pegawai(){
        return $this->belongsTo(Pegawai::class);
    }

    public function pejabat(){
        return $this->belongsTo(Pejabat::class);
    }

    public function tim_kerja(){
        return $this->belongsTo(TimKerja::class);
    }

    public function anggota_tim_kerja(){
        return $this->belongsTo(Anggota::class);
    }

    public function unit(){
        return $this->belongsTo(Unit::class);
    }
}
