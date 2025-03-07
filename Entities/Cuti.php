<?php

namespace Modules\Cuti\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cuti extends Model
{
    use HasFactory;

    protected $table = 'cuti';
    protected $primaryKey = 'id';
    protected $fillable = ['tanggal_mulai', 'tanggal_selesai', 'keterangan', 'dok_pendukung', 'status', 'dok_cuti', 'jenis_cuti_id', 'user_id'];
    
    public function jenis_cuti(){
        return $this->belongsTo(JenisCuti::class, 'jenis_cuti_id', 'id');
    }
}
