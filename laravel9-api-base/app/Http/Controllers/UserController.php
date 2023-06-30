<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function index()
    {
        User::all();
        $id = $this->verifyId('id', null);
        return $this->success($id);

    }

    public function info(Request $request, UserService $userService)
    {
        $user = $userService->getUserInfo();
        return $this->success($user);
    }
}
