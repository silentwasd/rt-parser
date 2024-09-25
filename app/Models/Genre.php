<?php

namespace App\Models;

use App\Enums\GenreType;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'genre_type'
    ];

    protected $casts = [
        'genre_type' => GenreType::class
    ];
}
