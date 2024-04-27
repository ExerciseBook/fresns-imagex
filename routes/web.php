<?php

use Illuminate\Support\Facades\Route;
use Plugins\ImageX\Controllers\WebController;
use Plugins\ImageX\Middleware\CheckAccess;

Route::prefix('imagex')->group(function () {
    Route::get('upload', [WebController::class, 'upload'])->middleware(CheckAccess::class)->name('imagex.upload.file');
});
