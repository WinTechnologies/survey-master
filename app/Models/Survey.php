<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $table = 'surveies';

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
        'auto_submit'
    ];

    public function questions()
    {
        return $this->hasMany('App\Models\Question');
    }
}


