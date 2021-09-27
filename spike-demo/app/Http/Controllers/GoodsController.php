<?php

namespace App\Http\Controllers;

use App\Jobs\CloseExpiredOrder;
use App\Models\Goods;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Util\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use function PHPUnit\Framework\throwException;

class GoodsController extends Controller
{
    /**
     * 获取秒杀商品列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $goods = Cache::get('spike-goods-list', function(){
            $result = Goods::select('id','name','price','stock')->get()->toArray();
            $json = json_encode($result);
            Cache::add('spike-goods-list', $json);
            return $json;
        });

        $goods = json_decode($goods,true);
        return response()->json(['code'=>0,'msg'=>'查询成功','data'=>$goods]);
    }

    /**
     * 获取商品详情
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsDetail($id)
    {
        $detail = Cache::get('spike-goods-'.$id, function () use($id) {
            $result = Goods::find($id)->toArray();
            $json = json_encode($result);
            Cache::add('spike-gods-'.$id, $json);
            return $json;
        });
        $detail = json_decode($detail, true);
        return response()->json(['code'=>0,'msg'=>'查询成功','data'=>$detail]);
    }

    /**
     * 同步商品库存到redis队列
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncStock()
    {
        // 查出所有秒杀商品列表
        $result = Goods::get()->toArray();
        // 把秒杀商品写入队列
        $count = [];
        foreach ($result as $val) {
            $goods = "spike_goods_".$val['id'];
            $new_stock = $val['stock'] - Redis::llen($goods);
            for ($i = 0; $i < $new_stock; $i++) {
                Redis::rpush($goods, 1);
            }
            // 查询商品1队列长度，即库存数
            $count[$goods] =  Redis::llen($goods);
        }
        return response()->json(['code'=>0,'msg'=>'同步库存成功','data'=>$count]);
    }

    /**
     * 秒杀逻辑-第一步，验证库存
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStock(Request $request)
    {
        $goods_id = $request->get('goods_id');
        $user_id = JWTAuth::parseToken()->toUser()->id;
        // 商品库存队列key
        $goods = 'spike_goods_'.$goods_id;
        // 商品抢购成功的用户集合
        $success_user = 'success_user_'.$goods_id;

        // 验证用户是否已经存在集合中，存在说明已抢购过了，拒绝
        $result = Redis::sismember($success_user, $user_id);
        if ($result) {
            return response()->json(['code'=>1,'msg'=>'已经抢购过了','data'=>'']);
        }

        // 减库存并验证是否已被抢购光
        if (!Redis::lpop($goods)) {
            return response()->json(['code'=>2,'msg'=>'已被抢光了','data'=>'']);
        }

        // 把成功抢购的用户放入集合中
        if (!Redis::sadd($success_user,$user_id)) {
            // 已经在集合中了，加回库存，防止同一个用户并发请求
            Redis::rpush($goods,1);
            return response()->json(['code'=>1,'msg'=>'已经抢购过了','data'=>'']);
        }

        // 抢购成功，返回结果，进行下单操作
        return response()->json(['code'=>0,'msg'=>'抢购成功','data'=>'']);

    }

    /**
     *  第二步，生成订单信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        $user_id = JWTAuth::parseToken()->toUser()->id;
        $goods_id = $request->get('goods_id');

        $goods = 'spike_goods_'.$goods_id;
        $success_user = 'success_user_'.$goods_id;

        // 判断用户是否在成功抢购集合中
        if (!Redis::sismember($success_user, $user_id)) {
            return response()->json(['code'=>3,'msg'=>'手速慢了','data'=>'']);
        }

        DB::beginTransaction();
        try {
            // 获取商品信息
            $goods_detail = DB::table('goods')->where('id',$goods_id)->first();
            // 验证订单是否已生成
            $check_exist = DB::table('orders')
                ->where('user_id',$user_id)
                ->where('goods_name',$goods_detail->name)
                ->get()->toArray();
            if ($check_exist) {
                return response()->json(['code'=>4,'msg'=>'您已下单成功，赶快支付吧','data'=>'']);
            }
            // 减库存、生成订单
            $result1 = DB::table('goods')->where('id',$goods_id)->decrement('stock');
            // 雪花算法生成唯一数，// 1537200202186752
            $snowflake = new \Godruoyi\Snowflake\Snowflake;
            $order_id = DB::table('orders')->insertGetId([
                'order_no'=>$snowflake->id(),
                'user_id'=>$user_id,
                'goods_name'=>$goods_detail->name,
                'buy_price'=>$goods_detail->price,
                'buy_num'=>1,
                'subtotal'=>$goods_detail->price,
                'consignee'=>'test',
                'phone'=>13555555555,
                'address'=>'上海市徐汇区某某一品豪宅888号88楼88室',
                'state'=>0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result1 || !$order_id) {
                throw new Exception('error');
            }

            DB::commit();
            // 下单成功，跳转到支付页，如果用户15分钟内没有支付需要释放库存，可以用延迟任务处理。
            CloseExpiredOrder::dispatch($success_user,$goods,$order_id)->delay(Carbon::now()->addMinutes(2))
                    ->onConnection('database')
                    ->onQueue('close-expired-order');
            // 创建
            return response()->json(['code'=>0,'msg'=>'下单成功','data'=>'']);

        } catch (\Exception $e) {
            // 加回库存，从成功抢购集合中删除当前用户
            Redis::rpush($goods,1);
            Redis::srem($success_user, $user_id);
            DB::rollBack();
            // 失败，返回抢购页，重新抢购
            return response()->json(['code'=>4,'msg'=>'手速慢了','data'=>'']);
        }

    }

    /**
     * 第三步，支付
     */
    public function payOrder()
    {
        //.....略
    }

}
