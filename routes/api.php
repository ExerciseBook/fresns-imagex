<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Plugins\ImageX\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/image-x', function (Request $request) {
//     return $request->user();
// });

// Route::prefix('image-x')->group(function() {
//     Route::get('/', [ApiController\ImageXController::class, 'index']);
// });


Route::middleware('api')->prefix('imagex')->group(function ($r) {
    $r->post('files', [ApiController::class, 'applyUpload'])->name('imagex.files.apply');
    $r->patch('files/{sts}', [ApiController::class, 'commitUpload'])->name('imagex.files.commit');
});