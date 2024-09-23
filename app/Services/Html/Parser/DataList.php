<?php

namespace App\Services\Html\Parser;

use Illuminate\Support\Str;

class DataList
{
    private array $props = [];

    public function __construct(array $attributes)
    {
        $this->props = collect($attributes)->filter(fn(string $value, string $attribute) => Str::startsWith($attribute, 'data-'))
                                           ->mapWithKeys(fn(string $value, string $attribute) => [Str::chopStart($attribute, 'data-') => $value])
                                           ->all();
    }

    public function get(string $key): mixed
    {
        return $this->props[$key] ?? null;
    }

    public function all(): array
    {
        return $this->props;
    }
}
