<?php

namespace Modules\Cuti\Entities;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CutiLogs extends Model
{
    use HasFactory;

    protected $table = 'cuti_logs';
    protected $primaryKey = 'id';
    protected $fillable = ['status', 'cuti_id', 'updated_by', 'updated_at'];

    public function cuti(){
        return $this->belongsTo(Cuti::class, 'cuti_id', 'id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
