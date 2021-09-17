<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
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
Route::get('/', function(){
    return '123';
});
Route::post('auth/login', [AuthController::class,'login']);
Route::post('auth/refresh', [AuthController::class,'refresh']);

Route::group([
    'middleware' => 'jwt.auth',
//    'prefix' => ''
], function ($router) {
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('me', [AuthController::class,'me']);
    Route::get('users', [UsersController::class,'index']);
});

