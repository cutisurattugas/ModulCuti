<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::prefix('cuti')->group(function () {
    Route::prefix('pengajuan')->group(function () {
        Route::get('/', 'CutiController@index')->name('cuti.index');
        Route::get('/create', 'CutiController@create')->name('cuti.create');
        Route::post('/store', 'CutiController@store')->name('cuti.store');
        Route::get('/show/{access_token}', 'CutiController@show')->name('cuti.show');
        Route::get('/edit/{access_token}', 'CutiController@edit')->name('cuti.edit');
        Route::put('/update/{access_token}', 'CutiController@update')->name('cuti.update');
        Route::post('/approve-unit-kepegawaian/{access_token}', 'CutiController@approvedByKepegawaian')->name('cuti.approve.unit');
        Route::post('/approve-atasan/{access_token}', 'CutiController@approvedByAtasan')->name('cuti.approve.atasan');
        Route::post('/approve-pimpinan/{access_token}', 'CutiController@approvedByPimpinan')->name('cuti.approve.pimpinan');
        Route::post('/cancel/{access_token}', 'CutiController@cancelCuti')->name('cuti.cancel');
        Route::get('/print/{access_token}', 'CutiController@printCuti')->name('cuti.print');
    });
    Route::prefix('jenis')->group(function () {
        Route::get('/', 'JenisCutiController@index')->name('jenis_cuti.index');
        Route::post('/store', 'JenisCutiController@store')->name('jenis_cuti.store');
        Route::put('/update/{id}', 'JenisCutiController@update')->name('jenis_cuti.update');
        Route::delete('/destroy/{id}', 'JenisCutiController@destroy')->name('jenis_cuti.destroy');
    });
    Route::prefix('rekap')->group(function () {
        Route::get('/', 'RekapCutiController@index')->name('rekap.cuti.index');
        Route::get('/show/{id}', 'RekapCutiController@show')->name('rekap.cuti.show');
        Route::get('export/pdf', 'RekapCutiController@exportPdf')->name('rekap.cuti.export.pdf');
        Route::get('export/excel', 'RekapCutiController@exportExcel')->name('rekap.cuti.export.excel');
    });
});
// Tim Kerja
Route::prefix('tim-kerja')->group(function () {
    Route::get('/', 'TimKerjaController@index')->name('tim.index');
    Route::post('/store', 'TimKerjaController@store')->name('tim.store');
    Route::get('/{id}', 'TimKerjaController@show')->name('tim.show');
});

Route::prefix('anggota')->group(function () {
    Route::post('/store', 'AnggotaController@store')->name('anggota.store');
});
Route::get('/scan-cuti/{access_token}', 'CutiController@scanCuti')->name('cuti.scan');
