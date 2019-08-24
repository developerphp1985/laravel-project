<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configurations', function (Blueprint $table) {
           $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->string('name')->nullable(false);
            $table->dateTime('valid_from')->nullable(false);
            $table->dateTime('valid_to')->nullable(false);
            $table->string('defined_value');
            $table->string('defined_unit');
            $table->unsignedInteger('updated_by');
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configurations');
    }
}
