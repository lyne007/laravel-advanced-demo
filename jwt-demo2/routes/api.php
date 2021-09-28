<?php

use App\Http\Controllers\AuthController;
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
// 前端向后台提供code即可，会得到微信用户信息，并返回jwt的token
Route::namespace('wechat')->any('auth/login', [AuthController::class,'login']);
Route::post('auth/refresh', [AuthController::class,'refresh']);

Route::group([
    'middleware' => ['token.refresh','jwt.auth'], //jwt.auth
//    'prefix' => ''
], function ($router) {
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('me', [AuthController::class,'me']);
//    Route::get('users', [UsersController::class,'index']);
});
