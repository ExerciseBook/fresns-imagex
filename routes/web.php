<?php

use Illuminate\Support\Facades\Route;
use Plugins\ImageX\Controllers\WebController;

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

Route::prefix('imagex')->group(function () {
    Route::get('upload', [WebController::class, 'upload'])->name('imagex.upload.file');
});
