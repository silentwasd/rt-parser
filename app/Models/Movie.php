<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movie extends Model
{
    protected $fillable = [
        'topic_id',
        'title',
        'second_title',
        'year_from',
        'year_to'
    ];

    public function cover(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
