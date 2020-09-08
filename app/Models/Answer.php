<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'question_id',
        'content',
        'image',
        'correct',
        'jump_question_id'
    ];
}
