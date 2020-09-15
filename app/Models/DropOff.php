<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DropOff extends Model
{
    protected $table = 'drop_offs';

    protected $fillable = [
        'survey_id',
        'question_id',
        'answer_status',
        'device',
        'random_session_id'
    ];

    public function survey() {
        return $this->belongsTo('App\Models\Survey','survey_id', 'id');
    }
}