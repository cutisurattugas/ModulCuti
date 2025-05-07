<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCutiLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuti_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->unsignedBigInteger('cuti_id');
            $table->string('updated_by');
            $table->foreign('cuti_id')->references('id')->on('cuti')->onDelete('cascade');
            $table->foreign('updated_by')->references('username')->on('pegawai')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('cuti_logs');
    }
}
