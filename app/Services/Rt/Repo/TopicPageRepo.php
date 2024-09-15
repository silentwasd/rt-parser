<?php

namespace App\Services\Rt\Repo;

use App\Services\Html\Parser\Element;
use App\Services\Html\Parser\Filter;
use App\Services\Rt\Objects\Topic;

class TopicPageRepo extends Repository
{
    private static function makeElement(array $inject = []): array
    {
        return [
            'type'     => '',
            'text'     => '',
            'children' => [],
            ...$inject
        ];
    }

    private static function makeBlock(): array
    {
        return self::makeElement(['type' => 'block']);
    }

    private static function makeList(Element $root): array
    {
        $elements = [];

        foreach ($root->children as $child) {
            if ($child->name == 'li') {
                $elements[] = self::makeElement([
                    'type'     => 'list-element',
                    'children' => self::makeElements($child)
                ]);
            }
        }

        return $elements;
    }

    private static function makeElements(Element $root, bool $wrapWithBlock = false, bool $insideParagraph = false): array
    {
        $elements = [];

        if ($wrapWithBlock) {
            $block = static::makeBlock();

            $parent = &$block['children'];
        } else {
            $parent = &$elements;
        }

        $element = $wrapWithBlock && !$insideParagraph
            ? static::makeElement(['type' => 'paragraph'])
            : static::makeElement();

        if ($wrapWithBlock) {
            $elements[] = $block;
        }

        foreach ($root->children as $child) {
            if (
                ($child->attributes['class'] ?? '') == 'post-br' ||
                ($child->name == 'hr' && $child->classes()->has('post-hr'))
            ) {
                $parent[] = $element;

                $block = static::makeBlock();

                $parent = &$block['children'];

                $elements[] = $block;

                $element = $wrapWithBlock && !$insideParagraph
                    ? static::makeElement(['type' => 'paragraph'])
                    : static::makeElement();

                continue;
            }

            if ($child->name == 'br') {
                $parent[] = $element;

                $element = $insideParagraph
                    ? static::makeElement()
                    : static::makeElement(['type' => 'paragraph']);

                continue;
            }

            if ($child->name == 'ul' || ($child->name == 'ol' && ($child->attributes['class'] ?? false) && $child->attributes['class'] == 'post-ul')) {
                $parent[] = $element;

                $parent[] = static::makeElement([
                    'type'     => 'list',
                    'variant'  => 'unordered',
                    'children' => self::makeList($child)
                ]);

                $element = static::makeElement();

                continue;
            }

            if ($child->name == 'ol') {
                $parent[] = $element;

                $parent[] = static::makeElement([
                    'type'     => 'list',
                    'variant'  => 'ordered',
                    'children' => self::makeList($child)
                ]);

                $element = static::makeElement();

                continue;
            }

            if ($child->classes()->has('sp-wrap')) {
                continue;
            }

            if ($child->name == 'a') {
                if ($element['type'] == 'paragraph') {
                    $element['children'][] = static::makeElement([
                        'type'     => 'anchor',
                        'href'     => $child->attributes['href'],
                        'children' => self::makeElements($child, insideParagraph: true)
                    ]);

                    continue;
                }

                $parent[] = $element;

                $parent[] = static::makeElement([
                    'type'     => 'anchor',
                    'href'     => $child->attributes['href'],
                    'children' => self::makeElements($child)
                ]);

                $element = static::makeElement();

                continue;
            }

            if ($child->name == 'span') {
                if ($element['type'] == 'paragraph') {
                    $element['children'][] = static::makeElement([
                        'type'     => 'span',
                        'children' => self::makeElements($child, insideParagraph: true)
                    ]);

                    continue;
                }

                $parent[] = $element;

                $parent[] = static::makeElement([
                    'type'     => 'span',
                    'children' => self::makeElements($child)
                ]);

                $element = static::makeElement();

                continue;
            }

            if ($child->name == 'p') {
                $parent[] = $element;

                $parent[] = static::makeElement([
                    'type'     => 'paragraph',
                    'children' => self::makeElements($child)
                ]);

                $element = static::makeElement();

                continue;
            }

            if ($child->name == 'text') {
                $element['children'][] = static::makeElement([
                    'type' => 'text',
                    'text' => $child->text
                ]);

                continue;
            }

            $element['text'] .= html_entity_decode($child->deepText());
        }

        if ($element['text'] || $element['children'])
            $parent[] = $element;

        return $elements;
    }

    /**
     * Transform elements without a type (empties).
     * If an element has children, then we transfer children to the parent element.
     * Else drop that element.
     * @param array $elements
     * @return array
     */
    private static function transformEmpties(array $elements): array
    {
        foreach ($elements as $index => &$element) {
            if ($element['type'] != '' && !$element['children'])
                continue;
            elseif ($element['type'] != '' && $element['children']) {
                $element['children'] = static::transformEmpties($element['children']);
                continue;
            }

            if (!$element['children']) {
                unset($elements[$index]);
                continue;
            }

            $children = $element['children'];
            unset($elements[$index]);
            array_splice($elements, $index, 0, $children);
        }

        return array_values($elements);
    }

    public static function topic(string $body): Topic
    {
        $document = static::parser()->parse($body);

        $table = $document->find(new Filter(id: 'topic_main'));

        $topic = new Topic();

        /*$topic->description = collect(
            $table->find(new Filter(class: 'post_body'))
                  ->findAll(new Filter(name: 'text'), exclude: [new Filter(class: 'sp-wrap')])
        )->map(fn(Element $element) => trim($element->text))->join("\n");*/

        $topic->description = static::transformEmpties(
            static::makeElements($table->find(new Filter(class: 'post_body')), wrapWithBlock: true)
        );

        return $topic;
    }
}
