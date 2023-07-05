<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\VerifyRequestInput;

class BaseController extends Controller
{
    // API接口响应
    use ApiResponse;
    // 验证表单参数输入请求
    use VerifyRequestInput;
}
