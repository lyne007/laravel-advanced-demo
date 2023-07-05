<?php


namespace App\Models;


use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    // 为数组 / JSON 序列化准备日期
    use SerializeDate;
}
