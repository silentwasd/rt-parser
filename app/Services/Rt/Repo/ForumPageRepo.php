<?php

namespace App\Services\Rt\Repo;

use App\Services\Html\Parser\Filter;
use App\Services\Rt\Objects\ForumTopic;
use Illuminate\Support\Str;

class ForumPageRepo extends Repository
{
    public static function topics(string $body): array
    {
        $document = static::parser()->parse($body);

        $pageContainer = $document
            ->find(new Filter(class: "w100 vBottom pad_2"))
            ->find(new Filter(class: "small"))
            ->find(new Filter(name: "b"));

        $pages = [];

        foreach ($pageContainer->children as $child) {
            if (!in_array($child->name, ["b", "a"]) || (int)$child->text < 1) {
                continue;
            }

            $pages[] = $child->text;
        }

        $lastPage = array_reverse($pages)[0];

        $items = [];

        foreach ($document->findAll(new Filter(class: "hl-tr")) as $topicEl) {
            if (!$topicEl->find(new Filter(class: "torTopic bold tt-text")))
                continue;

            $authors = $topicEl->findAll(new Filter(class: "topicAuthor"));

            $items[] = [
                "original_id" => (int)$topicEl->data()->get("topic_id"),
                "name"        => $topicEl->find(new Filter(class: "torTopic bold tt-text"))->text,
                "author"      => count($authors) > 1 ? $authors[1]->text : null,
                "size"        => html_entity_decode(
                    $topicEl->find(new Filter(class: "small f-dl dl-stub"))?->text ?? 0
                ),
                "seeds"       => $topicEl->find(new Filter(class: "seedmed"))?->deepText(),
                "leeches"     => $topicEl->find(new Filter(class: "leechmed"))?->deepText(),
                "downloads"   => $topicEl
                    ->find(
                        new Filter(class: "med", attributes: ["title" => "Торрент скачан"])
                    )
                    ?->find(new Filter(name: "b"))?->text
            ];
        }

        return [
            'items'    => collect($items)->filter(fn(array $item) => $item['size'])
                                         ->map(function (array $item) {
                                             $topic            = new ForumTopic();
                                             $topic->id        = $item['original_id'];
                                             $topic->name      = html_entity_decode($item['name']);
                                             $topic->author    = $item['author'] ? trim(html_entity_decode($item['author'])) : null;
                                             $topic->size      = ForumTopic::parseSize($item['size']);
                                             $topic->seeds     = (int)$item['seeds'];
                                             $topic->leeches   = (int)$item['leeches'];
                                             $topic->downloads = (int)Str::replace(',', '', $item['downloads']);
                                             return $topic;
                                         })
                                         ->all(),
            'lastPage' => (int)$lastPage
        ];
    }
}
