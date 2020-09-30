<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Question extends Model
{
    protected $fillable = [
        'survey_id',
        'type',
        'question',
        'image',
        'order',
        'is_reliability',
        'is_required',
        'is_main',
        'is_random',
        'demographic',
        'answer_limit',
        'jump_id',
        'shape',
        'point',
        'video_src'
    ];

    public function answers()
    {
        return $this->hasMany('App\Models\Answer');
    }

    public function survey() {
        return $this->belongsTo('App\Models\Survey','survey_id', 'id');
    }

    public static function boot() {
        parent::boot();
        static::deleting(function($question) {
            $question->answers()->delete();
       });
    }

    public function result() {
        return $this->hasMany('App\Models\Result');
    }

    public static function question_answers_by_survey($survey_id) {
        $sql = "SELECT q.id as question_id, q.question, a.content, q.type, q.point, a.id as answer_id
                    FROM answers a
                    LEFT JOIN questions q ON q.id = a.question_id
                    WHERE q.survey_id = ".$survey_id."
                    ORDER BY a.id";

        $result = DB::select($sql);

        return $result;
    }

}
