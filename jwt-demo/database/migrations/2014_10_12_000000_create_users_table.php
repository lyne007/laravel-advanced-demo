<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nickname')->nullable();
            $table->string('avatar')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->unsignedSmallInteger('gender')->default(0)->comment('性别 0 未知 1 男 2女');
            $table->string('language')->nullable();
            $table->string('session_key')->nullable();
            $table->char('openid',32);
            $table->char('mobile',11)->nullable();
            $table->json('watermark')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
