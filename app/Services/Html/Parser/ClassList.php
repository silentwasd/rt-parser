<?php

namespace App\Services\Html\Parser;

class ClassList
{
    private array $items = [];

    public function __construct(string $class)
    {
        $this->items = explode(' ', $class);
    }

    public function has(string|array $name): bool
    {
        if (is_array($name))
            return count(array_diff($name, $this->items)) == 0;

        return in_array($name, $this->items);
    }

    public function mustBe(array $names): bool
    {
        return count(array_intersect($names, $this->items)) > 0;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function inline(): string
    {
        return implode(' ', $this->items);
    }
}
