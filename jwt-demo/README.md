# jwt-auth + 微信小程序授权 【代码齐全，可下载拿去参考，便于新手学习】

* laravel 8.*
* jwt-auth: 1.0.2
* laravel-wechat:^5.1

## 安装 tymon/jwt-auth 扩展包
让我们在这个 Laravel 应用中安装这个扩展包。如果您正在使用 Laravel 5.5 或以上版本，请运行以下命令来获取 dev-develop 版本的 JWT 包：
```shell
composer require tymon/jwt-auth:dev-develop --prefer-source
```
发布配置文件
```shell
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```
生成 JWT 密钥
```shell
php artisan jwt:secret
```
修改config/auth.php
```php
return [
    'defaults' => [
        'guard' => 'api',   // 修改
        'passwords' => 'users',
    ],
    'guards' => [
        ...
        // 增加
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
            'hash' => false,
        ],
    ],
    ...
    
];
```
## 安装laravel-wechat
```shell
composer require "overtrue/laravel-wechat:^5.1"
```
创建配置文件
```shell
php artisan vendor:publish --provider="Overtrue\LaravelWeChat\ServiceProvider"
```
config/wechat.php 放开以下注释
```php
 'mini_program' => [
     'default' => [
         'app_id'  => env('WECHAT_MINI_PROGRAM_APPID', ''),
         'secret'  => env('WECHAT_MINI_PROGRAM_SECRET', ''),
         'token'   => env('WECHAT_MINI_PROGRAM_TOKEN', ''),
         'aes_key' => env('WECHAT_MINI_PROGRAM_AES_KEY', ''),
     ],
 ],
```
.env 配置APPID、SECRET
```shell
JWT_SECRET=
#token有效时间，单位：分钟， 有效时间调整为2个小时
JWT_TTL=60
#为了使令牌无效，您必须启用黑名单。如果不想或不需要此功能，请将其设置为 false。
#当JWT_BLACKLIST_ENABLED=false时，可以在JWT_REFRESH_TTL时间内，无限次刷新使用旧的token换取新的token
#当JWT_BLACKLIST_ENABLED=true时，刷新token后旧的token即刻失效，被放入黑名单
JWT_BLACKLIST_ENABLED=true
#当多个并发请求使用相同的JWT进行时，由于 access_token 的刷新 ，其中一些可能会失败，以秒为单位设置请求时间以防止并发的请求失败。
#时间为10分钟，10分钟之内可以拿旧的token换取新的token。当JWT_BLACKLIST_ENABLED为true时，可以保证不会立即让token失效
JWT_BLACKLIST_GRACE_PERIOD=0

#微信
WECHAT_MINI_PROGRAM_APPID=
WECHAT_MINI_PROGRAM_SECRET=
```
在中间件 App\Http\Middleware\VerifyCsrfToken 排除微信相关的路由，如：
```php
protected $except = [
    // ...
    'wechat',
];
```

## 执行迁移文件
```shell
php artisan migrate
```

## 更新您的用户模型
```php
<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}

```

## 增加中间件处理刷新token
```shell
// 创建中间件
php artisan make:middleware RefreshToken
```
app/Htpp/Middleware/RefreshToken.php
```php
<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class RefreshToken extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        try {

            // 检查此次请求中是否带有 token，如果没有则抛出异常。
            $this->checkForToken($request);

            // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常

            // 检测用户的登录状态，如果正常则通过
            if ($this->auth->parseToken()->authenticate()) {

                return $next($request);
            }

            throw new UnauthorizedHttpException('jwt-auth', '未登录');
        } catch (JWTException $exception) {
            // 此处捕获到了 token 过期所抛出的 TokenExpiredException 异常，我们在这里需要做的是刷新该用户的 token 并将它添加到响应头中
            try {
                // 刷新用户的 token
                $token = $this->auth->refresh();
                // 使用一次性登录以保证此次请求的成功
                Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
                $request->headers->set('Authorization','Bearer '.$token);

            } catch (JWTException $exception) {
                // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
                throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage());
            }
        }

        // 在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
    }

}

```
app/Http/Kernel.php
```php
protected $routeMiddleware = [
    ...
    // 增加
   'token.refresh' => \App\Http\Middleware\RefreshToken::class,
];
```

## 设置路由
```php
Route::post('auth/login', [AuthController::class,'login']);
Route::post('auth/refresh', [AuthController::class,'refresh']);

Route::group([
    'middleware' => ['token.refresh','jwt.auth'], //jwt.auth
//    'prefix' => ''
], function ($router) {
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('me', [AuthController::class,'me']);
    Route::get('users', [UsersController::class,'index']);
});
```

> 当access_token过期后会在任意请求当相应头返回通过Authorization返回新token，只要替换本地缓存即可继续后续请求。

效果：
![Alt text](https://github.com/lyne007/laravel-advanced-demo/blob/master/imgs/jwt-%E5%BE%AE%E4%BF%A1%E5%B0%8F%E7%A8%8B%E5%BA%8F.png?raw=true)

