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
Route::post('/tokens/create', [AuthController::class, 'store']);
// Route::get('/user/info', [AuthController::class,'getUserInfo']);
Route::get('/test', function (){
   dd(getenv('stateful'),parse_url(env('APP_URL'), PHP_URL_HOST));
});
Route::middleware(['auth:sanctum'])->group(function ($router) {

    // 通过token验证
    $router->get('/user/info', [AuthController::class, 'getUserInfo']);


});
