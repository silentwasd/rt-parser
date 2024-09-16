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

    public function toArray(): array
    {
        return collect($this->children)
            ->map(function (Element $element) {
                if ($element->name == 'span') {
                    if ($element->classes()->has('post-br')) {
                        return [
                            'type'     => 'block',
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if ($element->classes()->has('post-b')) {
                        return [
                            'type'     => 'strong',
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if ($element->classes()->has('post-i')) {
                        return [
                            'type'     => 'em',
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if (
                        isset($element->attributes['style']) &&
                        $element->attributes['style'] == 'font-size: 24px; line-height: normal;'
                    ) {
                        return [
                            'type'     => 'headline',
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if ($element->classes()->has('post-align')) {
                        return [
                            'type'     => 'block',
                            'text'     => '',
                            'align'    => isset($element->attributes['style']) && $element->attributes['style'] == 'text-align: center;'
                                ? 'center'
                                : 'left',
                            'children' => $element->toArray()
                        ];
                    }

                    return [
                        'type'     => 'span',
                        'text'     => '',
                        'children' => $element->toArray()
                    ];
                }

                if ($element->name == 'br') {
                    return [
                        'type'     => 'break',
                        'text'     => '',
                        'children' => []
                    ];
                }

                if ($element->name == 'a') {
                    return [
                        'type'     => 'anchor',
                        'text'     => $element->attributes['href'],
                        'children' => $element->toArray()
                    ];
                }

                if ($element->name == 'div') {
                    if ($element->classes()->has('sp-wrap')) {
                        return [
                            'type'     => 'spoiler',
                            'text'     => html_entity_decode(
                                $element->find(new Filter(class: 'sp-head folded'))
                                        ->find(new Filter(name: 'span'))->deepText()
                            ),
                            'children' => $element->find(new Filter(class: 'sp-body'))
                                                  ->toArray()
                        ];
                    }

                    if ($element->classes()->has('post-box')) {
                        return [
                            'type'     => 'block',
                            'variant'  => 'inline',
                            'rounded'  => true,
                            'bordered' => !(isset($element->attributes['style']) && $element->attributes['style'] == 'border-color: transparent;'),
                            'margined' => true,
                            'padded'   => true,
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if ($element->classes()->has('post-box-default')) {
                        return [
                            'type'     => 'block',
                            'variant'  => 'inline',
                            'rounded'  => false,
                            'bordered' => false,
                            'align'    => 'top',
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if ($element->classes()->has('post-box-center')) {
                        return [
                            'type'     => 'block',
                            'variant'  => 'table',
                            'rounded'  => false,
                            'bordered' => false,
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    if ($element->classes()->has('q-wrap')) {
                        return [
                            'type'     => 'quote',
                            'text'     => html_entity_decode(
                                $element->find(new Filter(class: 'q-head'))->find(new Filter(name: 'b'))->text
                            ),
                            'children' => $element->find(new Filter(class: 'q'))->toArray()
                        ];
                    }

                    return [
                        'type'     => 'block',
                        'variant'  => '',
                        'rounded'  => false,
                        'bordered' => false,
                        'text'     => '',
                        'children' => $element->toArray()
                    ];
                }

                if ($element->name == 'u' && $element->classes()->has('q-post')) {
                    return null;
                }

                if ($element->name == 'pre') {
                    if ($element->classes()->has('post-nfo')) {
                        return [
                            'type'     => 'pre',
                            'variant'  => 'nfo',
                            'text'     => '',
                            'children' => $element->toArray()
                        ];
                    }

                    return [
                        'type'     => 'pre',
                        'text'     => '',
                        'children' => $element->toArray()
                    ];
                }

                if ($element->name == 'var') {
                    return [
                        'type'      => 'image',
                        'text'      => $element->attributes['title'],
                        'aligned'   => $element->classes()->has('postImgAligned'),
                        'direction' => $element->classes()->has('img-left')
                            ? 'left'
                            : ($element->classes()->has('img-right') ? 'right' : 'none'),
                        'children'  => []
                    ];
                }

                if ($element->name == 'hr') {
                    return [
                        'type'     => 'horizontal',
                        'text'     => '',
                        'children' => []
                    ];
                }

                if (
                    $element->name == 'ul' ||
                    $element->classes()->has('post-ul')
                ) {
                    return [
                        'type'     => 'list',
                        'variant'  => 'unordered',
                        'text'     => '',
                        'children' => $element->toArray()
                    ];
                }

                if ($element->name == 'ol') {
                    return [
                        'type'     => 'list',
                        'variant'  => 'ordered',
                        'text'     => '',
                        'children' => $element->toArray()
                    ];
                }

                if ($element->name == 'li') {
                    return [
                        'type'     => 'list-element',
                        'text'     => '',
                        'children' => $element->toArray()
                    ];
                }

                return [
                    'type'     => 'text',
                    'text'     => html_entity_decode($element->text),
                    'children' => []
                ];
            })
            ->filter(fn(?array $element) => $element !== null)
            ->all();
    }

    public function __toString(): string
    {
        return collect($this->children)
            ->map(function (Element $element) {
                if ($element->name == 'span') {
                    if ($element->classes()->has('post-br'))
                        return "<div class='py-1.5'></div>";

                    if ($element->classes()->has('post-b'))
                        return "<strong class='font-semibold'>$element</strong>";

                    return "<span>$element</span>";
                }

                if ($element->name == 'br')
                    return '<br/>';

                if ($element->name == 'a')
                    return sprintf("<a href='%s' class='text-primary-600 font-semibold'>%s</a>", $element->attributes['href'], $element);

                if ($element->name == 'div') {
                    if ($element->classes()->has('sp-wrap'))
                        return "<div></div>";

                    return "<div>$element</div>";
                }

                return $element->text;
            })
            ->join("\n");
    }
}
