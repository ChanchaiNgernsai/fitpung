<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trainer extends Model
{
    /** @use HasFactory<\Database\Factories\TrainerFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty',
        'bio',
        'experience_years',
        'price_per_session',
        'image_path',
        'gender',
        'is_verified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(TrainerReview::class);
    }

    public function courses()
    {
        return $this->hasMany(TrainerCourse::class);
    }
}
