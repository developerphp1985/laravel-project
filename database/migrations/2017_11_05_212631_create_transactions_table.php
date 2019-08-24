<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('transaction_id');
            $table->integer('user_id');
            $table->string('ledger', 20)->nullable()->comment('ELT, BTC, ETH, EUR');
            $table->double('value')->nullable(false)->default(0.00);
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0 = failed, 1 = success, 2 = pending');
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
        Schema::dropIfExists('transactions');
    }
}
