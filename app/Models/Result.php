<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'survey_id',
        'question_id',
        'answer_id',
        'population_id',
        'session_id',
        'random_session_id',
        'trust',
        'referer',
        'utm_params'
    ];
}
