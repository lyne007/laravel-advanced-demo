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
