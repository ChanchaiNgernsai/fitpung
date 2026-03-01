<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trainer_id',
        'course_name',
        'total_hours',
        'used_hours',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }
}
