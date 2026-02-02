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
        'location',
        'google_map_url',
        'image_path',
        'room_config',
        'items',
        'operating_hours',
        'holidays',
        'promotions',
        'price_list',
        'is_public',
        'description',
        'price',
        'thumbnail_path',
        'is_approved',
    ];

    protected $casts = [
        'room_config' => 'array',
        'items' => 'array',
        'operating_hours' => 'array',
        'holidays' => 'array',
        'promotions' => 'array',
        'price_list' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
