<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OauthKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->string('service', 100);
            $table->string('access_token', 100);
            $table->timestamp('expires')->useCurrent();
            $table->string('refresh_token', 100);
            $table->string('uid', 100);
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
        Schema::dropIfExists('oauth_keys');
    }
}
