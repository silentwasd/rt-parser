<?php

namespace App\Services\Rt\Objects;

class ForumTopic
{
    public ?int $id;
    public ?string $name;
    public ?string $author;
    public ?int $size;
    public ?int $seeds;
    public ?int $leeches;
    public ?int $downloads;

    public static function parseSize(string $size): int
    {
        if (!preg_match('/^([0-9.]+)\s(TB|GB|MB|KB)$/u', $size, $match))
            return 0;

        $value = (float)$match[1];
        $unit = $match[2];

        $unitMlp = [
            "TB" => 1024 * 1024 * 1024 * 1024,
            "GB" => 1024 * 1024 * 1024,
            "MB" => 1024 * 1024,
            "KB" => 1024
        ];

        return (int)round($value * $unitMlp[$unit]);
    }
}
