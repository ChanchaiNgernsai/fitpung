<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerSchedule extends Model
{
    protected $fillable = [
        'trainer_id',
        'date',
        'focus_area',
        'description'
    ];
}
