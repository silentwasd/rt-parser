<?php

namespace App\Services\Html\Parser;

class Filter
{
    public ?string $name = null;
    public array $attributes = [];
    public array $classes = [];
    public ?string $text = null;

    public function __construct(?string $name = null, array $attributes = [], ?string $class = null, ?string $id = null, array $classes = [], ?string $text = null)
    {
        $this->name = $name;

        $this->attributes = [
            ...$attributes,
            ...$class ? ['class' => $class] : [],
            ...$id ? ['id' => $id] : []
        ];

        $this->classes = $classes;

        $this->text = $text;
    }

    public function equals(Element $element): bool
    {
        $classes = collect(
            explode(" ", $element->attributes['class'] ?? '')
        )->map(fn(string $class) => trim($class))->filter(fn(string $class) => $class);

        return (($this->name && $element->name == $this->name) || !$this->name) &&
            (
                (
                    count($this->attributes) > 0 &&
                    count(array_intersect($element->attributes, $this->attributes)) == count($this->attributes)
                ) ||
                count($this->attributes) == 0
            ) &&
            (
                (
                    count($this->classes) > 0 &&
                    $classes->intersect($this->classes)->count() == count($this->classes)
                ) ||
                count($this->classes) == 0
            ) &&
            (
                (
                    $this->text != null &&
                    $this->text == $element->text
                ) ||
                $this->text == null
            );
    }
}
