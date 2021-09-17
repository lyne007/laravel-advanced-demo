<?php

use App\Jobs\SendUserSmsOrder;
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
    return view('welcome');
});

Route::get('/send-sms', function () {

    SendUserSmsOrder::dispatch(1,13888888888)
        ->onConnection('database')
        ->onQueue('send-sms');
    return '发送';
});
