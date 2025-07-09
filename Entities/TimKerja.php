<?php

namespace Modules\Cuti\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Jabatan\Entities\Pejabat;
use Modules\Kepegawaian\Entities\Pegawai;

class TimKerja extends Model
{
    use HasFactory;

    protected $table = 'tim_kerja';
    protected $primaryKey = 'id';
    protected $fillable = ['unit_id', 'parent_id', 'ketua_id'];

    public function parentUnit()
    {
        return $this->belongsTo(TimKerja::class, 'parent_id');
    }

    public function subUnits()
    {
        return $this->hasMany(TimKerja::class, 'parent_id');
    }

    public function ketua()
    {
        return $this->belongsTo(Pejabat::class, 'ketua_id');
    }

    public function anggota()
    {
        return $this->belongsToMany(Pegawai::class, 'tim_kerja_anggota', 'tim_kerja_id', 'pegawai_id')->wherePivot('peran', 'Anggota');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('ketua', 'childrenRecursive');
    }

    public function allRelatedUnits()
    {
        // Ambil sub-unit dan parent unit
        return $this->children->merge($this->parent ? collect([$this->parent]) : collect());
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
