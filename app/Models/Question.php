<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
