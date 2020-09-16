<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('intro');
            $table->string('btn_start')->nullable();
            $table->string('btn_submit')->nullable();
            $table->string('google_analytics')->nullable();
            $table->string('facebook_pixel')->nullable();
            $table->string('welcome_image');
            $table->integer('population_id')->nullable();
            $table->integer('theme_id');
            $table->string('language');
            $table->boolean('limit');
            $table->integer('views')->default(0);
            $table->integer('timer_min')->nullable();
            $table->integer('timer_sec')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->boolean('auto_submit')->nullable();
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
        Schema::dropIfExists('surveys');
    }
}
