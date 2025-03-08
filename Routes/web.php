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
        Route::get('/tambah', 'CutiController@create')->name('cuti.create');
        Route::post('/store', 'CutiController@store')->name('cuti.store');
    });
    Route::prefix('jenis')->group(function () {
        Route::get('/', 'JenisCutiController@index')->name('jenis_cuti.index');
        Route::get('/tambah', 'JenisCutiController@create')->name('jenis_cuti.create');
        Route::post('/store', 'JenisCutiController@store')->name('jenis_cuti.store');
        Route::get('/edit/{id}', 'JenisCutiController@edit')->name('jenis_cuti.edit');
        Route::put('/update/{id}', 'JenisCutiController@update')->name('jenis_cuti.update');
        Route::delete('/destroy/{id}', 'JenisCutiController@destroy')->name('jenis_cuti.destroy');
    });
});
