<?php

namespace App\Services\Html\Parser;

use Illuminate\Support\Str;

class ParserService
{
    private array $defaultTagDefinition = [
        "single"              => false,
        "tags_included"       => true,
        "parse_name"          => true,
        "ignore"              => false,
        "attributes_included" => true,
        "opening_tag"         => [
            "start" => "<",
            "end"   => ">"
        ],
        "closing_tag"         => [
            "start" => "</",
            "end"   => ">"
        ]
    ];

    private array $overrideTagDefinitions = [
        "comment" => [
            "single"              => true,
            "parse_name"          => false,
            "attributes_included" => false,
            "opening_tag"         => [
                "start" => "<!--",
                "end"   => "-->"
            ]
        ],
        "script"  => [
            "tags_included" => false
        ],
        "meta"    => [
            "single" => true
        ],
        "link"    => [
            "single" => true
        ],
        "style"   => [
            "tags_included" => false
        ],
        "doctype" => [
            "single"      => true,
            "parse_name"  => false,
            "opening_tag" => [
                "start" => "<!DOCTYPE",
                "end"   => ">"
            ]
        ],
        "img"     => [
            "single" => true
        ],
        "br"      => [
            "single" => true
        ],
        "input"   => [
            "single" => true
        ],
        "hr"      => [
            "single" => true
        ]
    ];

    public function predictDefinitionKey(string $buffer): ?string
    {
        return collect($this->overrideTagDefinitions)
            ->filter(function (array $definition, string $name) use ($buffer) {
                $merged     = [...$this->defaultTagDefinition, ...$definition];
                $openingTag = $merged["opening_tag"]["start"];

                $name = $merged["parse_name"] ? $name : "";

                return Str::startsWith($buffer, $openingTag . $name);
            })
            ->keys()
            ->first();
    }

    public function getDefinition(?string $key): array
    {
        return [
            ...$this->defaultTagDefinition,
            ...$key == null ? [] : $this->overrideTagDefinitions[$key] ?? []
        ];
    }

    public function parse(string $html): Element
    {
        $nextId = 1;

        $document       = new Element($nextId++);
        $document->name = 'document';
        $root           = &$document->children;

        $parent   = &$root;
        $opened   = [];
        $tagIndex = [];

        $tagStarted = false;
        $tagBuffer  = "";

        $attributes          = [];
        $attributeName       = "";
        $attributeValue      = "";
        $startAttributeName  = false;
        $startAttributeValue = "";
        $emptyAttribute      = false;

        $text = "";

        $VALID_ATTRIBUTE_NAME_CHARS = [...range("A", "z"), ...range(0, 9), "-", "_"];
        $VALID_QUOTES               = ['"', "'"];

        foreach (mb_str_split($html) as $char) {
            if (!$tagStarted && $char == "<") {
                $tagStarted = true;
            }

            if (!$tagStarted) {
                $text .= $char;
                continue;
            }

            $tagBuffer .= $char;

            if (count($opened) > 0) {
                $openedTag     = $tagIndex[$opened[count($opened) - 1]];
                $definitionKey = $this->predictDefinitionKey($openedTag->original);
                $definition    = $this->getDefinition($definitionKey);

                if (!$definition["tags_included"]) {
                    $waitingTag =
                        $definition["closing_tag"]["start"] .
                        $openedTag->name .
                        $definition["closing_tag"]["end"];

                    if (Str::endsWith($tagBuffer, $waitingTag)) {
                        $openedTag->text .= $text . Str::chopEnd($tagBuffer, $waitingTag);
                        $tagStarted      = false;
                        $tagBuffer       = "";
                        $text            = "";

                        array_pop($opened);

                        if (count($opened) > 0) {
                            $parent = &$tagIndex[$opened[count($opened) - 1]]->children;
                        } else {
                            $parent = &$root;
                        }
                    }

                    continue;
                }

                if (
                    Str::startsWith($tagBuffer, $definition["closing_tag"]["start"]) &&
                    Str::endsWith($tagBuffer, $definition["closing_tag"]["end"])
                ) {
                    if ($text) {
                        $textElement       = new Element($nextId++);
                        $textElement->name = "text";
                        $textElement->text = $text;
                        $parent[]          = $textElement;
                    }

                    $openedTag->text .= $text;
                    $tagStarted      = false;
                    $tagBuffer       = "";
                    $text            = "";
                    array_pop($opened);

                    if (count($opened) > 0) {
                        $parent = &$tagIndex[$opened[count($opened) - 1]]->children;
                    } else {
                        $parent = &$root;
                    }

                    continue;
                }
            }

            $definitionKey = $this->predictDefinitionKey($tagBuffer);
            $definition    = $this->getDefinition($definitionKey);

            if (
                ($char == " " || $emptyAttribute) &&
                $definition["attributes_included"] &&
                !$startAttributeName &&
                !$startAttributeValue
            ) {
                $startAttributeName = true;

                if ($emptyAttribute) {
                    $emptyAttribute = false;
                } else {
                    continue;
                }
            }

            if ($startAttributeName) {
                if (in_array($char, $VALID_ATTRIBUTE_NAME_CHARS)) {
                    $attributeName .= $char;
                } elseif ($char == "=") {
                    continue;
                } elseif (!$startAttributeValue && in_array($char, $VALID_QUOTES)) {
                    $startAttributeValue = $char;
                    $startAttributeName  = false;
                    continue;
                } else {
                    $attributes[$attributeName] = "";
                    $attributeName              = "";
                    $startAttributeName         = false;
                    $emptyAttribute             = true;
                }
            }

            if ($startAttributeValue) {
                if ($startAttributeValue != $char) {
                    $attributeValue .= $char;
                } else {
                    $attributes[$attributeName] = $attributeValue;
                    $attributeName              = "";
                    $attributeValue             = "";
                    $startAttributeValue        = "";
                }
            }

            if (
                Str::startsWith($tagBuffer, $definition["opening_tag"]["start"]) &&
                Str::endsWith($tagBuffer, $definition["opening_tag"]["end"])
            ) {
                if (!$definition["ignore"]) {
                    $name = $definitionKey;

                    if (!$definitionKey && preg_match('/^<([-_!A-Za-z0-9]+)/', $tagBuffer, $matches)) {
                        $name = $matches[1];
                    }

                    if (
                        $text &&
                        isset($opened[count($opened) - 1]) &&
                        ($openedTag = $tagIndex[$opened[count($opened) - 1]])
                    ) {
                        $openedTag->text .= $text;
                    }

                    if ($text) {
                        $textElement       = new Element($nextId++);
                        $textElement->name = "text";
                        $textElement->text = $text;
                        $parent[]          = $textElement;
                    }

                    $tag             = new Element($nextId++);
                    $tag->original   = $tagBuffer;
                    $tag->name       = $name;
                    $tag->attributes = collect($attributes)->filter(fn($value, $name) => $name)->all();

                    $parent[] = $tag;

                    if (!$definition["single"]) {
                        $opened[] = $tag->id;
                        $parent   = &$tag->children;
                    }

                    $tagIndex[$tag->id] = $tag;
                }

                $tagStarted = false;
                $tagBuffer  = "";

                $attributes          = [];
                $startAttributeName  = false;
                $startAttributeValue = "";
                $attributeName       = "";
                $attributeValue      = "";
                $emptyAttribute      = false;
                $text                = "";
            }
        }

        return $document;
    }
}
