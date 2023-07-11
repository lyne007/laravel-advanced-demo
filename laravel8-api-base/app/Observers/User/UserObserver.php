<?php

namespace App\Observers\User;

use Illuminate\Support\Facades\DB;
use App\Models\Api\User;

class UserObserver
{
    public function saved(User $user)
    {
        rescue(function () use ($user) {
            $wasRecentlyCreated = $user->wasRecentlyCreated;
            if ($wasRecentlyCreated) {
                // 创建用户后需要执行的业务逻辑， 比如注册成功发送短信
                // dispatch(new UserSmsJobs($user, $wasRecentlyCreated));
            }
        });
    }
}
