<?php

namespace App\Services;

use App\Services\BaseService;

class UserService extends BaseService
{
    // 获取用户信息
    public function getUserInfo()
    {
        return ['id' => 1, 'nickname' => '张三', 'age' => 18];
    }


}
