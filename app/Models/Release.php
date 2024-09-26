<?php

namespace App\Models;

use App\Enums\ReleaseType;
use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = [
        'name',
        'release_type'
    ];

    protected $casts = [
        'release_type' => ReleaseType::class
    ];
}
