<?php

namespace App\Services\Html\Parser;

class ClassList
{
    private array $items = [];

    public function __construct(string $class)
    {
        $this->items = explode(' ', $class);
    }

    public function has(string $name): bool
    {
        return in_array($name, $this->items);
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
