<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimKerjaAnggotaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tim_kerja_anggota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tim_kerja_id');
            $table->unsignedBigInteger('pegawai_id');
            $table->string('peran')->nullable();
            $table->timestamps();
            
            $table->foreign('tim_kerja_id')->references('id')->on('tim_kerja')->onDelete('cascade');
            $table->foreign('pegawai_id')->references('id')->on('pegawais')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tim_kerja_anggota');
    }
}
