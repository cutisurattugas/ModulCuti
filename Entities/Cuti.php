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
    protected $fillable = ['tanggal_mulai', 'tanggal_selesai', 'keterangan', 'catatan_kepegawaian','alasan_batal', 'dok_pendukung', 'status', 'dok_cuti', 'tanggal_disetujui_pejabat', 'tanggal_disetujui_pimpinan', 'pegawai_id', 'pejabat_id', 'pimpinan_id', 'tim_kerja_id', 'unit_id', 'jenis_cuti_id', 'user_id'];

    public function jenis_cuti()
    {
        return $this->belongsTo(JenisCuti::class, 'jenis_cuti_id', 'id');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id');
    }

    public function pejabat()
    {
        return $this->belongsTo(Pejabat::class, 'pejabat_id', 'id');
    }

    public function tim_kerja()
    {
        return $this->belongsTo(TimKerja::class, 'tim_kerja_id', 'id');
    }

    public function anggota_tim_kerja()
    {
        return $this->belongsTo(Anggota::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function logs()
    {
        return $this->hasMany(CutiLogs::class, 'cuti_id');
    }
}
