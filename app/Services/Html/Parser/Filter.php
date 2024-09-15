<?php

namespace App\Services\Html\Parser;

class Filter
{
    public ?string $name = null;
    public array $attributes = [];

    public function __construct(?string $name = null, array $attributes = [], ?string $class = null, ?string $id = null)
    {
        $this->name       = $name;
        $this->attributes = [
            ...$attributes,
            ...$class ? ['class' => $class] : [],
            ...$id ? ['id' => $id] : []
        ];
    }

    public function equals(Element $element): bool
    {
        return (($this->name && $element->name == $this->name) || !$this->name) &&
            ((
                count($this->attributes) > 0 &&
                count(array_intersect($element->attributes, $this->attributes)) == count($this->attributes)
            ) || count($this->attributes) == 0);
    }
}
