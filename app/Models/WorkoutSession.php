<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutSession extends Model
{
    protected $fillable = ['user_id', 'workout_date', 'data'];

    protected $casts = [
        'data' => 'array',
        'workout_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
