<?php

use Illuminate\Support\Facades\Route;
use Plugins\ImageX\Controllers\WebController;

Route::prefix('imagex')->group(function () {
    Route::get('upload', [WebController::class, 'upload'])->name('imagex.upload.file');
});
