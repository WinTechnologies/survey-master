<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->integer('survey_id');
            $table->string('type');
            $table->string('question');
            $table->string('image')->nullable();
            $table->integer('order');
            $table->boolean('is_reliability')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_main')->default(false);
            $table->boolean('is_random')->default(false);
            $table->integer('demographic')->default(0);   // 0: default, 1: gender, 2:ages, 3: location, 4: education, 5: other
            $table->integer('answer_limit')->nullable();
            $table->integer('jump_id')->default(0);         // 0: default, > 0: jump id
            $table->string('video_src')->nullable();
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
        Schema::dropIfExists('questions');
    }
}
