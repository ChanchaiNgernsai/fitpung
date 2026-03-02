<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerVerification extends Model
{
    protected $fillable = ['trainer_id', 'user_id', 'date', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
