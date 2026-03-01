<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerCourse extends Model
{
    protected $fillable = [
        'trainer_id',
        'title',
        'description',
        'lesson_plan',
        'duration',
        'level',
        'price',
        'hours',
        'theme_color',
    ];

    protected $casts = [
        'lesson_plan' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }
}
