<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

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
        $code = $request->get('code');
        $encryptedData = $request->get('encrypted_data');
        $iv = $request->get('iv');
        $rawData = $request->get('rawData');
        if (!$code || !$encryptedData || !$iv) {
            return response()->json(['code'=>403, 'msg'=>'参数不合法','data'=>null]);
        }
        $app = \EasyWeChat::miniProgram(); // 小程序
        // 均支持传入配置账号名称
        //  \EasyWeChat::officialAccount('foo'); // `foo` 为配置文件中的名称，默认为 `default`
        $data = $app->auth->session($code);
        if (!$data) {
            return response()->json(['code'=>403, 'msg'=>'code异常','data'=>null]);
        }
        $weappOpenid = $data['openid'];
        $weixinSessionKey = $data['session_key'];
//        $decryptedData = $app->encryptor->decryptData($weixinSessionKey, $iv, $encryptedData);
        $rawData = json_decode($rawData,true);
        $user = User::UpdateOrCreate(['openid' => $weappOpenid], [
            'openid' => $weappOpenid,
            'nickname' => $rawData['nickName']??'',
            'avatar' => $rawData['avatarUrl']??'',
            'gender' => $rawData['gender']??'',
            'city' => $rawData['city']??'',
            'province' => $rawData['province']??'',
            'country' => $rawData['country']??'',
            'language' => $rawData['language']??'',
            'session_key' => $weixinSessionKey,
            'password' => Hash::make($weappOpenid),
            // 'watermark' => '',
            // 'unionId' => $decryptedData['unionId'] ?? '',
            // 'mobile' => $decryptedData['mobile'] ?? '',
//            'created_at' => date('Y-m-d H:i:s'),
//            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $credentials = array("id"=>$user->id,"openid"=>$weappOpenid,"avatar"=>$user->avatar,"nickname"=>$user->nickname,"password"=>$weappOpenid);
        if(! $token = auth('api')->attempt($credentials)){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
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
