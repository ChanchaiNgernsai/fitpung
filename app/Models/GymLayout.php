<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'room_config',
        'items',
    ];

    protected $casts = [
        'room_config' => 'array',
        'items' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
