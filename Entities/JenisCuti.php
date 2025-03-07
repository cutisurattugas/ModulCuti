<?php

namespace Modules\Cuti\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenisCuti extends Model
{
    use HasFactory;

    protected $table = 'jenis_cuti';
    protected $primaryKey = 'id';
    protected $fillable = ['nama_cuti', 'jumlah_cuti', 'deskripsi'];
}
