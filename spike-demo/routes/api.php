<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoodsController;
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
Route::post('auth/login', [AuthController::class,'login']);

Route::group([
    'middleware' => ['token.refresh','jwt.auth'], //jwt.auth
//    'prefix' => ''
], function ($router) {
    $router->get('goods',[GoodsController::class,'index']);
    $router->get('goodsDetail/{id}', [GoodsController::class,'goodsDetail']);
    $router->post('syncStock', [GoodsController::class, 'syncStock']);
    $router->post('checkStock', [GoodsController::class, 'checkStock']);
    $router->post('createOrder', [GoodsController::class, 'createOrder']);
});

