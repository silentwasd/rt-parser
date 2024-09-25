<?php

namespace App\Http\Resources;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Movie */
class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'topic_id' => $this->topic->original_id,
            'title'    => $this->title,
            'cover'    => $this->cover?->path,
            'year'     => [
                'from' => $this->year_from,
                'to'   => $this->year_to
            ]
        ];
    }
}
