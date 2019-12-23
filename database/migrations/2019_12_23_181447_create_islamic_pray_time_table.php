<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIslamicPrayTimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('islamic_pray_time', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('city');
            $table->integer('notification')->default('1');
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
        Schema::dropIfExists('islamic_pray_time');
    }
}
