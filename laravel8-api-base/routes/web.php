<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    list($code, $message) = \App\Helpers\ResponseEnum::CLIENT_NOT_FOUND_ERROR;
    return response()->json([
        'status'  => 'fail',
        'code'    => $code,
        'message' => $message,
        'data'    => null,
        'error'  => '',
    ]);
});
