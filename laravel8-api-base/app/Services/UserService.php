<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Helpers\ResponseEnum;
use App\Models\Api\User;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    /**
     * 获取用户信息
     *
     * @param $mobile
     * @param $password
     * @return mixed
     * @throws BusinessException
     */
    static function checkUser($mobile, $password)
    {
        $user = User::where('mobile', $mobile)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            throw new BusinessException(ResponseEnum::CLIENT_HTTP_UNAUTHORIZED);
        }
        return $user;
    }

}
