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
        Route::get('/show/{id}', 'CutiController@show')->name('cuti.show');
        Route::post('/approve-unit-kepegawaian/{id}', 'CutiController@approvedByKepegawaian')->name('cuti.approve.unit');
        Route::post('/approve-atasan/{id}', 'CutiController@approvedByAtasan')->name('cuti.approve.atasan');
        Route::post('/approve-pimpinan/{id}', 'CutiController@approvedByPimpinan')->name('cuti.approve.pimpinan');
    });
    Route::prefix('jenis')->group(function () {
        Route::get('/', 'JenisCutiController@index')->name('jenis_cuti.index');
        Route::post('/store', 'JenisCutiController@store')->name('jenis_cuti.store');
        Route::put('/update/{id}', 'JenisCutiController@update')->name('jenis_cuti.update');
        Route::delete('/destroy/{id}', 'JenisCutiController@destroy')->name('jenis_cuti.destroy');
    });
});
