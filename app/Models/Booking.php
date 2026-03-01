<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $table = 'trainer_bookings';

    protected $fillable = [
        'trainer_id',
        'user_id',
        'course_name',
        'booking_date',
        'status',
        'gym_id',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
    ];

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gym()
    {
        return $this->belongsTo(GymLayout::class, 'gym_id');
    }
}
