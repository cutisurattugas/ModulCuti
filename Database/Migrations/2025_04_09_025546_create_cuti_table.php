<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCutiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuti', function (Blueprint $table) {
            $table->id();
            $table->string('tanggal_mulai');
            $table->string('tanggal_selesai');
            $table->integer('jumlah_cuti');
            $table->text('keterangan')->nullable();
            $table->text('catatan_kepegawaian')->nullable();
            $table->text('alasan_batal')->nullable();
            $table->string('dok_pendukung')->nullable();
            $table->string('access_token')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('pegawai_id');
            $table->unsignedBigInteger('pejabat_id');
            $table->timestamp('tanggal_disetujui_pejabat')->nullable();
            $table->unsignedBigInteger('pimpinan_id');
            $table->timestamp('tanggal_disetujui_pimpinan')->nullable();
            $table->unsignedBigInteger('tim_kerja_id');
            $table->unsignedBigInteger('jenis_cuti_id');
            $table->foreign('pegawai_id')->references('id')->on('pegawais')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('pejabat_id')->references('id')->on('pejabats')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('pimpinan_id')->references('id')->on('pejabats')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('tim_kerja_id')->references('id')->on('tim_kerja')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('jenis_cuti_id')->references('id')->on('jenis_cuti')->onDelete('cascade')->onUpdat('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cuti');
    }
}
