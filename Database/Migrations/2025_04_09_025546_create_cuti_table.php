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
            $table->text('keterangan');
            $table->string('dok_pendukung')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('pegawai_id');
            $table->foreign('pegawai_id')->references('id')->on('pegawai')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('pejabat_id');
            $table->foreign('pejabat_id')->references('id')->on('pejabat')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('tim_kerja_id');
            $table->foreign('tim_kerja_id')->references('id')->on('tim_kerja')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('jenis_cuti_id');
            $table->foreign('jenis_cuti_id')->references('id')->on('jenis_cuti')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
