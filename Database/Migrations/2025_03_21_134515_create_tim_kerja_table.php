<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimKerjaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tim_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable(); // untuk relasi hirarki antar unit
            $table->unsignedBigInteger('ketua_id')->nullable(); // relasi ke pejabat
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('tim_kerja')->onDelete('cascade');
            $table->foreign('ketua_id')->references('id')->on('pejabats')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tim_kerja');
    }
}
