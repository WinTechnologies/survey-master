<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('img_url');
            $table->integer('size_ans');
            $table->integer('size_ques');
            $table->string('text_color');
            $table->string('border_color');
            $table->integer('size_ans_img');
            $table->string('background_color');
            $table->string('button_color');
            $table->string('footer_color');
            $table->string('font_family');
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
        Schema::dropIfExists('themes');
    }
}
