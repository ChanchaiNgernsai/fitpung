<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'type',
        'target_muscles',
        'technique',
    ];

    protected $casts = [
        'target_muscles' => 'array',
        'technique' => 'array',
    ];
}
