# Laravel --Jobs （同步\异步）消息队列 Queue  【代码齐全，可下载拿去参考，便于新手学习】
> 消息队列系统完成发货消息通知 - jobs任务类

## 准备工作
修改.env
```shell
QUEUE_CONNECTION=database   # 异步（database）
```
生成队列数据表（jobs）、失败队列数据表（faild_jobs）
```shell
php artisan queue:table
php artisan queue:faild-table
php artisan migrate
```
填充数据 
/database/seeders/DatabaseSeeder.php
```php
public function run()
{
    // 放开注释
     \App\Models\User::factory(10)->create();
}
```
```shell
php artisan db:seed   # 执行填充
```
## 创建任务
```shell
php artisan make:job SendUserSmsOrder
```
app/Jobs/SendUserSmsOrder.php
```php
<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUserSmsOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $userTel;
    protected $name;

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
    public function __construct(int $userId,$userTel)
    {
        $this->userId = $userId;
        $this->userTel = $userTel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $user = User::find($this->userId);
            $this->name = $user->name;

            // 发送短信
            $content = "{$this->name}先生/女士，您在xxx商城购买的商品已发货，请注意查收，物流信息请登录xxx小程序查询【xxx】";
            $result = $this->sendSmsApi($this->userTel, $content);
            if ($result['returnstatus'] == 'Success') {
                echo date('Y-m-d H:i:s') . "\n" . $this->name . '的短信已发送...';
                \Log::info($this->name . '短信发送成功');
            } else {
                \Log::info($this->name . '短信发送失败');
            }
        } catch (\Exception $exception) {
            \Log::error($this->name.'队列任务执行失败'."\n".date('Y-m-d H:i:s'));
            //$this->release($this->attempts() * 10);   // 失败则重新加入队列
        }
    }

    /**
     * 处理一个失败的任务
     *
     * @return void
     */
    public function failed()
    {
        \Log::error($this->name.'队列任务执行失败'."\n".date('Y-m-d H:i:s'));
    }

    /**
     * 发送短信，请求第三方短信接口
     * @param $tel
     * @param $content
     */
    public function sendSmsApi($tel, $content)
    {
        // 这里根据自己使用的短信接口自行实现。。。
        // ...
        return ['returnstatus'=>'Success'];  // 假数据
    }
}
```
开启队列监听
```shell
php artisan queue:work --queue=send-sms
```
>【线上】使用supervisor守护进程, 附上配置文件
```shell
[program:Jobs_SendSmsOrder_QueueWork]
process_name=%(program_name)s_%(process_num)02d
command=php artisan queue:work --queue=send-sms   # --sleep=3 --tries=3
directory=/home/vagrant/code/bonli_wx_shop
autostart=true
autorestart=true
#user=forge
numprocs=8
redirect_stderr=true
stdout_logfile=/home/vagrant/code/wwwlogs/supervisord/jobs_sendsmsorder_queuework.log
stopwaitsecs=3600
```
## 使用
创建测试路由
```php
Route::get('/send-sms', function () {

    SendUserSmsOrder::dispatch(1,13888888888)
        ->onConnection('database')
        ->onQueue('send-sms');
    return '发送...';
});
```
## 效果
![Alt text](https://github.com/lyne007/laravel-advanced-demo/blob/master/imgs/queue-sendSMS.png?raw=true)


