<?php

namespace Modules\Cuti\Entities;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Pengaturan\Entities\Pegawai;

class CutiLogs extends Model
{
    use HasFactory;

    protected $table = 'cuti_logs';
    protected $primaryKey = 'id';
    protected $fillable = ['status', 'cuti_id', 'updated_by', 'updated_at'];

    public function cuti(){
        return $this->belongsTo(Cuti::class, 'cuti_id', 'id');
    }
    public function pegawai(){
        return $this->belongsTo(Pegawai::class, 'updated_by', 'username');
    }
}
