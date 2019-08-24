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
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('email')->unique();
            $table->string('user_name')->unique();
            $table->string('password');
            $table->string('first_name', 100)->comment('user first name');
            $table->string('last_name', 100)->comment('user last name');
            $table->string('BTC_wallet_address', 100)->nullable();
            $table->string('ETH_wallet_address', 100)->nullable();
            $table->double('BTC_balance')->nullable(false)->default(0);
            $table->double('ETH_balance')->nullable(false)->default(0);
            $table->double('ELT_balance')->nullable(false)->default(0);
            $table->double('EUR_balance')->nullable(false)->default(0);
            $table->string('referrer_key')->nullable();
            $table->string('referrer_user_id')->nullable(false)->default(0);
            $table->string('phone', 50)->nullable();
            $table->string('address1', 50)->nullable();
            $table->string('address2', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->integer('postal_code')->nullable();
            $table->string('country_code')->nullable();
            $table->tinyInteger('role')->default(2)->comment('admin = 1, users = 2');
            $table->tinyInteger('join_via')->default(1)->comment('1 = website, 2 = social');
            $table->bigInteger('social_id')->nullable()->comment('if user comes via social site');
            $table->rememberToken();
            $table->string('registration_ip')->nullable();
            $table->string('last_update_ip')->nullable();
            $table->timestamps();
            $table->tinyInteger('status')->default(0)->comment('0 = inactive, 1 = active and 2 = delete');
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
