<?php

namespace Modules\Cuti\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';
    protected $primaryKey = 'id';
    protected $fillable = ['nama'];

    public function timKerja()
    {
        return $this->hasMany(TimKerja::class);
    }
}
