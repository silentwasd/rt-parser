<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topic extends Model
{
    protected $fillable = [
        'name',
        'size',
        'seeds',
        'leeches',
        'downloads',
        'author_id'
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
