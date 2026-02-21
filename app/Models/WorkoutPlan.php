<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];
}
