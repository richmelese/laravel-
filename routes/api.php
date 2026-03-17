<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiDocsController;

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

// Swagger / OpenAPI UI (API documentation)
Route::get('docs', [ApiDocsController::class, 'docs'])->name('api.docs');
Route::get('openapi.json', [ApiDocsController::class, 'openapi'])->name('api.openapi');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
