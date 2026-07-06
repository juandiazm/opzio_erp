<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| Nini Integration API Routes
|--------------------------------------------------------------------------
|
| External API endpoints for nini_admin_app to sync wallet recharges.
| Protected by api_token middleware (X-Api-Token header authentication).
|
*/
Route::prefix('nini-integration')->middleware('api_token')->group(function () {
    Route::get('/health', [\App\Http\Controllers\NiniIntegrationController::class, 'health']);
    Route::post('/sync-recharge', [\App\Http\Controllers\NiniIntegrationController::class, 'syncRecharge']);
});
