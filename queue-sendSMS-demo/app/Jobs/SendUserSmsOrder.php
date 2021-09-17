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
