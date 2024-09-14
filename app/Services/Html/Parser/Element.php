<?php

namespace App\Services\Html\Parser;

class Element
{
    public int $id;
    public string $original = "";
    public ?string $name = null;
    public string $text = "";
    public array $attributes = [];

    /** @var Element[] */
    public array $children = [];

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function find(Filter $filter, bool $recursive = true): ?Element
    {
        foreach ($this->children as $child) {
            if ($filter->equals($child))
                return $child;
        }

        if ($recursive) {
            foreach ($this->children as $child) {
                $subChild = $child->find($filter);
                if ($subChild !== null)
                    return $subChild;
            }

            return null;
        }

        return null;
    }

    /**
     * @param Filter $filter
     * @param bool $recursive
     * @return Element[]
     */
    public function findAll(Filter $filter, bool $recursive = true): array
    {
        $found = [];

        foreach ($this->children as $child) {
            if ($filter->equals($child))
                $found[] = $child;
        }

        if ($recursive) {
            foreach ($this->children as $child) {
                $found = [...$found, ...$child->findAll($filter)];
            }
        }

        return $found;
    }
}
