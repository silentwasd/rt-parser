<?php

namespace App\Services\Rt\Repo;

use App\Services\Html\Parser\Element;
use App\Services\Html\Parser\Filter;
use App\Services\Rt\Objects\Topic;

class TopicPageRepo extends Repository
{
    public static function topic(string $body): Topic
    {
        $document = static::parser()->parse($body);

        $table = $document->find(new Filter(id: 'topic_main'));

        $topic = new Topic();

        /*$topic->description = collect(
            $table->find(new Filter(class: 'post_body'))
                  ->findAll(new Filter(name: 'text'), exclude: [new Filter(class: 'sp-wrap')])
        )->map(fn(Element $element) => trim($element->text))->join("\n");*/

        $topic->description = $table->find(new Filter(class: 'post_body'))->toArray();

        return $topic;
    }
}
