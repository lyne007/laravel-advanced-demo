<?php

namespace App\Jobs;

use App\Models\Goods;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class CloseExpiredOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $success_user_key;  // 成功抢购的用户集合key
    protected $goods_key;  // 库存队列中的商品key
    protected $orderId;    // 订单id，用于关闭订单

    /**
     * 在超时之前任务可以运行的秒数
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * 任务可尝试的次数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($success_user_key,$goods_key,$orderId)
    {
        $this->success_user_key = $success_user_key;
        $this->goods_key = $goods_key;
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $order = Order::find($this->orderId);
            $order->state = 9;  // 失效
            $order->save();
            \Log::info('订单：'.$this->orderId." 已更新为失效 \n".date('Y-m-d H:i:s'));

            // 库存释放，成功抢购用户集合去除
            Redis::rpush($this->goods_key, 1);
            Redis::srem($this->success_user_key, $order->user_id);
            \Log::info('商品：'.$this->goods_key." 库存已释放 \n".date('Y-m-d H:i:s'));

        } catch (\Exception $exception) {
            \Log::error('订单：'.$this->orderId.' 队列任务执行失败'."\n".date('Y-m-d H:i:s'));
        }
    }

    /**
     * 处理一个失败的任务
     *
     * @return void
     */
    public function failed()
    {
        \Log::error('订单：'.$this->orderId.'队列任务执行失败'."\n".date('Y-m-d H:i:s'));
    }

}
