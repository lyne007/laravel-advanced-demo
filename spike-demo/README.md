#秒杀场景 【适用于新手学习】
* jwt
* redis队列
* laravel延迟任务处理失效订单
* supervisor




## 创建一个延迟任务
> 当下单成功后，创建一个延迟列队处理15分钟内不支付，取消订单并释放库存

```shell
php artisan make:job CloseExpiredOrder
```


