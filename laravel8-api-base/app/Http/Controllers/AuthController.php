<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    /**
     * 创建token
     *
     * @param Request $request
     * @return mixed
     * @throws \App\Exceptions\BusinessException
     */
    public function store(Request $request)
    {
        $request->validate([
            'mobile' => 'required',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = UserService::checkUser($request->mobile, $request->password);
        return $user->createToken($request->device_name)->plainTextToken;
    }

    /**
     * 获取用户信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfo(Request $request)
    {
        return $this->success($request->user());
    }
}
