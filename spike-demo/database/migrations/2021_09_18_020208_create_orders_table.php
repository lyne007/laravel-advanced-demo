<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('order_no');
            $table->string('goods_name');
            $table->decimal('buy_price',8,2);
            $table->integer('buy_num');
            $table->decimal('subtotal',8,2);
            $table->string('consignee');
            $table->char('phone',11);
            $table->string('address');
            $table->tinyInteger('state')->comment('状态：0待付款，1待发货，2待签收,9失效');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
