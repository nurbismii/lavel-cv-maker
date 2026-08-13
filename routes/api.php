<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InternalVPeopleCvController;
use App\Http\Controllers\Api\InternalVPeopleCvFileController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/internal/vpeople/cv-data', [InternalVPeopleCvController::class, 'index'])
    ->withoutMiddleware('throttle:api')
    ->middleware(['vpeople.integration', 'throttle:300,1'])
    ->name('api.internal.vpeople.cv-data');

Route::get('/internal/vpeople/documents/{document}', [InternalVPeopleCvFileController::class, 'document'])
    ->withoutMiddleware('throttle:api')
    ->middleware(['vpeople.integration', 'throttle:120,1'])
    ->name('api.internal.vpeople.documents.show');

Route::get('/internal/vpeople/profiles/{profile}/photo', [InternalVPeopleCvFileController::class, 'photo'])
    ->withoutMiddleware('throttle:api')
    ->middleware(['vpeople.integration', 'throttle:120,1'])
    ->name('api.internal.vpeople.profiles.photo');
