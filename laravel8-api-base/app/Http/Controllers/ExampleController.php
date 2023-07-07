<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseEnum;
use App\Models\Api\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class ExampleController extends BaseController
{
    public function index(Request $request, UserService $userService)
    {
//        $id = $this->verifyId('id', null);
//        dd($id);

        // 使用方法1，
//        $user = UserService::getUserInfo();
        // 使用方法2（不需要单例），$user = $userService->getUserInfo();
//        return $this->success($user);
    }
}
