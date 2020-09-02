<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'img_url',
        'size_ans',
        'size_ques',
        'text_color',
        'border_color',
        'size_ans_img',
        'background_color',
        'button_color',
        'footer_color',
        'font_family'
    ];
}
