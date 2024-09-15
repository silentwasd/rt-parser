<?php

namespace App\Services\Rt\Objects;

class SearchTopic
{
    public ?int $id;
    public ?string $name;
    public ?string $author;
    public ?int $size;
    public ?int $seeds;
    public ?int $leeches;
    public ?int $downloads;
    public ?int $created_at;
    public ?string $category;
}
