<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class User extends Eloquent
{
    use SoftDeletes;
    protected $connection = 'mongodb-demo'; // 库名
    protected $collection = 'users';  // 文档名
    protected $primaryKey = '_id'; // 自增id
    protected $guarded = [];
    protected $dates = ['deleted_at'];
}
