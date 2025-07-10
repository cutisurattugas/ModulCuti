<?php

namespace Modules\Cuti\Entities;

use App\Models\Core\User;
use Modules\Kepegawaian\Entities\Pegawai as BasePegawai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Jabatan\Entities\Pejabat;
use Modules\SuratTugas\Entities\AnggotaSuratTugas;
use Modules\SuratTugas\Entities\SuratTugas;

class Pegawai extends BasePegawai
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $guarded = ['id'];
    
    public function timKerja()
    {
        return $this->hasMany(TimKerja::class, 'tim_kerja_anggota', 'pegawai_username')->withPivot('peran');
    }

    public function timKerjaAnggota()
    {
        return $this->belongsToMany(TimKerja::class, 'tim_kerja_anggota', 'pegawai_id', 'tim_kerja_id')->withPivot('peran');
    }

    public function anggota(){
        return $this->hasOne(Anggota::class, 'pegawai_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function pejabat()
    {
        return $this->hasOne(Pejabat::class);
    }

    public function rencanakerja(){
        return $this->hasMany(RencanaKerja::class, 'pegawai_id', 'id');
    }

    public function timKerjaKetua()
    {
        return $this->belongsToMany(TimKerja::class, 'tim_kerja_anggota')
            ->wherePivot('peran', 'Ketua');
    }

    public function bawahan()
    {
        return $this->timKerjaKetua()
            ->with('anggota')
            ->get()
            ->pluck('anggota')
            ->flatten()
            ->unique('id')
            ->values();
    }

    public function cascading()
    {
        return $this->hasMany(Cascading::class, 'pegawai_id', 'id');
    }
    public function cuti()
    {
        return $this->hasMany(Cuti::class, 'pegawai_id');
    }

    public function suratTugas()
    {
        return $this->hasManyThrough(SuratTugas::class, AnggotaSuratTugas::class, 'pegawai_id', 'id', 'id', 'surat_tugas_id');
    }
}
