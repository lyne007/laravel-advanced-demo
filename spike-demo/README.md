# 秒杀场景 【代码齐全，可下载拿去参考，便于新手学习】
* jwt （可参考jwt-demo）
* redis队列
* laravel延迟任务处理失效订单
* supervisor

## 准备工作
修改.env
```shell
QUEUE_CONNECTION=database   # 异步（database）
```
执行迁移
```shell
php artisan migrate
```
填充数据
```shell
php artisan db:seed   # 执行填充
```

## 创建一个延迟任务
> 当下单成功后，创建一个延迟列队处理15分钟内不支付，取消订单并释放库存

```shell
php artisan make:job CloseExpiredOrder
```
app/Jobs/CloseExpiredOrder.php
```php
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

```
开启队列监听
```shell
php artisan queue:work --queue=close-expired-order
```
>【线上】使用supervisor守护进程, 附上配置文件
```shell
[program:Jobs_CloseExpiredOrder_QueueWork]
process_name=%(program_name)s_%(process_num)02d
command=php artisan queue:work --queue=close-expired-order  # --sleep=3 --tries=3
directory=/home/vagrant/code/laravel-advanced-demo/spike-demo
autostart=true
autorestart=true
#user=forge
numprocs=8
redirect_stderr=true
stdout_logfile=/home/vagrant/code/wwwlogs/supervisord/jobs_closeorder_queuework.log
stopwaitsecs=3600
```
## 接口列表
app/Http/Controllers/GoodsController.php
* 获取token
* 同步秒杀商品到reids
* 获取商品列表
* 获取商品详情
* 秒杀验证
* 下单
* 支付（略）

