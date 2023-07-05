<?php

namespace App\Services;

use App\Helpers\ApiResponse;

class BaseService
{
    // 引入api统一返回消息
    use ApiResponse;

    protected static $instance;

    public static function getInstance()
    {
        if (static::$instance instanceof static){
            return self::$instance;
        }
        static::$instance = new static();
        return self::$instance;
    }

    protected function __construct(){}

    protected function __clone(){}

}
