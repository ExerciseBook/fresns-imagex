<?php

use Illuminate\Support\Facades\Route;
use Plugins\ImageX\Controllers\ApiController;

Route::middleware('api')->prefix('imagex')->group(function ($r) {
    $r->post('files', [ApiController::class, 'applyUpload'])->name('imagex.files.apply');
    $r->patch('files/{sts}', [ApiController::class, 'commitUpload'])->name('imagex.files.commit');
});
