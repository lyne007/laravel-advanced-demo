# jwt-auth + 微信网页授权 【代码齐全，可下载拿去参考，便于新手学习】

* laravel 8.*
* jwt-auth: 1.0.2
* laravel-wechat:^5.1

> 微信后台设置注意点：
> 1，js安全域名不需要`http` `https`
> 2，微信授权回调域名不要`http` `https`，比如回调地址是https://www.abc.com/callback 微信授权回调域名设置为：`www.abc.com` 即可。

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
 /*
     * 公众号
     */
    'official_account' => [
        'default' => [
            'app_id'  => env('WECHAT_OFFICIAL_ACCOUNT_APPID', 'your-app-id'),         // AppID
            'secret'  => env('WECHAT_OFFICIAL_ACCOUNT_SECRET', 'your-app-secret'),    // AppSecret
            'token'   => env('WECHAT_OFFICIAL_ACCOUNT_TOKEN', 'your-token'),           // Token
            'aes_key' => env('WECHAT_OFFICIAL_ACCOUNT_AES_KEY', ''),                 // EncodingAESKey

            /*
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址(如果使用中间件，则随便填写。。。)
             * enforce_https：是否强制使用 HTTPS 跳转
             */
             'oauth'   => [
                 'scopes'        => array_map('trim', explode(',', env('WECHAT_OFFICIAL_ACCOUNT_OAUTH_SCOPES', 'snsapi_userinfo'))),
                 'callback'      => env('WECHAT_OFFICIAL_ACCOUNT_OAUTH_CALLBACK', '/examples/oauth_callback.php'),
                 'enforce_https' => true,
             ],
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

#微信授权【公众号】
WECHAT_OFFICIAL_ACCOUNT_APPID=
WECHAT_OFFICIAL_ACCOUNT_SECRET=
WECHAT_OFFICIAL_ACCOUNT_TOKEN=
WECHAT_OFFICIAL_ACCOUNT_OAUTH_SCOPES=snsapi_userinfo
WECHAT_OFFICIAL_ACCOUNT_OAUTH_CALLBACK=
```
在中间件 App\Http\Middleware\VerifyCsrfToken 排除微信相关的路由，如：
```php
protected $except = [
    // ...
    'wechat',
    'api/wechat',
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
## 创建auth控制器
```shell
php artisan make:controller AuthController
```
app/Htpp/Controllers/AuthController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Overtrue\Socialite\AuthorizeFailedException;
use PHPUnit\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class AuthController extends Controller
{

    /**
     * Create a new AuthController instance.
     * 要求附带email和password（数据来源users表）
     *
     * @return void
     */
    public function __construct()
    {
        // 这里额外注意了：官方文档样例中只除外了『login』
        // 这样的结果是，token 只能在有效期以内进行刷新，过期无法刷新
        // 如果把 refresh 也放进去，token 即使过期但仍在刷新期以内也可刷新
        // 不过刷新一次作废
        // $this->middleware('jwt.auth', ['except' => ['login','refresh']]);
        // 另外关于上面的中间件，官方文档写的是『auth:api』
        // 但是我推荐用 『jwt.auth』，效果是一样的，但是有更加丰富的报错信息返回
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // 前端授权获取code
            $code = $request->get('code');
            // 实例化
            $app = app('wechat.official_account');
            // 获取微信用户信息
            $user = $app->oauth->user();  // 因为code只能用一次，如果是重复使用时这里会报错，所有用AuthorizeFailedException来接收异常
            Log::debug('user:'.json_encode($user));
            $data  = $user->getOriginal();
            Log::debug('getOriginal:'.json_encode($data));

            if (!$data) {
                return response()->json(['code'=>403, 'msg'=>'code异常','data'=>null]);
            }
            $weappOpenid = $data['openid'];
            $user = User::UpdateOrCreate(['openid' => $weappOpenid], [
                'openid' => $weappOpenid,
                'nickname' => $data['nickname']??'',
                'avatar' => $data['headimgurl']??'',
                'gender' => $data['sex']??'',
                'city' => $data['city']??'',
                'province' => $data['province']??'',
                'country' => $data['country']??'',
                'language' => $data['language']??'',
                'session_key' => '',
                'password' => Hash::make($weappOpenid),
            ]);

            $credentials = array("id"=>$user->id,"openid"=>$weappOpenid,"avatar"=>$user->avatar,"nickname"=>$user->nickname,"password"=>$weappOpenid);
            if(! $token = auth('api')->attempt($credentials)){
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->respondWithToken($token);
        } catch (AuthorizeFailedException $exception) {

            return response()->json(['code'=>403, 'msg'=>'['.$exception->body['errcode'].']'.$exception->body['errmsg'],'data'=>null]);
        }

    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(JWTAuth::parseToken()->touser());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        JWTAuth::parseToken()->invalidate();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::parseToken()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
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
// 前端向后台提供code即可，会得到微信用户信息，并返回jwt的token
Route::namespace('wechat')->any('auth/login', [AuthController::class,'login']);
Route::post('auth/refresh', [AuthController::class,'refresh']);

Route::group([
    'middleware' => ['token.refresh','jwt.auth'], //jwt.auth
//    'prefix' => ''
], function ($router) {
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('me', [AuthController::class,'me']);
//    Route::get('users', [UsersController::class,'index']);
});
```

> 当access_token过期后会在任意请求当相应头返回通过Authorization返回新token，只要替换本地缓存即可继续后续请求。

