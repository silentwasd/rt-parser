<?php

namespace App\Models;

use App\Enums\GenreType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Movie extends Model
{
    protected $fillable = [
        'topic_id',
        'title',
        'second_title',
        'year_from',
        'year_to',
        'release_id',
        'forum_id'
    ];

    public function cover(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)
                    ->where('genre_type', GenreType::Movie);
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
