<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $table = 'surveys';

    protected $fillable = [
        'title',
        'intro',
        'btn_start',
        'btn_submit',
        'google_analytics',
        'facebook_pixel',
        'welcome_image',
        'population_id',
        'theme_id',
        'language',
        'limit',
        'views',
        'timer_min',
        'timer_sec',
        'expired_at',
        'auto_submit',
        'is_one_response',
        'redirect_url',
        'shape',
        'point',
        'user_id'
    ];

    public function questions()
    {
        return $this->hasMany('App\Models\Question', 'survey_id', 'id');
    }

    public function population() {
        return $this->belongsTo('App\Models\Population','population_id', 'id');
    }

    public function results() {
        return $this->hasMany('App\Models\Result', 'survey_id', 'id');
    }

    public static function boot() {
        parent::boot();
        static::deleting(function($survey) {
            $survey->questions()->get()->each->delete();
            $survey->results()->get()->each->delete();
            $survey->drop_offs()->get()->each->delete();
       });
    }

    public function drop_offs() {
        return $this->hasMany('App\Models\DropOff');
    }
}


