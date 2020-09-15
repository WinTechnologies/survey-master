<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->integer('survey_id');
            $table->integer('question_id');
            $table->integer('answer_id');
            $table->integer('population_id');
            $table->string('session_id');
            $table->string('random_session_id');
            $table->boolean('trust')->default(false);
            $table->string('referer')->nullable();
            $table->mediumText('utm_params')->nullable();
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
        Schema::dropIfExists('results');
    }
}
