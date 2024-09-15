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
     * @param Filter[] $exclude
     * @return Element[]
     */
    public function findAll(Filter $filter, bool $recursive = true, array $exclude = []): array
    {
        $found = [];

        foreach ($this->children as $child) {
            if (
                $filter->equals($child) &&
                collect($exclude)->filter(fn(Filter $filter) => $filter->equals($child))->count() == 0
            ) {
                $found[] = $child;
            }

            if ($recursive) {
                if (collect($exclude)->filter(fn(Filter $filter) => $filter->equals($child))->count() > 0)
                    continue;

                $found = [...$found, ...$child->findAll($filter, exclude: $exclude)];
            }
        }

        return $found;
    }

    public function deepText(): string
    {
        return $this->children
            ? collect(
                $this->findAll(new Filter(name: 'text'))
            )->map(fn(Element $element) => trim($element->text))->join("\n")
            : $this->text;
    }

    public function classes(): ClassList
    {
        return new ClassList($this->attributes['class'] ?? '');
    }
}
