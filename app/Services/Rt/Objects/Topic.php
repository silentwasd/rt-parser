<?php

namespace App\Services\Rt\Objects;

class Topic
{
    public string $title;
    public array $description = [];
    public array $comments = [];
    public string $magnet;
    public int $size;
    public int $seeds;
    public int $leeches;
}
