<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCutiSisaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuti_sisa', function (Blueprint $table) {
            $table->id();
            $table->string('pegawai_username');
            $table->integer('tahun');
            $table->integer('cuti_awal')->default(12);
            $table->integer('cuti_dibawa')->default(0);
            $table->integer('cuti_digunakan')->default(0);
            $table->foreign('pegawai_username')->references('username')->on('pegawai')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('cuti_sisa');
    }
}
