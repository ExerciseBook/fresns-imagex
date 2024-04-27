<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;
use Plugins\ImageX\Controllers\ApiController;
use Plugins\ImageX\Middleware\CheckAuth;

Route::prefix('imagex')->middleware([EncryptCookies::class, CheckAuth::class])->group(function ($r) {
    $r->post('files', [ApiController::class, 'applyUpload'])->name('imagex.files.apply');
    $r->patch('files/{sts}', [ApiController::class, 'commitUpload'])->name('imagex.files.commit');
});
