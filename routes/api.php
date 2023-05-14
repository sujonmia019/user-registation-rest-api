<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('users/{id?}', [UserController::class, 'index']);
Route::post('add-user', [UserController::class, 'store']);
Route::post('add-multi-user', [UserController::class, 'multiUser']);
Route::put('user/{id}/update', [UserController::class, 'update']);
Route::delete('user/delete/{id}', [UserController::class, 'delete']);
Route::post('user/multi-delete', [UserController::class, 'multiDelete']);
