<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Population extends Model
{
    protected $fillable = [
        'group_name',
        'parent_set',
        'size_set',
        'type',
        'utm'
    ];
}
